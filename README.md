# Salon Booking Calendar (Laravel)

A salon booking system built using **Laravel 12** and **FullCalendar** that allows customers to view and book available time slots based on beautician availability.

---

## 🚀 Features

- Weekly / Monthly / Daily / List calendar views
- 1-hour time slots (09:00 AM – 06:00 PM)
- 5 beauticians available per time slot
- Automatic slot availability calculation
- Friday marked as holiday
- Past dates and past time slots blocked
- Current day always visible
- Clean and user-friendly UI
- AJAX-based slot loading and booking

---

## 🛠️ Tech Stack

- **Backend:** Laravel 12 (PHP)
- **Frontend:** FullCalendar, Bootstrap 5, JavaScript
- **Database:** MySQL
- **AJAX:** jQuery / Fetch API

---

## 📅 Booking Logic

- Each time slot can have **maximum 5 bookings**
- Slot status:
  - 🟢 **Available** → shows remaining slots
  - 🔴 **Closed** → fully booked or not available
- Friday is treated as a **holiday**
- Only **current and upcoming** slots are displayed

---

## 🖼️ Screenshots

> Screenshots demonstrating different calendar views and booking behavior.

### 📆 Weekly View
Shows available and closed slots with clear color indication.

![Weekly View](screenshots/weekly.png)


---

### 🔴 Fully Booked Slot
Closed slots are highlighted in red and cannot be booked.

![Fully Booked Slot](screenshots/fully-booked.png)

---

## ⚙️ Installation & Setup

```bash
# Clone repository
git clone https://github.com/YOUR_USERNAME/saloon-booking.git

# Go to project folder
cd saloon-booking

# Install dependencies
composer install

# Create environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Configure database in .env
# DB_DATABASE, DB_USERNAME, DB_PASSWORD

# Run migrations
php artisan migrate

# Start server
php artisan serve
