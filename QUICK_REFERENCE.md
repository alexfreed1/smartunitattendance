# SUAS Quick Reference Card

## 🚀 Deployment Checklist

### 1. Supabase Setup (5 minutes)
- [ ] Create project at [supabase.com](https://supabase.com)
- [ ] Copy database credentials (host, port, user, password)
- [ ] Run `supabase/master_schema.sql` in SQL Editor
- [ ] Verify tables: `super_admins`, `institutions`

### 2. GitHub Setup (2 minutes)
- [ ] Create repository on GitHub
- [ ] Push code: `git init && git add . && git commit -m "Initial" && git remote add origin <url> && git push -u origin main`

### 3. Render Deployment (5 minutes)
- [ ] Connect GitHub to [Render](https://render.com)
- [ ] Create Web Service from repository
- [ ] Set environment variables:
  ```
  USE_SUPABASE=true
  SUPABASE_DB_HOST=db.xxx.supabase.co
  SUPABASE_DB_PORT=5432
  SUPABASE_DB_USER=postgres
  SUPABASE_DB_PASS=your-password
  SUPABASE_MASTER_DB_NAME=postgres
  APP_ENV=production
  DEBUG=false
  ```
- [ ] Deploy and wait for success

### 4. Post-Deployment (3 minutes)
- [ ] Access your Render URL
- [ ] Login as Super Admin: `superadmin` / `super123`
- [ ] Register first institution
- [ ] Change all default passwords

---

## 📁 Important Files

| File | Purpose |
|------|---------|
| `supabase/master_schema.sql` | Run in Supabase SQL Editor |
| `supabase/institution_schema.sql` | Schema for new institutions |
| `.env.example` | Environment template |
| `render.yaml` | Render auto-deploy config |
| `Dockerfile` | Docker build config |
| `DEPLOYMENT_GUIDE.md` | Full deployment guide |

---

## 🔑 Default Credentials

| Role | Username | Password | Change First? |
|------|----------|----------|---------------|
| Super Admin | `superadmin` | `super123` | ⚠️ YES |
| Admin | `admin` | `admin123` | ⚠️ YES |
| Trainer | `john` | `john123` | Recommended |
| Student | `E001` | `123456` | Optional |

---

## 🌐 URLs

| Page | Path |
|------|------|
| Home | `/` |
| Super Admin | `/super_admin_login.php` |
| Select Institution | `/select_institution.php` |
| Admin Login | `/admin/login.php` |
| Trainer Login | `/lecturer/login.php` |
| Student Login | `/student_login.php` |

---

## 🐛 Quick Troubleshooting

| Error | Solution |
|-------|----------|
| Database connection failed | Check Supabase credentials |
| Build failed | Check render.yaml syntax |
| 502 Bad Gateway | Wait 2-3 min (cold start) |
| Session errors | `chmod -R 777 storage/` |
| Unknown database | Run master_schema.sql |

---

## 💰 Cost

- **Free Tier**: $0/month (Supabase Free + Render Free)
- **Production**: ~$32/month (Supabase Pro $25 + Render Standard $7)

---

## 📞 Support Links

- Supabase Docs: [supabase.com/docs](https://supabase.com/docs)
- Render Docs: [render.com/docs](https://render.com/docs)
- PHP Docs: [php.net/docs](https://php.net/docs)

---

**Total Deployment Time: ~15 minutes** ⏱️
