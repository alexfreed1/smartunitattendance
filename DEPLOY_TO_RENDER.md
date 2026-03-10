# 🚀 Deploy SUAS to Render with Supabase

## Complete Step-by-Step Guide

---

## ✅ Prerequisites

- ✅ GitHub account
- ✅ Render account (free tier available)
- ✅ Supabase project (already created: `smartunitattendance`)

---

## Step 1: Push Your Code to GitHub

### Open Command Prompt in your HLSUAS folder:

```bash
cd c:\xampp\htdocs\HLSUAS
```

### Initialize Git Repository:

```bash
git init
git add .
git commit -m "Initial SUAS deployment with Supabase"
```

### Create GitHub Repository:

1. Go to: https://github.com/new
2. Repository name: `suas` (or your preferred name)
3. Visibility: **Public** or **Private** (your choice)
4. **Do NOT** initialize with README
5. Click **Create repository**

### Push to GitHub:

```bash
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/suas.git
git push -u origin main
```

**Replace `YOUR_USERNAME` with your actual GitHub username!**

---

## Step 2: Create Render Account

1. Go to: https://render.com
2. Click **Get Started for Free**
3. Sign up with **GitHub** (recommended) or email
4. Complete account setup

---

## Step 3: Deploy to Render

### Option A: Using render.yaml (Automatic)

1. Go to: https://dashboard.render.com
2. Click **New** → **Blueprint**
3. Connect your GitHub account (if not already connected)
4. Select your `suas` repository
5. Render will detect `render.yaml` configuration
6. Click **Apply**

### Option B: Manual Setup

1. Go to: https://dashboard.render.com
2. Click **New** → **Web Service**
3. Connect your repository
4. Configure:

**Basic Settings:**
```
Name: suas-app
Region: Oregon (or closest to you)
Branch: main
Root Directory: (leave blank)
Runtime: PHP
```

**Build Settings:**
```
Build Command:
mkdir -p storage/logs storage/sessions storage/cache && chmod -R 777 storage/

Start Command:
php -S 0.0.0.0:$PORT -t public
```

**Instance Type:**
```
Select: Free tier
```

5. Click **Advanced** and add environment variables (next step)

---

## Step 4: Add Environment Variables

In Render dashboard, go to your service → **Environment** tab

Add these variables:

```
USE_SUPABASE=true
SUPABASE_DB_HOST=db.lpxyqipihklpaxxfourw.supabase.co
SUPABASE_DB_PORT=5432
SUPABASE_DB_USER=postgres
SUPABASE_DB_PASS=HqcUpqT8IV6FGkve
SUPABASE_MASTER_DB_NAME=postgres
APP_ENV=production
DEBUG=false
SESSION_SECURE=false
CSRF_ENABLED=true
```

Click **Save Changes**

---

## Step 5: Deploy!

1. Go to **Logs** tab in Render dashboard
2. Watch the deployment progress (takes 3-5 minutes)
3. Wait for: "Server listening on port"
4. Click your Render URL (e.g., `https://suas-app.onrender.com`)

---

## Step 6: Verify Supabase Connection

### Access Test Page:

```
https://YOUR-APP.onrender.com/test_connection.php
```

You should see all green checkmarks:
- ✅ .env File Found (from environment variables)
- ✅ Supabase Mode Enabled
- ✅ Supabase Host Configured
- ✅ Database Password Set
- ✅ Supabase Connection Successful!
- ✅ Database Tables Found

---

## Step 7: Start Using SUAS

### 1. Login as Super Admin:

```
URL: https://YOUR-APP.onrender.com/super_admin_login.php
Username: superadmin
Password: super123
```

### 2. Register Your Institution:

1. Enter Institution Name (e.g., "Hansen Technical Institute")
2. Enter Institution Code (e.g., "HTI001")
3. Click "Create Database & Register"

### 3. Test Institution Login:

1. Go back to home
2. Click "Select Institution"
3. Enter your institution code
4. Test Admin/Trainer/Student logins

---

## 🔐 Default Credentials

| Role | Username | Password | Change First? |
|------|----------|----------|---------------|
| Super Admin | `superadmin` | `super123` | ⚠️ YES |
| Institution Admin | `admin` | `admin123` | ⚠️ YES |
| Trainer | `john` | `john123` | Recommended |
| Student | `E001` | `123456` | Optional |

---

## 🎯 Quick Checklist

- [ ] Code pushed to GitHub
- [ ] Render account created
- [ ] Web Service created on Render
- [ ] Environment variables added
- [ ] Deployment successful (green checkmark)
- [ ] Test connection page shows all green
- [ ] Super Admin login works
- [ ] Institution registered
- [ ] All login types tested
- [ ] Default passwords changed

---

## 🐛 Troubleshooting

### "Build Failed"

**Check Logs:**
1. Go to Render dashboard → Your service → Logs
2. Look for error messages
3. Common fixes:
   - Ensure `render.yaml` exists
   - Check build command syntax
   - Verify all files committed to Git

### "Database Connection Failed"

**Check:**
1. Environment variables are correct
2. Supabase project is active
3. Master schema executed in Supabase SQL Editor
4. Supabase allows connections (Settings → Database → Connections)

### "502 Bad Gateway"

**Wait 2-3 minutes** - Render free tier has cold starts after inactivity.

### "Table doesn't exist"

**Run Master Schema:**
1. Go to Supabase Dashboard → SQL Editor
2. Paste content from `supabase/master_schema.sql`
3. Click Run

---

## 💰 Cost

**Free Tier:**
- Render: Free (750 hours/month)
- Supabase: Free (500MB database, unlimited users)
- **Total: $0/month**

**Production (when ready):**
- Render Standard: $7/month
- Supabase Pro: $25/month
- **Total: ~$32/month**

---

## 📊 Your Supabase Details

```
Project: smartunitattendance
Host: db.lpxyqipihklpaxxfourw.supabase.co
Port: 5432
Database: postgres
User: postgres
Password: HqcUpqT8IV6FGkve
```

---

## 🎉 Success!

Once deployed, your application will be accessible 24/7 at your Render URL.

**Example URLs:**
- Home: `https://suas-app.onrender.com/`
- Super Admin: `https://suas-app.onrender.com/super_admin_login.php`
- Test Connection: `https://suas-app.onrender.com/test_connection.php`

---

## 📞 Need Help?

- Render Docs: https://render.com/docs
- Supabase Docs: https://supabase.com/docs
- Check Logs: Render Dashboard → Your Service → Logs

---

**Ready to deploy? Start with Step 1!** 🚀
