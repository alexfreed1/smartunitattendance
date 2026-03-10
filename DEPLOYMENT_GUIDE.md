# SUAS Deployment Guide

Complete guide for deploying SUAS to production with Supabase and Render.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Step-by-Step Deployment](#step-by-step-deployment)
3. [Post-Deployment Configuration](#post-deployment-configuration)
4. [Monitoring & Maintenance](#monitoring--maintenance)
5. [Backup & Recovery](#backup--recovery)

---

## Prerequisites

### Required Accounts

- ✅ [GitHub](https://github.com) account
- ✅ [Supabase](https://supabase.com) account (free tier available)
- ✅ [Render](https://render.com) account (free tier available)

### Required Software (Local Development)

- PHP 8.0 or higher
- MySQL 5.7+ (for local development)
- Git
- Composer (optional)

---

## Step-by-Step Deployment

### Phase 1: Database Setup (Supabase)

#### Step 1.1: Create Supabase Project

1. Visit [supabase.com](https://supabase.com)
2. Click **"Start your project"** or **"New Project"**
3. Fill in:
   - **Project name:** `suas-production` (or your preferred name)
   - **Database password:** ⚠️ **Save this securely!**
   - **Region:** Choose closest to your target users
   - **Pricing plan:** Free tier (includes 500MB database, sufficient for thousands of records)
4. Click **"Create new project"**
5. Wait 2-3 minutes for provisioning

#### Step 1.2: Configure Database

1. In Supabase dashboard, click **"SQL Editor"** in left sidebar
2. Click **"New query"**
3. Open `supabase/master_schema.sql` from your project
4. Copy entire contents
5. Paste into SQL Editor
6. Click **"Run"** or press `Ctrl+Enter`
7. Verify success message: "Success. No rows returned"

#### Step 1.3: Get Connection Details

1. Go to **Settings** (gear icon) → **Database**
2. Under **Connection string**, select **URI** tab
3. Copy these values:
   ```
   Host: db.xxxxxxxxxxxxx.supabase.co
   Port: 5432
   Database: postgres
   User: postgres
   Password: [your password]
   ```

#### Step 1.4: Verify Tables

1. Go to **Table Editor** in left sidebar
2. You should see:
   - `super_admins`
   - `institutions`

✅ **Database setup complete!**

---

### Phase 2: GitHub Repository Setup

#### Step 2.1: Prepare Repository

```bash
# Navigate to project directory
cd c:\xampp\htdocs\HLSUAS

# Initialize Git repository
git init

# Add all files
git add .

# Create initial commit
git commit -m "Initial SUAS deployment with Supabase support"
```

#### Step 2.2: Create GitHub Repository

1. Go to [github.com/new](https://github.com/new)
2. Repository name: `suas` (or your preferred name)
3. Visibility: **Public** or **Private** (your choice)
4. **Do NOT** initialize with README (we already have one)
5. Click **"Create repository"**

#### Step 2.3: Push to GitHub

```bash
# Add remote repository (replace YOUR_USERNAME)
git remote add origin https://github.com/YOUR_USERNAME/suas.git

# Rename branch to main
git branch -M main

# Push to GitHub
git push -u origin main
```

✅ **GitHub repository ready!**

---

### Phase 3: Render Deployment

#### Step 3.1: Create Render Account

1. Visit [render.com](https://render.com)
2. Click **"Get Started for Free"**
3. Sign up with GitHub (recommended) or email

#### Step 3.2: Deploy Using Blueprint (Recommended)

1. Go to [dashboard.render.com](https://dashboard.render.com)
2. Click **"New"** → **"Blueprint"**
3. Connect your GitHub account (if not already connected)
4. Select your `suas` repository
5. Render will detect `render.yaml`
6. Review the configuration
7. Click **"Apply"**

#### Step 3.3: Configure Environment Variables

After Blueprint creates services:

1. Go to the created **Web Service**
2. Click **"Environment"** tab
3. Add these variables:

```
# Database (from Supabase)
USE_SUPABASE=true
SUPABASE_DB_HOST=db.xxxxx.supabase.co
SUPABASE_DB_PORT=5432
SUPABASE_DB_USER=postgres
SUPABASE_DB_PASS=your-database-password
SUPABASE_MASTER_DB_NAME=postgres

# Application
APP_ENV=production
APP_URL=https://suas-app.onrender.com
DEBUG=false
SESSION_SECURE=true
```

4. Click **"Save Changes"**

#### Step 3.4: Manual Deployment (Alternative)

If not using Blueprint:

1. Click **"New"** → **"Web Service"**
2. Connect repository
3. Configure:

**Basic Settings:**
- **Name:** `suas-app`
- **Region:** Oregon (or closest to users)
- **Branch:** `main`
- **Root Directory:** (leave blank)
- **Runtime:** PHP

**Build Settings:**
- **Build Command:**
  ```bash
  mkdir -p storage/logs storage/sessions storage/cache && chmod -R 777 storage/
  ```
- **Start Command:**
  ```bash
  php -S 0.0.0.0:$PORT -t public
  ```

**Instance Type:**
- **Free** tier (sufficient for development/small deployments)

4. Add environment variables (same as above)
5. Click **"Create Web Service"**

#### Step 3.5: Wait for Deployment

1. Deployment takes 3-5 minutes
2. Watch logs in **"Logs"** tab
3. Look for: "Server listening on port 10000"

✅ **Application deployed!**

---

### Phase 4: Post-Deployment Configuration

#### Step 4.1: Test Application

1. Get your Render URL from dashboard (e.g., `https://suas-app.onrender.com`)
2. Open in browser
3. You should see SUAS welcome screen

#### Step 4.2: Register Institution

1. Click **"Super Admin"** → **"Super Admin Login"**
2. Login: `superadmin` / `super123`
3. Register your first institution:
   - **Name:** Your institution name
   - **Code:** e.g., `HTI001`
4. Click **"Create Database & Register"**

⚠️ **Note:** With Supabase, institutions use PostgreSQL schemas instead of separate databases.

#### Step 4.3: Change Default Passwords

1. **Super Admin:** Change immediately after first login
2. **Institution Admin:** Change after institution setup
3. Navigate to profile/settings to change passwords

#### Step 4.4: Configure Custom Domain (Optional)

1. In Render dashboard, go to your service
2. Click **"Settings"** → **"Custom Domains"**
3. Click **"Add Custom Domain"**
4. Enter your domain (e.g., `suas.yourdomain.com`)
5. Update DNS records:
   - **Type:** CNAME
   - **Name:** suas (or @)
   - **Value:** suas-app.onrender.com
6. Wait for DNS propagation (up to 48 hours)
7. Enable **Auto-HTTPS** for SSL certificate

---

## Monitoring & Maintenance

### View Application Logs

**Render Dashboard:**
1. Go to your service
2. Click **"Logs"** tab
3. View real-time logs

**Common Log Messages:**
- ✅ "Server listening on port" - Application running
- ⚠️ "Database connection error" - Check credentials
- ❌ "Fatal error" - Check application code

### Database Monitoring

**Supabase Dashboard:**
1. Go to your project
2. **Settings** → **Database**
3. View connection count, database size

**Table Editor:**
- View records in each table
- Verify data integrity

### Performance Optimization

1. **Enable Caching:**
   ```env
   CACHE_DRIVER=file
   ```

2. **Database Indexes:** Already included in schema

3. **CDN:** Use Cloudflare for static assets

### Security Updates

1. **Monitor Dependencies:**
   ```bash
   composer audit
   ```

2. **Update PHP Packages:**
   ```bash
   composer update
   git add composer.lock
   git commit -m "Update dependencies"
   git push
   ```
   (Auto-deploys to Render)

---

## Backup & Recovery

### Automated Backups (Supabase)

Supabase automatically backs up databases:

- **Free tier:** 7-day point-in-time recovery
- **Pro tier:** 30-day backup retention

**Enable Backups:**
1. Supabase Dashboard → **Settings** → **Database**
2. Backups are enabled by default

### Manual Backup

**Export from Supabase:**
1. Go to **SQL Editor**
2. Run backup query for each table
3. Save results

**Automated Backup Script:**
```bash
# Add to cron job
pg_dump "postgresql://postgres:password@db.xxxxx.supabase.co:5432/postgres" > backup_$(date +%Y%m%d).sql
```

### Disaster Recovery

**Restore from Backup:**
1. Supabase Dashboard → **SQL Editor**
2. Run backup SQL file
3. Verify data restoration

**Re-deploy Application:**
1. Render dashboard → **"Manual Deploy"**
2. Select branch and deploy

---

## Troubleshooting

### Common Issues

#### 1. "Database Connection Failed"

**Check:**
- ✅ Supabase project is active
- ✅ Credentials in Render environment variables
- ✅ Supabase allows connections from all IPs (Settings → Database → Connections)

#### 2. "Build Failed" on Render

**Check:**
- ✅ `render.yaml` syntax is correct
- ✅ All file paths are correct
- ✅ Build logs for specific errors

#### 3. "502 Bad Gateway"

**Solutions:**
- Wait 2-3 minutes (Render cold start on free tier)
- Check application logs for errors
- Verify PORT environment variable

#### 4. "Session Errors"

**Fix:**
```bash
# In Render dashboard, add to build command:
mkdir -p storage/logs storage/sessions storage/cache
chmod -R 777 storage/
```

### Getting Help

1. **Documentation:** Check README.md
2. **Logs:** Render dashboard → Logs
3. **Database:** Supabase dashboard → Logs
4. **GitHub Issues:** Create an issue in your repository

---

## Cost Estimation

### Free Tier (Development/Small Production)

- **Supabase:** Free
  - 500MB database
  - 50,000 monthly active users
  - Community support

- **Render:** Free
  - 750 hours/month (shared)
  - 512MB RAM
  - Auto-deploy from Git

**Total: $0/month**

### Production Tier

- **Supabase Pro:** $25/month
  - 8GB database
  - Unlimited users
  - Email support

- **Render Standard:** $7/month
  - Dedicated resources
  - 2GB RAM
  - Priority support

**Total: ~$32/month**

---

## Next Steps

1. ✅ Test all functionality
2. ✅ Register institutions
3. ✅ Create user accounts
4. ✅ Train administrators
5. ✅ Monitor performance
6. ✅ Set up backups
7. ✅ Configure custom domain
8. ✅ Enable SSL

---

## Support Resources

- **Supabase Docs:** [supabase.com/docs](https://supabase.com/docs)
- **Render Docs:** [render.com/docs](https://render.com/docs)
- **PHP Docs:** [php.net/docs](https://php.net/docs)

---

**Deployment Complete! 🎉**

Your SUAS application is now live and ready to use!
