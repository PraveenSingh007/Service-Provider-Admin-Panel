# Service Provider Admin Panel

A comprehensive, role-based PHP Admin Panel built for managing service providers, employees, attendance, salary generation, quotations, invoices, daily expenses, and company profile.

## 🛠️ Features

- 👑 **Role-Based Access Control (RBAC)**: Support for Super Administrator, Administrator, Manager, Office Incharge, Office Staff, and Site Engineer.
- 🏢 **Company Profile**: Dynamic header & footer details on Quotations and Invoices (Restricted to Super Admin).
- 👥 **Employee Management**: Manage staff details including Aadhaar Card, PAN Card, roles, salary, and active status.
- 📅 **Attendance & Salary Tracking**: Self and team attendance marking with automated salary calculations.
- 💰 **Daily Expenses Module**: Record operational expenses with employee assignment and ownership-based role access.
- 📑 **Quotations & Invoices**: Generate, track, and print detailed customer quotations and invoices.

## 💻 Tech Stack

- **PHP 8.x** (Object-Oriented, MVC pattern architecture)
- **MySQLi** database connection manager
- **Bootstrap 5** & **Sneat Admin Template**
- **JavaScript (Vanilla & AJAX)**

## 🚀 Getting Started

1. Clone the repository into your web server directory (`htdocs` for XAMPP):
   ```bash
   git clone https://github.com/PraveenSingh007/Service-Provider-Admin-Panel.git
   ```
2. Import database schema files (`employees.sql`, `company_profile.sql`, `daily_expenses.sql`).
3. Access the panel in your browser at `http://localhost/sneat/html/index.php`.
