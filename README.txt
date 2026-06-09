Apr 13, 2026
- added erd blueprint

May 18, 2026
- added whole front end

May 19, 2026
- added create group front end

May 23, 2026
- connecting some parts

May 24, 2026
- added trustfund back-end

May 27, 2026
- Added precise Date and Time tracking to all Admin tables.
- Integrated timestamp display into the Group Details modal.
- Standardized date formatting across the entire backend.

May 30, 2026
- Replaced "redirect" pages with clean, modern status popups (modals).
- Automated status alerts for Pending, Denied, Suspended, and Success states.
- Implemented automatic CSS versioning (filemtime) for instant design updates.
- Secured login: Suspended accounts are now blocked at the authentication level.

# Paluwagan - Group ECHO

A web app for managing paluwagan / trust fund groups. Members can create or join groups, pay contributions on time, and get payouts. There's also an admin side for approvals and an OLAP analytics part (for our Advanced Database Systems class).

Basically a full OLTP + OLAP project.

## What we used
- PHP (mix of mysqli and PDO)
- MySQL / MariaDB (XAMPP)
- Plain HTML, CSS, and some JS
- Separate databases: `trustfund_db` (main app) and `trustfund_olap` (analytics)

## How to set it up (XAMPP)

1. Put the whole `Group-ECHO` folder inside your XAMPP `htdocs` folder.

2. Open XAMPP Control Panel and start **Apache** and **MySQL**.

3. Open phpMyAdmin: go to `http://localhost/phpmyadmin`

4. Import the two SQL files (use the Import tab):

   - `Paluwagan/back-end/sql/oltp/trustfund_db.sql` → creates the main `trustfund_db`
   - `Paluwagan/back-end/sql/olap/olap_full_setup.sql` → creates `trustfund_olap` + dimensions, fact table, views, and some demo queries

   Just copy-paste everything or select the file and hit Go. The olap one has comments at the top if you get stuck.

5. Open the app:

   `http://localhost/Group-ECHO/Paluwagan/`

6. Register a new account on the landing page (login/register tabs).

   **Getting admin access:** New accounts start as regular members. To test the admin panel, go to phpMyAdmin → `trustfund_db` → `users` table → edit your row and set `role` to `admin`. Log out and log back in.

## Default database settings
- Host: localhost
- User: root
- Password: (empty)
- Main DB: trustfund_db
- OLAP DB: trustfund_olap

If your MySQL password is not blank, change it in:
- `Paluwagan/back-end/db.php`
- `Paluwagan/back-end/olap_db.php`

## Notes
- Uploaded receipts and verification docs go into `Paluwagan/assets/uploads/`
- The OLAP stuff needs the ETL/sync scripts to move data over (there's buttons or pages for that in the admin analytics side)
- File uploads accept images and PDFs

The real documentation / extra explanations are inside the SQL files themselves (lots of comments). The old day-by-day notes are still in the old `README.txt`.

Made for school project. Works on XAMPP with MariaDB 10.4+ (the default one).

## Demo / Presentation Data (3 years of realistic activity)

For demos and presentations you want the site to look lived-in for ~3 years:

1. Import the two schema files first:
   - `sql/trustfund_oltp.sql`
   - `sql/trustfund_olap.sql` (for the analytics side)

2. Run the seeder (produces 120 users, ~28 groups — mostly public, 1000s of contributions/payouts/transactions/wallet rows with dates from 2023-2026):
   - Easiest: open in browser while XAMPP is running
     `http://localhost/Group-ECHO/sql/seed_demo_data.php?reset=1`
   - Or via CLI: `C:\xampp\php\php.exe sql\seed_demo_data.php`

3. All seeded accounts use the password hash: `$2y$10$GKfh18Ysah5fH9O7w2o0puPVdUo3E7hREQAR52DgPuPpLP8tD891u`

4. After seeding the OLTP data, log in as an admin and run the ETL/OLAP sync from the Admin Analytics page so the charts, ROLLUPs, and fact tables show rich historical data.

5. Explore:
   - Admin → Users, Groups, Transactions, Verifications, Analytics
   - Regular member accounts: My Groups, public group discovery, Group Details (full history + who got paid when), Payments, Wallet activity

To start fresh: run the seeder again with ?reset=1 (or set $RESET_FIRST).