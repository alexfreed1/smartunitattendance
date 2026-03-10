# SUAS - Fix PostgreSQL Driver Issue

## ❌ Error: "could not find driver"

This means PHP doesn't have the PostgreSQL PDO extension enabled.

---

## ✅ Solution 1: Enable PDO PostgreSQL in XAMPP (Recommended for Local Testing)

### Step 1: Find php.ini

1. Go to your XAMPP installation folder (usually `C:\xampp\php\`)
2. Open `php.ini` in Notepad or any text editor

### Step 2: Enable the Extension

1. Press `Ctrl+F` and search for: `pdo_pgsql`
2. Find this line:
   ```ini
   ;extension=pdo_pgsql
   ```
3. Remove the semicolon `;` to uncomment it:
   ```ini
   extension=pdo_pgsql
   ```
4. Also search for `pgsql` and enable it too:
   ```ini
   extension=pgsql
   ```

### Step 3: Restart Apache

1. Open XAMPP Control Panel
2. Click **Stop** on Apache (if running)
3. Click **Start** on Apache

### Step 4: Verify

1. Create a file `c:\xampp\htdocs\HLSUAS\check_ext.php` with:
   ```php
   <?php
   echo "PDO Drivers: " . implode(', ', PDO::getAvailableDrivers());
   ?>
   ```
2. Open: `http://localhost/HLSUAS/check_ext.php`
3. You should see `pgsql` in the list

---

## ✅ Solution 2: Use PHP Built-in Server with Extension

If you're using the `php -S` command, you need to enable the extension in your CLI php.ini:

### For Windows PHP CLI:

1. Find your PHP installation folder (e.g., `C:\PHP\` or check `where php` in Command Prompt)
2. Open `php.ini` (or create from `php.ini-development`)
3. Enable these lines:
   ```ini
   extension=pdo_pgsql
   extension=pgsql
   ```
4. Restart your terminal and run:
   ```bash
   php -S localhost:8000
   ```

---

## ✅ Solution 3: Quick Fix - Use MySQL Locally (Easiest!)

Since you're using XAMPP, you can test locally with MySQL instead of Supabase:

### Edit `.env` file:

```env
# Change this to false for local MySQL testing
USE_SUPABASE=false

# MySQL settings (XAMPP defaults)
DB_HOST=127.0.0.1
DB_USER=root
DB_PASS=
MASTER_DB_NAME=hlsuas_master
```

### Then:

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Go to **SQL** tab
3. Run: `init_master_db.sql` content
4. Start using the app locally!

---

## 🔍 Check Which PHP You're Using

Run this in Command Prompt:

```bash
where php
php --ini
```

This shows:
- PHP executable location
- Which php.ini file is being used

---

## 📋 Quick Test

After enabling the extension, run:

```bash
php -m | findstr pgsql
```

You should see:
```
pdo_pgsql
pgsql
```

---

## 🎯 Recommended Approach

**For Local Development:** Use MySQL (Solution 3)
- Faster
- No internet required
- Easy to debug

**For Production:** Use Supabase
- Deploy to Render
- Connect to your Supabase database

---

## Need Help?

Run this diagnostic script: `http://localhost/HLSUAS/test_connection.php`

It will show which extensions are loaded and what's missing.
