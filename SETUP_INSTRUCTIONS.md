# SUAS Setup Instructions - Smart Unit Attendance

## ✅ Step 1: Supabase Database Setup (5 minutes)

### Run the Master Schema

1. Go to your Supabase project: https://supabase.com/dashboard/project/lpxyqipihklpaxxfourw

2. Click on **SQL Editor** in the left sidebar

3. Click **New query**

4. Open the file: `supabase/master_schema.sql` (from your HLSUAS folder)

5. Copy ALL the content and paste it into the SQL Editor

6. Click **Run** or press `Ctrl+Enter`

7. You should see: ✅ "Success. No rows returned"

### Verify Tables Created

1. Click on **Table Editor** in the left sidebar
2. You should see two tables:
   - ✅ `super_admins`
   - ✅ `institutions`

---

## ✅ Step 2: Test Local Connection (2 minutes)

### Start the Application

```bash
# Open Command Prompt in your HLSUAS folder
cd c:\xampp\htdocs\HLSUAS

# Start PHP server
php -S localhost:8000
```

### Access the Application

Open your browser and go to: **http://localhost:8000**

You should see the SUAS welcome screen!

---

## ✅ Step 3: Register Your First Institution

### Login as Super Admin

1. Click **"Super Admin"** or go to: http://localhost:8000/super_admin_login.php
2. **Username:** `superadmin`
3. **Password:** `super123`
4. Click **Login**

### Create Institution

1. Enter Institution Name: e.g., **"Hansen Technical Institute"**
2. Enter Institution Code: e.g., **"HTI001"**
3. Click **"Create Database & Register"**

✅ You should see: "Institution registered successfully!"

---

## ✅ Step 4: Test Institution Login

### Select Institution

1. Go back to home: http://localhost:8000/
2. Click **"Select Institution"**
3. Enter your institution code: **HTI001** (or whatever you used)
4. Click **Continue**

### Test Admin Login

1. Click **"Admin Login"**
2. **Username:** `admin`
3. **Password:** `admin123`
4. Click **Login**

✅ You should see the Admin Dashboard!

---

## 🔐 Default Credentials

### Super Admin
- **Username:** `superadmin`
- **Password:** `super123`

### Institution Admin
- **Username:** `admin`
- **Password:** `admin123`

### Trainers
- **Username:** `john` / **Password:** `john123`
- **Username:** `mary` / **Password:** `mary123`

### Students
- **Admission:** `E001` / **Password:** `123456`
- **Admission:** `E002` / **Password:** `123456`

---

## ⚠️ Important Security Steps

### Change Default Passwords!

1. **Super Admin Password:**
   - Login as superadmin
   - Go to profile/settings
   - Change password immediately

2. **Institution Admin Password:**
   - Login as admin
   - Change password in profile

---

## 🚀 Deploy to Render (Optional - For Production)

### Push to GitHub

```bash
# In your HLSUAS folder
git init
git add .
git commit -m "Initial SUAS deployment"

# Create repository on GitHub, then:
git remote add origin https://github.com/YOUR_USERNAME/suas.git
git branch -M main
git push -u origin main
```

### Deploy to Render

1. Go to https://render.com
2. Sign in with GitHub
3. Click **New** → **Web Service**
4. Select your `suas` repository
5. Configure:
   - **Name:** `suas-app`
   - **Environment:** PHP
   - **Build Command:** `mkdir -p storage/logs storage/sessions storage/cache && chmod -R 777 storage/`
   - **Start Command:** `php -S 0.0.0.0:$PORT -t public`
6. Add environment variables (same as your `.env` file):
   ```
   USE_SUPABASE=true
   SUPABASE_DB_HOST=db.lpxyqipihklpaxxfourw.supabase.co
   SUPABASE_DB_PORT=5432
   SUPABASE_DB_USER=postgres
   SUPABASE_DB_PASS=HqcUpqT8IV6FGkve
   SUPABASE_MASTER_DB_NAME=postgres
   APP_ENV=production
   DEBUG=false
   ```
7. Click **Create Web Service**

---

## 🐛 Troubleshooting

### "Database connection failed"
- ✅ Check `.env` file exists in `c:\xampp\htdocs\HLSUAS\.env`
- ✅ Verify Supabase credentials are correct
- ✅ Ensure master_schema.sql was executed in Supabase

### "Table doesn't exist"
- ✅ Run the `supabase/master_schema.sql` in Supabase SQL Editor
- ✅ Check Table Editor to verify tables were created

### "Institution code not found"
- ✅ Make sure you registered the institution via Super Admin
- ✅ Check institution is active in Supabase `institutions` table

### Application won't start
```bash
# Check PHP version (need 8.0+)
php -v

# Check if port 8000 is available
netstat -ano | findstr :8000
```

---

## 📞 Need Help?

1. **Run Setup Check:** http://localhost:8000/setup_check.php
2. **Check Logs:** Look in `storage/logs/` folder
3. **Review Guide:** See `DEPLOYMENT_GUIDE.md` for detailed instructions

---

## ✅ Quick Checklist

- [ ] `.env` file created with Supabase credentials
- [ ] `supabase/master_schema.sql` executed in Supabase
- [ ] Tables `super_admins` and `institutions` exist
- [ ] Application starts at http://localhost:8000
- [ ] Super Admin login works
- [ ] Institution created successfully
- [ ] Admin/Trainer/Student logins work
- [ ] Default passwords changed

---

**Setup Complete! 🎉**

Your SUAS is now connected to Supabase and ready to use!
