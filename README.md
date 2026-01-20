<<<<<<< HEAD
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
=======
# Pterodactyl Billing (PHP prototype)

Самописный мини‑биллинг на **PHP + HTML + CSS**.

Функции:

- Регистрация/логин
- При регистрации: **автосоздание пользователя в Pterodactyl** (Application API)
- Баланс в рублях (хранится в копейках)
- Тарифы (plans) и покупка: списание с баланса → **создание сервера в Pterodactyl**
- Demo‑пополнение для тестирования (без платёжки)

> Это прототип: для прода требуется доработка (платёжка, админка, логирование, лимиты, продление, анти‑фрод).

## 1) Установка

```bash
cd ptero-billing
cp .env.example .env
php bin/migrate.php
php bin/seed_plans.php
php -S 127.0.0.1:8000 -t public
```

Открывай: `http://127.0.0.1:8000`

## 2) Настройка Pterodactyl

В `.env`:

- `PTERO_BASE_URL` — базовый URL панели (например `https://panel.example.com`)
- `PTERO_PANEL_URL` — URL панели для кнопки «Открыть панель» (обычно такой же)
- `PTERO_APP_KEY` — Application API key (начинается с `ptla_...`)

## 3) Тарифы

Тарифы находятся в таблице `plans`.

Для запуска примера используй `php bin/seed_plans.php`, но обязательно замени:

- `ptero_egg_id` — ID вашего Egg
- `deploy_locations_json` — массив ID локаций (Locations)
- `environment_json` — переменные Egg (ключи и значения)

Если переменные Egg не заданы, Pterodactyl вернёт ошибку валидации.

## 4) Как тестировать

1. Регистрация в биллинге.
2. На странице «Баланс» сделать demo‑пополнение.
3. Купить тариф → будет создан сервер в Pterodactyl.

## 5) Важные замечания по безопасности

- Не храни API ключ Pterodactyl в коде — только в `.env`.
- Для продакшна обязательно HTTPS.
- Баланс веди через **транзакции** (ledger), а не просто `balance +=`.
- Добавь rate limit на логин/регистрацию.

>>>>>>> 1229945 (Initial commit)
