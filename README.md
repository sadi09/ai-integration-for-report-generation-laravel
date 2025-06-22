# Laravel AI-Powered Sales Search App

This Laravel application demonstrates how to integrate **Google AI Studio** with your own product to unlock real-world AI usage. Users can type natural language queries to retrieve meaningful insights from basic sales data.

---

## 🚀 Features

- Natural language search powered by Google AI Studio
- Basic sales database with smart query interpretation
- A practical example of AI integration in traditional applications

---

## 🛠️ Installation & Setup

Follow these steps to set up and run the project:

### 1. Clone the Repository

```bash
git clone https://github.com/your-username/laravel-ai-sales.git
cd laravel-ai-sales
```

### 2. Install PHP & JS Dependencies

```bash
composer install
```

### 3. Configure Environment

Copy the `.env.example` file to `.env` and update the environment settings.

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Set Up Database

Edit `.env` to configure your database connection:

```env
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Run migrations:

```bash
php artisan migrate --seed
```

---

## 🤖 Google AI Studio Setup

To enable AI-powered search, you must configure an API key.

### Step 1: Get API Key

1. Go to [Google AI Studio](https://makersuite.google.com/app).
2. Log in with your Google account.
3. Navigate to "API Access" or go to [Google Cloud Console](https://console.cloud.google.com/).
4. Create a project and enable **Generative Language API**.
5. Generate an API key under **Credentials**.

### Step 2: Add API Key to `.env`

In your `.env` file, add the key as:

```env
AI_TOOL_KEY=your_google_ai_studio_api_key
```

This key allows the app to communicate with Google AI to interpret user queries.

---

## 🧪 Running the App

Start the Laravel development server:

```bash
php artisan serve
```

Visit `http://localhost:8000` and type natural language queries like:

- "Show me the top 3 customers by sales."
- "Which product sold the most last week?"

---


## 📌 Purpose

This project showcases how to integrate AI features into your Laravel application using real data, making your product smarter and more intuitive for users.

---

## 📜 License

This project is open-source and available under the [MIT License](LICENSE).
