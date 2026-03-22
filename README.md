# EveryWear

![EveryWear Logo](images/logo.png)

An e-commerce clothing website built as a university group project.

## Website Link
Click [here](https://cs2team44.cs2410-web01pvm.aston.ac.uk/) to view the website.

## Description
EveryWear is designed as a fully functional online clothing store, demonstrating the integration of frontend, backend, and database systems. Users can: <br>
Browse products by category <br>
View product details <br>
Create and manage accounts <br>
Add items to a shopping cart <br>
Place orders <br>
View order history <br>
Contact support via a form <br>

## Features
**Browsing Products:** Click on the different items like top, bottom to view all available products on the website <br>
Search by category or product name (future enhancement) <br>
See product details such as price, description, and availability <br>
**Account Management:** Signup: Users can create an account with email, username, and password <br>
Login/Logout: Secure login system with session handling <br>
Profile: View personal account information (currently basic functionality) <br>
**Shopping Cart:** Add or remove products from the cart <br>
View total price and quantity of selected items <br>
Update quantities before checkout <br>
**Checkout & Orders:** Place orders using the shopping cart <br>
Orders are stored in the database for tracking <br>
View past orders in orders.php <br>
Each order shows items, quantity, total price, and order date <br>
**Contact Form:** Users can send inquiries or feedback through a simple form <br>
Submissions are sent to the database for review (or email in a real implementation) <br>
**Responsive Design:** Works on desktop and mobile devices <br>
Pages adjust layout to fit different screen sizes <br>

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
## Usage Guide
**Browsing & Selecting Products:** 
Open index.php to see all products. <br>
Click on a product to view details. <br>
Click Add to Cart to save it for checkout. <br>
**Managing Your Account:**
Signup: Navigate to create-account.php, fill out the form, and submit. <br>
Login: Navigate to login.php, enter credentials, and submit. <br>
Logout: Use the logout link to safely end your session. <br>
**Using the Cart:**
View the cart at cart.php. <br>
Adjust quantities or remove items as needed. <br>
Click Checkout to proceed to order placement. <br>
**Placing Orders:**
After checkout, your order is saved in the database. <br>
Access orders.php to see your order history and details. <br>
**Contact Form:**
Navigate to Contact Us.html <br>
Fill in your name, email, and message <br>
Submit the form to send your inquiry <br>

## Error Handling & Tips
**Invalid Login:** Shows an error if credentials don’t match
**Empty Cart:** Checkout is disabled if the cart has no items
**Database Errors:** Ensure .env is correctly configured; errors will appear in PHP logs

## Contributing
Fork the repository <br>
Create a new branch (git checkout -b feature/YourFeature) <br>
Commit your changes (git commit -m 'Add some feature') <br>
Push to the branch (git push origin feature/YourFeature) <br>
Open a Pull Request

## Notes

- Never commit a real `.env` file. Use `.env.example` as a template only.
- This is a student project for university purposes.

## Team Members
### Frontend
Shihad Hussain 240133588
Jaimin Nish 240389923
Maryam Khan Yaqoob 240153760
Sukanya Badoghu 240324810
### Backend
Omarion Cohen 230112438
Ammar Salem 230145090
