<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Playing With AI</title>

    <!-- MDB Bootstrap (Material Design) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <!-- jQuery (For AJAX) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- MDB JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>

    <style>
        .custom-card {
            border-radius: 12px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
        <h4 class="text-primary">
            <i class="bi bi-bookmarks"></i> Playing With AI
        </h4>
        <div class="pagetitle-btn"></div>
    </div>

    <div class="card p-4 custom-card">
        <div class="mb-3">
            <!-- Input Form -->
            <form action="{{ route('ai-query-process') }}" method="POST" id="searchForm">
                @csrf
                <div class="input-group mb-3">
                    <input type="text" id="userQuery" name="userQuery" class="form-control"
                           placeholder="Enter your search query..." required>
                </div>

                <!-- Centered Button -->
                <div class="text-center">
                    <button type="submit" id="searchButton" class="btn btn-primary ripple">
                        <i class="bi bi-search"></i> Search With AI
                    </button>
                </div>
            </form>
        </div>

        <!-- Error Message -->
        <div id="errorAlert" class="alert alert-danger mt-3 d-none"></div>

        <!-- Results Table -->
        <div id="aiResponseContainer"></div>
    </div>
</div>

<script>
    document.getElementById('searchForm').addEventListener('submit', function (event) {
        event.preventDefault();
        let userQuery = $('#userQuery').val();

        // Clear previous response
        $('#aiResponseContainer').html('');

        // Add loading indicator
        $('#searchButton').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Loading...');

        $.ajax({
            url: '/ai-query-process',
            type: 'POST',
            data: {userQuery: userQuery},
            success: function (response) {
                $('#searchButton').prop('disabled', false).html('<i class="bi bi-search"></i> Search With AI');
                if (response.html) {
                    $('#aiResponseContainer').html(response.html);
                } else {
                    $('#aiResponseContainer').html('<p class="text-danger">Error generating response.</p>');
                }
            },
            error: function () {
                $('#searchButton').prop('disabled', false).html('<i class="bi bi-search"></i> Search With AI');
                $('#aiResponseContainer').html('<p class="text-danger">Server error. Please try again.</p>');
            }
        });
    });
</script>

</body>
</html>
