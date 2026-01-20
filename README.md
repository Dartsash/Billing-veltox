# Veltox Billing (PHP prototype)

Custom billing system using PHP + HTML + CSS.

Functions:

- Registration/Login
- Upon registration: **auto-create user in Pterodactyl** (API application)
- Balance in rubles (stored in kopecks)
- Tariffs (plans) and purchases: balance → **create server in Pterodactyl**

## 1) Installation

``` bash
CD-ptero-billing
cp .env.example .env
php bin/migrate.php
php bin/seed_plans.php
```

## 2) Pterodactyl Configuration

In `.env`:

- `PTERO_BASE_URL` — panel base URL (e.g. `https://panel.example.com`)
- `PTERO_PANEL_URL` — panel URL.
- `PTERO_APP_KEY` — Application API key (starts with `ptla_...`)

## 3) Plans

Plans are located in the "plans" table.

To run the example, use `php bin/seed_plans.php`, but be sure to replace:

- `ptero_egg_id` — your egg ID
- `deploy_locations_json` — location array ID
- `environment_json` — Egg variables (keys and values)

If the Egg variables are not set, Pterodactyl will return a validation error.
