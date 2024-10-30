# README.txt

## Project Overview

This is a Laravel project designed as a coding test for the Senior PHP Developer role at ChannelEngine. The application interacts with the ChannelEngine API to fetch and manage orders and order lines, displaying the top 5 selling products and allowing stock updates.

## Prerequisites

Before you begin, ensure you have the following installed on your system:

- PHP 8.1 or higher
- Composer
- MySQL or another compatible database
- Git

## Installation Steps

Follow these steps to set up the project on your local machine:

### 1. Clone the Repository

Open your terminal and run:

```bash
git clone https://github.com/JesusGarciaValadez/channelEngineChallenge
```

Navigate into the project directory:

```bash
cd channelEngineChallenge
```

### 2. Install PHP Dependencies

Install the PHP dependencies using Composer:

```bash
composer install
```

### 3. Set Up Environment Variables

Create a copy of the example environment file:

```bash
cp .env.example .env
```

Open the `.env` file in a text editor and update the following settings:

#### Application Key

Leave this empty for now; we'll generate it in the next step.

#### Database Configuration

Set up your database connection details:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

Replace `your_database_name`, `your_database_user`, and `your_database_password` with your actual database credentials.

#### ChannelEngine API Configuration

Add the following lines to your `.env` file:

```env
CHANNELENGINE_API_URL=https://api.channelengine.net
CHANNELENGINE_API_KEY=your_channelengine_api_key
```

Replace `your_channelengine_api_key` with your actual API key from ChannelEngine.

### 4. Generate Application Key

Generate the application key:

```bash
php artisan key:generate
```

This command will set the `APP_KEY` value in your `.env` file.

### 5. Set Up the Database

Ensure that the database specified in your `.env` file exists. If not, create it using your database management tool or via the command line:

```bash
mysql -u your_database_user -p -e "CREATE DATABASE your_database_name;"
```

### 6. Run Migrations

Run the database migrations to create the necessary tables:

```bash
php artisan migrate
```

## Running the Application

Start the Laravel development server:

```bash
php artisan serve
```

The application will be accessible at `http://localhost:8000`.

## Running Unit Tests

The project includes unit tests to ensure functionality and reliability.

### 1. Configure the Testing Environment

By default, Laravel uses the `phpunit.xml` file for test configuration. Ensure that your testing environment is correctly set up.

The `phpunit.xml` file should have the correct database configuration for testing. Typically, you can use an in-memory SQLite database for faster tests.

In your `.env.testing` file (create it if it doesn't exist), set the following:

```env
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

### 2. Run the Tests

To run all tests, execute:

```bash
php artisan test
```

Or, to run tests with detailed output:

```bash
php artisan test --verbose
```

### 3. Running Specific Tests

To run a specific test file or method, you can use:

```bash
php artisan test --filter YourTestMethodName
```

Replace `YourTestMethodName` with the name of the test method you want to run.

## Additional Information

### Mocking API Calls

The unit tests mock external API calls using Laravel's `Http` facade. This ensures tests are isolated and do not depend on external services.

### API Key and URL

Ensure that your `CHANNELENGINE_API_KEY` and `CHANNELENGINE_API_URL` are correctly set in your `.env` file. Without these, API calls will fail.

### Contact Information

If you encounter any issues or have questions, please contact the project maintainer at `jesus.garciav@me.com`.

## Summary of Commands

- Clone Repository:

  ```bash
  git clone https://github.com/JesusGarciaValadez/channelEngineChallenge
  cd channelEngineChallenge
  ```

- Install Dependencies:

  ```bash
  composer install
  ```

- Set Up Environment:

  ```bash
  cp .env.example .env
  php artisan key:generate
  ```

- Configure Database in `.env`:

  ```env
  DB_CONNECTION=mysql
  DB_HOST=127.0.0.1
  DB_PORT=3306
  DB_DATABASE=your_database_name
  DB_USERNAME=your_database_user
  DB_PASSWORD=your_database_password
  ```

- Run Migrations:

  ```bash
  php artisan migrate
  ```

- Run the Application:

  ```bash
  php artisan serve
  ```

- Run Tests:

  ```bash
  php artisan test
  ```
