# SUAS - Smart Unit Attendance System

> **Efficient • Secure • Smart**

A modern, multi-institution attendance management system with beautiful design and powerful features. Now with **Supabase** support and one-click deployment to **Render**.

---

## 📋 Table of Contents

- [Features](#-features)
- [Quick Start (Local Development)](#-quick-start-local-development)
- [Supabase Setup](#-supabase-setup)
- [Deployment to Render](#-deployment-to-render)
- [GitHub Actions CI/CD](#-github-actions-cicd)
- [Docker Deployment](#-docker-deployment)
- [System Architecture](#-system-architecture)
- [Default Credentials](#-default-credentials)
- [Troubleshooting](#-troubleshooting)
- [Support](#-support)

---

## 🚀 Quick Start (Local Development)

### Option 1: Traditional MySQL (XAMPP)

#### Step 1: Initialize Master Database

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click on the "SQL" tab
3. Copy and paste the entire contents of `init_master_db.sql`
4. Click "Go" to execute

✅ Success! You should see "Query executed successfully"

#### Step 2: Access the System

1. Start Apache and MySQL in XAMPP
2. Navigate to: `http://localhost/HLSUAS/`

#### Step 3: Register Your Institution

1. Click on **"Super Admin"** or go to: `http://localhost/HLSUAS/super_admin_login.php`
2. **Login:**
   - Username: `superadmin`
   - Password: `super123`
3. **Register Institution:**
   - Enter institution name (e.g., "Hansen Technical Institute")
   - Enter institution code (e.g., "HTI001")
   - Click "Create Database & Register"

### Option 2: PHP Built-in Server

```bash
# Navigate to project directory
cd HLSUAS

# Start PHP built-in server
php -S localhost:8000

# Access at http://localhost:8000
```

---

## 🐘 Supabase Setup

### Step 1: Create Supabase Project

1. Go to [supabase.com](https://supabase.com) and sign up/login
2. Click **"New Project"**
3. Fill in project details:
   - **Name:** SUAS Production
   - **Database Password:** Choose a strong password
   - **Region:** Choose closest to your users
4. Click **"Create new project"**

### Step 2: Get Database Credentials

1. In your Supabase dashboard, go to **Settings** → **Database**
2. Under **Connection string**, select **URI** mode
3. Copy the connection details:
   - **Host:** `db.xxxxxxxxxxxxx.supabase.co`
   - **Port:** `5432`
   - **Database:** `postgres`
   - **User:** `postgres`
   - **Password:** (your password from step 1)

### Step 3: Run Master Database Migration

1. In Supabase dashboard, go to **SQL Editor**
2. Click **"New query"**
3. Copy and paste the contents of `supabase/master_schema.sql`
4. Click **"Run"** to execute

✅ Master database schema is now set up!

### Step 4: Configure Environment Variables

Create a `.env` file in the project root:

```env
# Supabase Configuration
USE_SUPABASE=true
SUPABASE_DB_HOST=db.xxxxxxxxxxxxx.supabase.co
SUPABASE_DB_PORT=5432
SUPABASE_DB_USER=postgres
SUPABASE_DB_PASS=your-super-secure-password
SUPABASE_MASTER_DB_NAME=postgres

# Application Settings
APP_ENV=production
APP_URL=https://your-app.onrender.com
DEBUG=false
```

### Step 5: Test Connection

Access your application. The system will automatically connect to Supabase.

### Step 6: Register Institution (Creates Separate Schema)

**Note:** Supabase free plan includes one database. For multi-tenancy, we use PostgreSQL schemas instead of separate databases.

1. Login as Super Admin
2. Register institution as usual
3. The system will create schemas within the same Supabase database

---

## ☁️ Deployment to Render

### Prerequisites

- GitHub account
- Render account ([sign up free](https://render.com))
- Supabase account (for database)

### Step 1: Push to GitHub

```bash
# Initialize git repository
git init
git add .
git commit -m "Initial SUAS deployment"

# Create GitHub repository and push
git remote add origin https://github.com/yourusername/suas.git
git branch -M main
git push -u origin main
```

### Step 2: Create Supabase Database

Follow the [Supabase Setup](#-supabase-setup) steps above.

### Step 3: Deploy to Render

#### Option A: Using render.yaml (Recommended)

1. Go to [dashboard.render.com](https://dashboard.render.com)
2. Click **"New"** → **"Blueprint"**
3. Connect your GitHub repository
4. Select the repository containing `render.yaml`
5. Render will automatically detect the configuration
6. Click **"Apply"**

#### Option B: Manual Setup

1. Go to [dashboard.render.com](https://dashboard.render.com)
2. Click **"New"** → **"Web Service"**
3. Connect your repository
4. Configure:
   - **Name:** SUAS App
   - **Environment:** PHP
   - **Build Command:** `mkdir -p storage/logs && chmod -R 777 storage/`
   - **Start Command:** `php -S 0.0.0.0:$PORT -t public`
   - **Instance Type:** Starter (Free)

5. Add environment variables:

```
USE_SUPABASE=true
SUPABASE_DB_HOST=db.xxxxx.supabase.co
SUPABASE_DB_PORT=5432
SUPABASE_DB_USER=postgres
SUPABASE_DB_PASS=your-password
SUPABASE_MASTER_DB_NAME=postgres
APP_ENV=production
DEBUG=false
```

6. Click **"Create Web Service"**

### Step 4: Configure Domain (Optional)

1. In Render dashboard, go to your service
2. Click **"Settings"** → **"Custom Domains"**
3. Add your domain
4. Update DNS records as instructed

### Step 5: Access Your Application

Navigate to your Render URL: `https://suas-app.onrender.com`

---

## 🔧 GitHub Actions CI/CD

### Automatic Deployment

The repository includes a GitHub Actions workflow that:

1. **Tests** code on every push
2. **Builds** Docker image
3. **Deploys** to Render automatically

### Setup GitHub Secrets

1. Go to your GitHub repository
2. Click **Settings** → **Secrets and variables** → **Actions**
3. Add the following secrets:

```
RENDER_SERVICE_ID: your-render-service-id
RENDER_API_KEY: your-render-api-key
RENDER_DEPLOY_WEBHOOK: https://api.render.com/deploy/srv-xxxxx
```

### Manual Deployment Trigger

```bash
# Push to main branch triggers deployment
git push origin main

# Or create a release
git tag -a v1.0.0 -m "Release version 1.0.0"
git push origin v1.0.0
```

---

## 🐳 Docker Deployment

### Local Development with Docker

```bash
# Build Docker image
docker build -t suas-app .

# Run container
docker run -d -p 8080:80 \
  -e USE_SUPABASE=true \
  -e SUPABASE_DB_HOST=db.xxxxx.supabase.co \
  -e SUPABASE_DB_PASS=your-password \
  --name suas-app suas-app

# Access at http://localhost:8080
```

### Docker Compose (Optional)

Create `docker-compose.yml`:

```yaml
version: '3.8'

services:
  suas-app:
    build: .
    ports:
      - "8080:80"
    environment:
      - USE_SUPABASE=true
      - SUPABASE_DB_HOST=db.xxxxx.supabase.co
      - SUPABASE_DB_PASS=your-password
    volumes:
      - ./storage:/var/www/html/storage
```

Run:

```bash
docker-compose up -d
```

---

## 🏗️ System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    SUPERABASE DATABASE                       │
│                    (PostgreSQL)                              │
│  ┌──────────────┐  ┌──────────────────────────────────┐    │
│  │ super_admins │  │ institutions                     │    │
│  └──────────────┘  │ - id, name, code, db_name, active│    │
│                    └──────────────────────────────────┘    │
│                            │                                │
│                            ▼                                │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ Institution Schemas (PostgreSQL Schemas)            │   │
│  │ ┌──────────┐  ┌──────────┐  ┌──────────┐           │   │
│  │ │suas_inst1│  │suas_inst2│  │suas_inst3│           │   │
│  │ │(full DB) │  │(full DB) │  │(full DB) │           │   │
│  │ └──────────┘  └──────────┘  └──────────┘           │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    RENDER WEB SERVICE                        │
│              (PHP 8.2 + Apache)                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  SUAS Application                                    │  │
│  │  - Auto-scaling                                      │  │
│  │  - SSL/HTTPS                                         │  │
│  │  - Automatic Deployments                             │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔐 Default Credentials

### Super Admin (System-wide)
| Username   | Password   |
|------------|------------|
| superadmin | super123   |

⚠️ **Change immediately after first login!**

### Institution Admin
| Username | Password   |
|----------|------------|
| admin    | admin123   |

### Trainers
| Username | Password | Department  |
|----------|----------|-------------|
| john     | john123  | Electrical  |
| mary     | mary123  | Mechanical  |

### Students
| Admission No | Password | Name              |
|--------------|----------|-------------------|
| E001         | 123456   | Alice Mwangi      |
| E002         | 123456   | Brian Otieno      |
| E003         | 123456   | Catherine Njoroge |
| M001         | 123456   | Daniel Kimani     |

---

## 🛠️ Troubleshooting

### Database Connection Errors

**Error:** "Database connection failed"

**Solutions:**
1. Verify Supabase credentials in `.env`
2. Check Supabase project is active
3. Ensure database migrations ran successfully
4. Check firewall allows connections from Render

### Render Deployment Issues

**Error:** "Build failed"

**Solutions:**
1. Check build logs in Render dashboard
2. Verify `render.yaml` syntax
3. Ensure all environment variables are set
4. Check PHP version compatibility

### "Unknown database" Error

**Solution:** Run the master schema migration in Supabase SQL Editor.

### Session Errors

**Solution:** Ensure storage directories are writable:
```bash
chmod -R 777 storage/
```

### 502 Bad Gateway

**Solution:** 
1. Check application logs in Render dashboard
2. Verify PHP server is starting correctly
3. Check PORT environment variable

---

## 📊 Monitoring & Logs

### Render Logs

```bash
# View logs in Render dashboard
Dashboard → Your Service → Logs

# Or use Render CLI
render logs --service-id your-service-id
```

### Supabase Logs

```
Dashboard → Database → Logs
```

---

## 🔒 Security Best Practices

1. **Change default passwords** immediately
2. **Use environment variables** for sensitive data
3. **Enable HTTPS** (automatic on Render)
4. **Regular backups** of Supabase database
5. **Keep dependencies updated**
6. **Use strong passwords** for Supabase

---

## 📞 Support

For institution-specific support, contact your system administrator.

For technical issues:
- GitHub Issues: [Create an issue](https://github.com/yourusername/suas/issues)
- Documentation: Check this README

---

## 📄 License

© 2025 **SMART UNIT ATTENDANCE SYSTEM (SUAS)**

*Efficient • Secure • Smart*

---

## 🎯 Quick Reference

### Important Files

| File | Purpose |
|------|---------|
| `supabase/master_schema.sql` | Supabase master database |
| `supabase/institution_schema.sql` | Institution database |
| `config.php` | Database router |
| `config_master.php` | Master database |
| `.env.example` | Environment template |
| `render.yaml` | Render configuration |
| `Dockerfile` | Docker build |

### Important URLs

| Page | URL |
|------|-----|
| Home | `/` |
| Super Admin Login | `/super_admin_login.php` |
| Institution Selection | `/select_institution.php` |
| Admin Login | `/admin/login.php` |
| Trainer Login | `/lecturer/login.php` |
| Student Login | `/student_login.php` |

### Environment Variables

```env
USE_SUPABASE=true
SUPABASE_DB_HOST=
SUPABASE_DB_PORT=5432
SUPABASE_DB_USER=postgres
SUPABASE_DB_PASS=
SUPABASE_MASTER_DB_NAME=postgres
APP_ENV=production
DEBUG=false
```
