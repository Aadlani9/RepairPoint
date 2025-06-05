Certainly! Here's the English version of the `README.md` file for your **RepairPoint** web app:

---

```markdown
# 📱 RepairPoint

**RepairPoint** is a web-based mobile repair management system designed for repair shops. It allows you to register customer repairs, print POS tickets, manage ongoing and completed repairs, and organize your team in a simple and efficient environment.

---

## ⚙️ General Information

- 📌 **Project Name:** RepairPoint
- 🏷️ **Category:** Web Application (PHP + MySQL)
- 🧑‍💼 **Target Users:** Mobile and electronics repair shops
- 📲 **Compatible With:** Mobile phones, tablets, and desktop browsers

---

## 🚀 Key Features

- 📝 Register customer repairs and generate thermal tickets
- 🛠️ Manage active and completed repair tasks
- 🔍 Search customers by name, phone, or repair reference
- 👥 Multi-user support with login system (technicians, managers)
- ⚙️ Add brands and models per shop
- 🧾 Automatically generate printable delivery receipts

---

## 🧱 Project Structure

```

RepairPoint/
├── config/                # Configuration files
├── includes/              # Shared components (header, footer, auth)
├── assets/                # CSS, JS, images
├── pages/                 # App pages
├── api/                   # Backend APIs
├── sql/                   # Database SQL dump
├── index.php              # Entry point
└── logout.php             # Logout handler

```

---

## 🛠️ Technologies Used

| Component      | Technology         |
|----------------|--------------------|
| Server         | PHP 8.x            |
| Database       | MySQL / MariaDB    |
| Front-end      | CSS3 (Flex/Grid)   |
| Print Layouts  | CSS Print (POS 80mm, 58mm) |
| Interactions   | Vanilla JavaScript |
| Auth Sessions  | PHP Sessions       |

---

## 🗄️ Database Overview

### Main Tables:

- `users`: App users (name, email, hashed password, role)
- `shops`: Shop information (name, address, contact)
- `repairs`: Repair records for each customer
- `brands`: Phone brands (Apple, Samsung, etc.)
- `models`: Phone models linked to brands
- `issues`: Predefined common issues for selection

Schema is available in `sql/database.sql`.

---

## ✅ Getting Started

1. Create a new MySQL database
2. Import the `sql/database.sql` file
3. Configure DB credentials in `config/database.php`
4. Open `index.php` in your browser

---

## 🔐 User Roles

The first admin account can be created via registration or seeded manually. Technicians log in with their credentials and are limited to their permissions.

---

## 🧾 Notes

- Works locally with XAMPP, MAMP, or any PHP environment
- POS ticket printing supported (58mm / 80mm paper)
- Multi-user: each shop can have multiple team members with separate logins

---

## 📧 Support

For inquiries or technical support:
- 📩 contact@electromti.com  
- 🌐 https://electromti.com

---

## © License

© 2025 RepairPoint. All rights reserved.
```

---

Would you like a downloadable version as a `.md` file, or shall I include instructions for deploying to a live server (like cPanel or VPS)?
