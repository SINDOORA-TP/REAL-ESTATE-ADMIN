# Magaram Real Estate — Executive Admin Suite

The dedicated administrative and property portfolio management portal for **Magaram Real Estate**.

---

## 1. Features

- **Executive Authentication:**
  - Secure login using `password_hash()` and `password_verify()`.
  - Session regeneration on login to prevent fixation.
  - Session-based brute force rate limiting and CSRF protection.
- **Landmark Properties CRUD (`/properties`):**
  - Add, edit, list, and delete luxury listings.
  - Multi-image upload with client MIME inspection (`JPEG`, `PNG`, `WebP`).
  - Cover photo selection and dynamic thumbnail gallery management.
  - Amenities tagging (Infinity Pool, Private Elevator, Smart Home Automation, Italian Marble, etc.).
  - Availability status (`Available`, `Upcoming`, `Sold`) and featured toggle.
- **Lead Pipeline & Inquiries (`/enquiries.php`):**
  - Track private viewing requests, brochure downloads, and general consultations.
  - Update lead status (`New`, `Contacted`, `Completed`) with internal executive notes.
  - One-click **Export to CSV**.
- **Client Testimonial Registry (`/testimonials.php`):**
  - Record and manage client reviews.
  - Public display approval controls (only real, approved testimonials are surfaced).
  - Explicit sample data flagging.
- **Architectural Gallery (`/gallery.php`):**
  - Upload high-resolution photographs with category tags (Exterior, Interior, Landscape, Amenities).
- **Brand & Content Controls (`/settings.php`):**
  - Edit office address, concierge phone, email, and Google Maps embed URL.
  - Toggle and customize the 4 homepage stats counters.

---

## 2. Credentials & Setup

- **Default Admin Credentials:**
  - **Username:** `admin`
  - **Password:** `admin123`
- **Database Schema:**
  - Schema definition is provided in `database.sql`.
  - Connects to MySQL/MariaDB database `magaram_db`.
  - Credentials can be configured in `includes/config.php` or `.env`.

---

## 3. Running Locally

```powershell
php -S localhost:8001
```
Open `http://localhost:8001` in your browser.

