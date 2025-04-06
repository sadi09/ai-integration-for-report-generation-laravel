<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AiQueryProcessController extends Controller
{


    public function viewpage()
    {
        return view('ai.ai-query');
    }

    public function convertToSQL(Request $request)
    {
        $allowedTables = [
            'users' => ['id', 'name', 'email'],
            'products' => ['id', 'name', 'price', 'stock_quantity', 'created_at'],
            'sales' => ['id', 'user_id', 'product_id', 'quantity', 'total_price', 'created_at'],
        ];

        $userQuery = $request->input('userQuery');
        $AiApiKey = env('AI_TOOL_KEY');

        // Prepare table and column restrictions
        $tableList = implode(', ', array_keys($allowedTables));
        $columnRestrictions = [];
        foreach ($allowedTables as $table => $columns) {
            $allowedColumnsString = ($columns === ['*']) ? 'all columns' : implode(', ', $columns);
            $columnRestrictions[] = "$table (only columns: $allowedColumnsString)";
        }

        Log::info("Table List: ".$tableList);
        Log::info("Column Resitrictions: ".implode('; ', $columnRestrictions) );

        // AI prompt to generate SQL query
        $prompt = "
    Convert this natural language query into a strict SQL SELECT query for a MySQL 8 database.
    The query must follow these rules:

    1. **Crucially, ALWAYS explicitly specify the columns to SELECT from the allowed list.  Do NOT use `SELECT *`. If the user's query implies 'all information', only select from the allowed columns for the relevant table.**  You MUST output only the specified columns, even if it seems like the user is asking for more implicit information.

    2. Only use the following tables: $tableList.

    3. Only use the following columns per table: " . implode('; ', $columnRestrictions) . ".

    4. Do NOT reference tables or columns outside this list.

    5. Do NOT include subqueries, joins, CTEs (WITH statements), or UNION statements.

    6. **For time-based filtering:**
        *   **Use `CURDATE()` and `NOW()` safely.**  For example, to get records from the last 7 days, use `WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)`. Avoid using `NOW()` if only the date is relevant.
        *   **If the user's query asks for a date range, directly convert the provided dates into `YYYY-MM-DD` format for use in the `WHERE` clause. For example: `WHERE ocs_date >= '2024-01-01' AND ocs_date <= '2024-01-31'`**
        *   **Always use the `created_at` or `ocs_date` column, or similar date/time column specified in the columns, for time-based filtering.**

    7. If the query involves sorting, use the 'ORDER BY' clause and specify the appropriate column and sort order (ASC or DESC).

    8. If the query requests the \"latest\" or \"last\" record, use `ORDER BY` and `LIMIT 1` to return only the most recent record.

    9. The output must contain only the SQL query, without markdown, explanation, or extra formatting.

    10. Ensure compatibility with MySQL 8.

    Here are more examples to guide you:

    *   **User:** \"List all OCS created in the last week\"
        **Expected SQL:** `SELECT ocs_no, ocs_date, department_id, ocs_from FROM tb_ocs_basic_info WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) ORDER BY ocs_date;`

    *   **User:** \"What is the latest OCS?\"
        **Expected SQL:** `SELECT ocs_no, ocs_date FROM tb_ocs_basic_info ORDER BY ocs_date DESC LIMIT 1;`

    *   **User:** \"Show me OCS number OCS-2024-123\"
        **Expected SQL:** `SELECT ocs_no, ocs_date, department_id, ocs_from FROM tb_ocs_basic_info WHERE ocs_no = 'OCS-2024-123';`

    *   **User:** \"How many OCS is from Tazul?\"
        **Expected SQL:** `SELECT COUNT('id') AS ocs_count FROM tb_ocs_basic_info WHERE ocs_from LIKE '%Tazul%';`

    User query: \"$userQuery\"
";

        $prompt = "Convert the 'User command: \"$userQuery' into mysql query. Generate only select query. Only use the following tables: $tableList. Only use the following columns per table: " . implode('; ', $columnRestrictions) . "";

        Log::info("prompt: ".$prompt);

        try {
            // Step 1: Get AI-Generated SQL Query
            $response = Http::timeout(60)->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $AiApiKey, [
                'contents' => [
                    ['parts' => ['text' => $prompt]]
                ]
            ]);

            $response->throw();
            $data = $response->json();
            $sqlQuery = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

            Log::info('Generated SQL: ' . $sqlQuery);

            // Clean SQL query if there is any markdown formatting
            $sqlQuery = preg_replace('/^```sql\s*|\s*```$/', '', trim($sqlQuery));

            if (!$sqlQuery) {
                Log::warning("AI returned an empty SQL query.");
                return response()->json(['error' => "AI returned an empty SQL query. Please try again."]);
            }

            // Step 2: Validate and Execute the SQL Query
            $result = $this->validateAndExecuteQuery($sqlQuery, $allowedTables);

            if ($result === false) {
                Log::error("Validation failed or Query Execution Error: " . $sqlQuery);
                return response()->json(['error' => "Validation failed or Query Execution Error."]);
            }

            // Step 3: Convert Query Result to HTML Structure
            $dataJson = json_encode($result);
            $htmlPrompt = "Generate a structured HTML response based on the following data: $dataJson.
        Ensure the design is user-friendly and visually appealing, using cards, lists, or paragraphs as appropriate. Do not include Markdown formatting, only pure HTML. Do not generate a full html page. Just generate a block using bootstrap classes.";

            // Step 4: Get AI-Generated HTML
            $htmlResponse = Http::timeout(60)->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $AiApiKey, [
                'contents' => [
                    ['parts' => ['text' => $htmlPrompt]]
                ]
            ])->json();

            $htmlOutput = $htmlResponse['candidates'][0]['content']['parts'][0]['text'] ?? null;

            // Ensure HTML is clean (Remove Markdown formatting if present)
            $htmlOutput = preg_replace('/^```html\s*|\s*```$/', '', trim($htmlOutput));

            if (!$htmlOutput) {
                Log::warning("AI returned an empty HTML response.");
                return response()->json(['error' => "AI returned an empty HTML response."]);
            }

            // Return the final HTML response
            return response()->json(['html' => $htmlOutput]);

        } catch (\Exception $e) {
            Log::error("Error in AI Query Processing: " . $e->getMessage());
            return response()->json(['error' => "Server error: " . $e->getMessage()]);
        }
    }

    private function validateAndExecuteQuery($query)
    {
        $allowedColumns = [
            'id', 'ocs_no', 'ocs_date', 'department_id', 'ocs_to', 'ocs_from',
            'buyer_id', 'factory_id', 'style_reference', 'item_no', 'contract_no',
            'description', 'qty', 'pack_of', 'fabric_cost_unit', 'shell_count',
            'season_id', 'company_id', 'size_range', 'item_type', 'currency',
            'gsp_duty', 'fabric_finance', 'add_fabric_breakdown',
            'factory_fob_attachment', 'dsl_fob_attachment', 'factory_fob', 'dsl_fob',
            'status', 'ref_ocs_id', 'created_at', 'updated_at'
        ];

        // Dangerous keywords (keep DATE functions allowed)
        $blacklist = ['UPDATE', 'DELETE', 'INSERT', 'DROP', 'TRUNCATE', 'ALTER', 'UNION', '--', ';'];

        // Allow safe MySQL functions (DATE_SUB, CURDATE, etc.)
        $allowedFunctions = ['DATE_SUB', 'CURDATE', 'NOW', 'DATE', 'YEAR', 'MONTH', 'DAY', 'COUNT', 'SUM', 'DAYOFMONTH', 'MONTHNAME', 'MAX'];

        // Check for dangerous keywords
        foreach ($blacklist as $word) {
            if (preg_match("/\b$word\b/i", $query)) {
                Log::warning("Blocked query containing dangerous keyword: $query");
                return false;
            }
        }

        // Extract columns used in SELECT statement
        if (preg_match('/SELECT (.*?) FROM/i', $query, $matches)) {
            $selectedColumns = explode(',', $matches[1]);
            $selectedColumns = array_map('trim', $selectedColumns);

            // Ensure only allowed columns are used
//            foreach ($selectedColumns as $column) {
//                if (!in_array($column, $allowedColumns)) {
//                    Log::warning("Blocked query due to unauthorized column: $column");
//                    return false;
//                }
//            }
        }

        // Allow safe functions, block others
        if (preg_match_all('/\b([A-Z_]+)\(/i', $query, $matches)) {
            foreach ($matches[1] as $function) {
                if (!in_array(strtoupper($function), $allowedFunctions)) {
                    Log::warning("Blocked query using unauthorized function: $function");
                    return false;
                }
            }
        }

        // Execute query securely
        try {
            $result = DB::select($query);
            return $result;
        } catch (\Exception $e) {
            Log::error("Query Execution Error: " . $e->getMessage());
            return false;
        }
    }
}
