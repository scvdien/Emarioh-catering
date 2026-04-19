# Live Deployment Checklist

## Hostinger

Before uploading, you can create a deployment zip from the project root with:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\create-hostinger-zip.ps1
```

This package includes hidden files such as `.htaccess` and your local `app/config.local.php`, while excluding `.git`, `.vscode`, session files, and local SQL backups.

1. Set the website PHP version to `8.2` or newer in hPanel.
2. Create a MySQL database and user in hPanel.
3. Upload the project into `public_html` or your chosen web root.
   - Do not upload local `storage/sessions/sess_*` files to the live server.
4. Create `app/config.local.php` on the live server before going live:
   - `app/config.local.php` is ignored by git, so it will not be included in a normal repository upload
   - Copy `app/config.local.php.example` and then edit the copied file on the server, or set the equivalent `EMARIOH_*` environment variables in hPanel
   - Set `development_mode` to `false`
   - Replace the `database` credentials with the Hostinger database name, user, and password
   - Keep `auto_create` as `false`
   - Keep `manage_schema` as `false` on live hosting after importing the SQL dump
   - Set `storage.path` and `storage.session_path` to a writable directory outside `public_html` when possible, for example `dirname(__DIR__) . '/../storage'`
   - Public image uploads are served through `media.php`, so the writable upload directory can stay under `storage` instead of `assets/images/uploads`
   - Set `app.url` to the final public `https://` URL for the site, including any subfolder, for example `https://your-domain.com` or `https://your-domain.com/emarioh-catering-services`
   - Set `force_https` to `true` after SSL is active so redirects, cookies, and generated links stay on HTTPS
   - The app now fails fast on non-local hosting when `app.url` or `force_https` is still missing or left on placeholder values
5. Import `database/schema/emarioh_catering_db.sql` into the Hostinger database.
   - If you are intentionally using app-driven schema bootstrapping, enable `manage_schema` only temporarily, load the app once, then turn it off again.
6. If you need your current local data instead of a fresh schema, export your local XAMPP database and import that dump instead.
7. Force HTTPS in Hostinger once SSL is active.
8. The app now ships with local UI fallbacks for fonts, icons, and Bootstrap behavior, so the pages stay usable even when third-party CDNs are slow or blocked.
9. Configure the PayMongo webhook URL to:

```text
https://your-domain.com/api/payments/paymongo-webhook.php
```

10. Replace PayMongo test keys with live keys before accepting real payments.
11. Test these flows after upload:
    - Admin login
    - Client registration and OTP
    - Public inquiry form
    - Booking submission
    - Image uploads in admin settings
    - Payment checkout and webhook status updates

## Notes

- The app no longer depends on automatic database creation by default.
- Runtime schema creation/update is intended for local or one-time maintenance use, not normal live traffic.
- Sensitive folders are blocked with `.htaccess`.
- If `storage` stays inside `public_html`, the included `.htaccess` files help block direct access, but an external writable path is still the safer option.
