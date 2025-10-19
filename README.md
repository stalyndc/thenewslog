# TheNewsLog.org

Initial scaffold generated from PRD and Agents guidelines.

## Getting Started

- Clone the repo and install dependencies with `composer install`.
- Copy `.env.example` to `.env` and update credentials as needed.
- Default admin login uses `admin@example.com` / `admin123` (replace `ADMIN_PASS_HASH` with your own hash via `php -r "echo password_hash('yourpass', PASSWORD_BCRYPT);"` — keep the hash wrapped in quotes to prevent env expansion).

## Local Development

- Run the built-in PHP server: `php -S 127.0.0.1:8000 -t public`.
- Visit `http://127.0.0.1:8000/` for the reader view or `/admin/login` for the admin placeholder.
- Authenticated admin routes are under `/admin/*`; log in via `/admin` with the credentials configured in `.env`.

## Logs

- Application logs write to `storage/logs/app.log` (created automatically on bootstrap).
