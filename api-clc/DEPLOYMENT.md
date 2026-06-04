# CLC Deployment

## API Laravel (`api-clc`)

1. Upload `api-clc` to cPanel.
2. Set `.env` database credentials to the existing `clinic_db`.
3. Set `APP_URL=https://your-api-domain.com`.
4. Set `FRONTEND_URL=https://your-mobile-or-web-domain.com` for reset password links.
5. Configure SMTP when ready:
   - `MAIL_MAILER=smtp`
   - `MAIL_HOST=...`
   - `MAIL_PORT=587`
   - `MAIL_USERNAME=...`
   - `MAIL_PASSWORD=...`
   - `MAIL_ENCRYPTION=tls`
   - `MAIL_FROM_ADDRESS=admin@your-domain.com`
6. Run `composer install --no-dev --optimize-autoloader`.
7. Run `php artisan migrate --force`.
8. Run `php artisan config:cache route:cache view:cache`.

## Web Admin (`web-clc`)

1. Upload `web-clc` to cPanel.
2. Point `.env` to the same `clinic_db`.
3. Run `composer install --no-dev --optimize-autoloader`.
4. Run `php artisan config:cache route:cache view:cache`.
5. Login default after API migration/seed: `admin@clc.test` / `password123`. Change it before production.

## Mobile Ionic (`mobile-clc`)

1. Update `src/environments/environment.prod.ts`:
   `apiUrl: 'https://your-api-domain.com/api'`
2. Build Android:
   - `npm install`
   - `npm run build -- --configuration production`
   - `npx cap sync android`
   - `npx cap open android`
3. Build APK/AAB from Android Studio.
