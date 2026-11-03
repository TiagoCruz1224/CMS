# Setup Guide

## Step-by-Step Installation

### 1. Prerequisites Check
```bash
# Check PHP version (need 8.2+)
php -v

# Check PHP extensions (need sodium and pdo_mysql; pdo_sqlite for the tests)
php -m | grep -E "sodium|pdo_mysql|pdo_sqlite"

# Check Composer
composer --version

# Check Node.js (need 18+)
node -v

# Check MySQL 8.0 (or Docker, to use backend/compose.yaml instead)
mysql --version
```

### 2. Clone the Repository
```bash
git clone https://github.com/TiagoCruz1224/CMS.git
cd CMS
```

### 3. Backend Setup

```bash
cd backend

# Copy environment file (before composer install: its post-install scripts boot Symfony)
cp .env.example .env

# Edit DATABASE_URL, APP_SECRET and JWT_PASSPHRASE in .env (or override them in .env.local)
nano .env
# e.g. DATABASE_URL="mysql://your_user:your_password@127.0.0.1:3306/cms_db?serverVersion=8.0&charset=utf8mb4"

# Install PHP dependencies (versions pinned by composer.lock)
composer install

# Optional: start MySQL 8.0 with Docker (its defaults match .env.example)
docker compose up -d database

# Generate JWT keys
php bin/console lexik:jwt:generate-keypair

# Create database
php bin/console doctrine:database:create --if-not-exists

# Run migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Start Symfony server (without TLS for development)
symfony server:start --no-tls
```

### 4. Create an Admin User

Registration always creates a regular user (`ROLE_USER`); roles sent to the API are ignored.
With the server running, register through the API and then promote the account (in `backend/`):

```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "<admin-email>",
    "password": "<password>",
    "firstName": "Admin",
    "lastName": "User"
  }'

php bin/console dbal:run-sql "UPDATE user SET roles = '[\"ROLE_ADMIN\"]' WHERE email = '<admin-email>'"
```

### 5. Frontend Setup

Open a new terminal in the repository root:

```bash
cd frontend

# Install dependencies
yarn install

# Start dev server
yarn dev
```

### 6. Access the Application

- **Frontend**: http://localhost:5173
- **Backend API**: http://localhost:8000/api
- **Login**: the admin account from step 4 (there are no default credentials)

## Testing the API

Run the backend test suite (functional tests on a throwaway SQLite database, no MySQL needed):

```bash
cd backend
php bin/phpunit
```

Smoke-test a running server end to end (registers its own users, promotes the admin with `bin/console`,
exits non-zero on failure):

```bash
cd backend
./test-api.sh
```

## Common Issues

### Issue: JWT Keys Not Found
**Solution:**
```bash
cd backend
php bin/console lexik:jwt:generate-keypair
```

### Issue: JWT Key Generation Fails on Windows (`error:80000003`)
**Solution:** PHP for Windows ships without a default OpenSSL config. Point `OPENSSL_CONF` at the one bundled with PHP:
```powershell
$env:OPENSSL_CONF = "C:\path\to\php\extras\ssl\openssl.cnf"
php bin/console lexik:jwt:generate-keypair
```

### Issue: Database Connection Failed
**Solution:**
1. Check MySQL is running: `sudo systemctl status mysql` (or `docker compose ps` in `backend/`)
2. Verify `DATABASE_URL` in `.env` (or `.env.local`)
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

## Next Steps

1. Use a strong password for the admin account
2. Replace the example values in `.env` (`APP_SECRET`, `JWT_PASSPHRASE`, database password)
3. Update `CORS_ALLOW_ORIGIN` for production
4. Configure email settings if needed

## Development Workflow

```bash
# Start backend (Terminal 1)
cd backend && symfony server:start --no-tls

# Start frontend (Terminal 2)
cd frontend && yarn dev

# Run tests when needed
cd backend && php bin/phpunit
```

## Additional Resources

- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [Vue 3 Documentation](https://vuejs.org/)
- [Tailwind CSS](https://tailwindcss.com/docs)
- [Pinia Documentation](https://pinia.vuejs.org/)
