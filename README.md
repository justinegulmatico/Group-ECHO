## Group-ECHO / Paluwagan

Simple student-level Paluwagan (rotating savings group) web app.

### What was done
- Consolidated duplicate cycle/payout logic into clear, short functions.
- Fixed invite code join (now does exact match on the real 6-char code).
- Rotation always includes the creator (ordered by joined_at).
- Group creation goes through admin approval (pending → active).
- One simple "Initialize Schedule" then "Release Payout + auto next cycle" flow.
- All code uses the columns from trustfund_db.sql (contribution_amount, cycle_length, created_by, is_active + status, etc).
- Removed magic string hacks and abandoned process_*.php paths.

### Core files (easy to follow for students)
- `Paluwagan/index.php` – login + register (pending status)
- `Paluwagan/back-end/php/my_groups.php` – create group + join with code
- `Paluwagan/back-end/php/group_details.php` – view group + init cycle + pay + release payout
- `Paluwagan/back-end/php/admin.php` – approve users + approve groups + manual payout release

### How the rotation works (student simple)
1. Creator clicks "Initialize Schedule" → Cycle 1 is created.
2. Members pay their contribution for the cycle.
3. Creator clicks "Release Payout" → money goes to the person who is next in join-order list, next cycle is automatically created with new contribution rows.
4. Repeats until everyone has received once.

Run with XAMPP + import the SQL. Default DB name: trustfund_db

Made simple on purpose.