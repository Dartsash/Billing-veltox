# Veltox Billing (PHP prototype)

Custom billing system using PHP + HTML + CSS.

Functions:

- Registration/Login
- Upon registration: **auto-create user in Pterodactyl** (API application)
- Balance in rubles (stored in kopecks)
- Tariffs (plans) and purchases: balance → **create server in Pterodactyl**

## 1) Installation

``` bash
sudo apt update
sudo apt install -y \
  nginx \
  mysql-server \
  php8.1 \
  php8.1-cli \
  php8.1-fpm \
  php8.1-mysql \
  php8.1-sqlite3 \
  php8.1-mbstring \
  php8.1-curl \
  php8.1-xml \
  php8.1-zip \
  unzip \
  git \
  curl
```
## 2) Installation Composer
``` bash
cd ~
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```
## 3) Cloning a project
``` bash
cd /var/www
git clone https://github.com/Dartsash/Billing-veltox.git billing
cd billing
```
## 4) Rights
``` bash
sudo chown -R www-data:www-data /var/www/billing
sudo chmod -R 755 /var/www/billing
sudo chmod -R 777 storage
```
## 3) Review .env
``` bash
cp .env.example .env
nano .env
----------------------------
# Base URL where your billing is accessible (optional)
APP_URL=http://localhost:8000

DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=billing
DB_USER=billing
DB_PASS=StrongPassHere

TURNSTILE_SITE_KEY=0x4AAAAAAB45cQuBnJG8zc86
TURNSTILE_SECRET_KEY=0x4AAAAAAB45cWww5qTiArcCF3C_kWY3Rgs


SMTP_HOST=connect.smtp.bz
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=sashaforzan@gmail.com
SMTP_PASS=xz
SMTP_FROM_EMAIL=no-reply@veltox.xyz
SMTP_FROM_NAME=Billing


APP_HOSTNAME="localhost"

# Pterodactyl settings
PTERO_BASE_URL=https://mgr.veltox.xyz
PTERO_PANEL_URL=https://mgr.veltox.xyz

ADMIN_EMAILS=sashaforzan@gmail.com

# Application API key (starts with ptla_...)
PTERO_APP_KEY=xz
```
## 4. Database
``` bash
mysql
CREATE DATABASE billing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'billing'@'localhost' IDENTIFIED BY 'StrongPassHere';
GRANT ALL PRIVILEGES ON billing.* TO 'billing'@'localhost';
FLUSH PRIVILEGES;
```
## 5. Migrations
``` bash
cd /var/www/billing
php bin/migrate.php
```
## 6. Nginx
``` bash
sudo nano /etc/nginx/sites-available/billing
server {
    server_name dartworld.pro www.dartworld.pro;

    root /var/www/html/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }

    location ~ /\. {
        deny all;
    }

    listen 443 ssl; # managed by Certbot
    ssl_certificate /etc/letsencrypt/live/dartworld.pro/fullchain.pem; # managed by Certbot
    ssl_certificate_key /etc/letsencrypt/live/dartworld.pro/privkey.pem; # managed by Certbot
    include /etc/letsencrypt/options-ssl-nginx.conf; # managed by Certbot
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem; # managed by Certbot


}
server {
    if ($host = www.dartworld.pro) {
        return 301 https://$host$request_uri;
    } # managed by Certbot


    if ($host = dartworld.pro) {
        return 301 https://$host$request_uri;
    } # managed by Certbot


    listen 80;
    server_name dartworld.pro www.dartworld.pro;
    return 404; # managed by Certbot




}
```
## 7. Activating
``` bash
sudo ln -s /etc/nginx/sites-available/billing /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```
## 8. HTTPS
```
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
```
