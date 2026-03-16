# EveryWear

An e-commerce clothing website built as a university group project.

## Description

EveryWear is an online clothing store where users can browse products, create accounts, log in, add items to a cart, and place orders.

## Tech Stack

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL (via PDO)
- **Other:** Java (utility/test code)

## Setup Instructions

1. **Clone the repository**
   ```bash
   git clone https://github.com/ccurrupt/Team-44.git
   cd Team-44
   ```

2. **Configure environment variables**
   ```bash
   cp .env.example .env
   ```
   Open `.env` and fill in your database credentials:
   ```
   DB_HOST=localhost
   DB_NAME=your_database_name
   DB_USER=your_database_user
   DB_PASS=your_database_password
   ```

3. **Run on a PHP server**

   Use a local PHP development server (e.g. XAMPP, WAMP, or the built-in PHP server):
   ```bash
   php -S localhost:8000 -t Database/
   ```
   Then open `http://localhost:8000` in your browser.

## Project Structure

```
Team-44/
├── Database/               # PHP backend files (login, signup, cart, checkout, etc.)
│   ├── dbconfig.php        # PDO database connection (reads from environment variables)
│   ├── db.php              # Alternative PDO connection file
│   ├── index.php           # Home page
│   ├── login.php           # Login page
│   ├── create-account.php  # Registration page
│   ├── process-login.php   # Login form handler
│   ├── process-signup.php  # Signup form handler
│   ├── cart.php            # Shopping cart
│   ├── checkout.php        # Checkout handler
│   ├── orders.php          # Order history
│   └── ...
├── Contact Us.html         # Contact form page
├── Place_order.html        # Order placement page
├── Math.java               # Java utility class (add function + tests)
├── Test.java               # Java test class
├── .env.example            # Example environment variable file
└── README.md               # This file
```

## Notes

- Never commit a real `.env` file. Use `.env.example` as a template only.
- This is a student project for university purposes.
