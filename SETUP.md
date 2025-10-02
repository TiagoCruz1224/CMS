# 🚀 Quick Setup Guide

## Step-by-Step Installation

### 1. Prerequisites Check
```bash
# Check PHP version (need 8.2+)
php -v

# Check Composer
composer --version

# Check Node.js (need 18+)
node -v

# Check MySQL
mysql --version
```

### 2. Clone & Navigate
```bash
git clone <your-repo-url>
cd CMS
```

### 3. Backend Setup

```bash
cd backend

# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env.local

# Edit database credentials in .env.local
nano .env.local
# Set: DATABASE_URL="mysql://your_user:your_password@127.0.0.1:3306/cms_db?serverVersion=8.0"

# Generate JWT keys
php bin/console lexik:jwt:generate-keypair

# Create database
php bin/console doctrine:database:create

# Run migrations
php bin/console doctrine:migrations:migrate

# Register admin user
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@cms.com",
    "password": "admin123",
    "firstName": "Admin",
    "lastName": "User",
    "roles": ["ROLE_ADMIN"]
  }'

# Start Symfony server (without TLS for development)
symfony server:start --no-tls
```

### 4. Frontend Setup

Open a new terminal:

```bash
cd frontend

# Install dependencies
yarn install

# Start dev server
yarn dev
```

### 5. Access the Application

- **Frontend**: http://localhost:5173
- **Backend API**: http://localhost:8000/api
- **Login**: admin@cms.com / admin123

## 🧪 Testing the API

Run the backend test suite:

```bash
cd backend
./test-api.sh
```

## 🔧 Common Issues

### Issue: JWT Keys Not Found
**Solution:**
```bash
cd backend
php bin/console lexik:jwt:generate-keypair
```

### Issue: Database Connection Failed
**Solution:**
1. Check MySQL is running: `sudo systemctl status mysql`
2. Verify credentials in `.env.local`
3. Ensure database exists: `php bin/console doctrine:database:create`

### Issue: Port Already in Use
**Solution:**
```bash
# For Symfony (change port)
symfony server:start --port=8001 --no-tls

# For Vite (change port in vite.config.js)
# Or kill the process using the port
lsof -ti:8000 | xargs kill -9
```

### Issue: CORS Errors
**Solution:**
- Ensure Symfony is running with `--no-tls` flag
- Check `backend/config/packages/nelmio_cors.yaml` is configured correctly

### Issue: Frontend Can't Connect to API
**Solution:**
1. Check Symfony is running on port 8000
2. Verify proxy in `frontend/vite.config.js`
3. Check browser console for errors

## 📝 Next Steps

1. ✅ Change default admin password
2. ✅ Configure your `.env.local` properly
3. ✅ Update `CORS_ALLOW_ORIGIN` for production
4. ✅ Set up proper file upload directories
5. ✅ Configure email settings if needed

## 🎯 Development Workflow

```bash
# Start backend (Terminal 1)
cd backend && symfony server:start --no-tls

# Start frontend (Terminal 2)
cd frontend && yarn dev

# Run tests when needed
cd backend && ./test-api.sh
```

## 📚 Additional Resources

- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [Vue 3 Documentation](https://vuejs.org/)
- [Tailwind CSS](https://tailwindcss.com/docs)
- [Pinia Documentation](https://pinia.vuejs.org/)
