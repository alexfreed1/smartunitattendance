# 🚀 SUAS Render Deployment - Quick Checklist

## ✅ Pre-Deployment Checklist

### 1. GitHub Setup
- [ ] Git initialized (`git init`)
- [ ] All files added (`git add .`)
- [ ] Initial commit created (`git commit -m "Initial"`)
- [ ] GitHub repository created
- [ ] Code pushed to GitHub (`git push -u origin main`)

### 2. Render Account
- [ ] Render account created at https://render.com
- [ ] GitHub account connected to Render

### 3. Supabase Verification
- [ ] Supabase project active: `smartunitattendance`
- [ ] Master schema executed in SQL Editor
- [ ] Tables exist: `super_admins`, `institutions`
- [ ] Connection details saved:
  - Host: `db.lpxyqipihklpaxxfourw.supabase.co`
  - Password: `HqcUpqT8IV6FGkve`

---

## 📦 Deployment Steps

### Step 1: Push to GitHub
```bash
cd c:\xampp\htdocs\HLSUAS
git init
git add .
git commit -m "Initial SUAS deployment"
git branch -M main
# Create repo on GitHub first, then:
git remote add origin https://github.com/YOUR_USERNAME/suas.git
git push -u origin main
```

### Step 2: Deploy to Render
- [ ] Go to https://dashboard.render.com
- [ ] Click **New** → **Web Service**
- [ ] Select your repository
- [ ] Configure:
  - [ ] Name: `suas-app`
  - [ ] Region: Oregon
  - [ ] Branch: `main`
  - [ ] Runtime: PHP
  - [ ] Build Command: `mkdir -p storage/logs storage/sessions storage/cache && chmod -R 777 storage/`
  - [ ] Start Command: `php -S 0.0.0.0:$PORT -t public`
  - [ ] Instance: Free

### Step 3: Add Environment Variables
In Render dashboard → Environment tab, add:
```
USE_SUPABASE=true
SUPABASE_DB_HOST=db.lpxyqipihklpaxxfourw.supabase.co
SUPABASE_DB_PORT=5432
SUPABASE_DB_USER=postgres
SUPABASE_DB_PASS=HqcUpqT8IV6FGkve
SUPABASE_MASTER_DB_NAME=postgres
APP_ENV=production
DEBUG=false
SESSION_SECURE=true
CSRF_ENABLED=true
```

### Step 4: Deploy
- [ ] Click **Create Web Service**
- [ ] Wait for deployment (3-5 minutes)
- [ ] Check Logs tab for "Server listening on port"
- [ ] Click your Render URL

---

## ✅ Post-Deployment Verification

### Test Connection
- [ ] Open: `https://YOUR-APP.onrender.com/test_connection.php`
- [ ] All green checkmarks visible

### Test Logins
- [ ] Super Admin: `superadmin` / `super123`
- [ ] Register first institution
- [ ] Institution selection works
- [ ] Admin login: `admin` / `admin123`
- [ ] Trainer login: `john` / `john123`
- [ ] Student login: `E001` / `123456`

### Security
- [ ] Change Super Admin password
- [ ] Change Institution Admin password
- [ ] Update `.env` with new passwords if needed

---

## 🎯 Success Criteria

✅ Application accessible at Render URL
✅ Test connection shows all green
✅ All login types work
✅ Institution registration works
✅ Attendance marking works
✅ PDF downloads work
✅ No errors in logs

---

## 🐛 Quick Troubleshooting

| Issue | Solution |
|-------|----------|
| Build failed | Check Logs tab, verify render.yaml exists |
| Database connection failed | Verify environment variables, check Supabase is active |
| 502 Bad Gateway | Wait 2-3 min (cold start on free tier) |
| Tables not found | Run supabase/master_schema.sql in Supabase |

---

## 📊 Your Configuration

**Supabase:**
- Project: `smartunitattendance`
- Host: `db.lpxyqipihklpaxxfourw.supabase.co`
- Database: `postgres`

**Render:**
- Service: `suas-app`
- Tier: Free
- Region: Oregon

---

## 📞 Resources

- Full Guide: `DEPLOY_TO_RENDER.md`
- Render Docs: https://render.com/docs
- Supabase Docs: https://supabase.com/docs

---

**Ready? Start with Step 1!** 🚀
