# UstaGo Avto — Backend Production Deployment Checklist

Ushbu yo'riqnoma Laravel backendni production serverga (VPS / Cloud Ubuntu) xavfsiz va to'liq joylashtirish bo'yicha ketma-ketlikni belgilaydi.

---

## 1. Talablar
- **PHP:** 8.2 yoki undan yuqori (extensions: `bcmath`, `ctype`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `curl`)
- **Web server:** Nginx (yoki Apache)
- **Database:** MySQL 8.0+ yoki MariaDB 10.5+
- **SSL sertifikat:** HTTPS (Let's Encrypt / Certbot) — TWA va PWA faqat HTTPS orqali ishlaydi!

---

## 2. Serverga deploy qilish qadamlari

### 1) Kodni yuklash va konfiguratsiya
```bash
cd /var/www/ustago-backend
cp .env.production.example .env
nano .env # Ma'lumotlar bazasi paroli va domenlarni kiriting
```

### 2) Composer optimizatsiyalangan o'rnatish
```bash
composer install --no-dev --optimize-autoloader
```

### 3) Yangi APP_KEY generatsiya qilish
```bash
php artisan key:generate
```

### 4) Ruxsatlarni (permissions) to'g'rilash
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 5) Fayllar xotirasi (Storage Link)
```bash
php artisan storage:link
```

### 6) Bazani migratsiya qilish
```bash
php artisan migrate --force
```

### 7) Kesh va optimizatsiya buyruqlari
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## 3. Nginx konfiguratsiyasi namunasi (`/etc/nginx/sites-available/api.ustago.uz`)

```nginx
server {
    listen 80;
    server_name api.ustago.uz;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name api.ustago.uz;

    ssl_certificate /etc/letsencrypt/live/api.ustago.uz/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.ustago.uz/privkey.pem;

    root /var/www/ustago-backend/public;
    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 4. Asinxron Queue Worker (Supervisor)
Fon xabarnomalar va bildirishnomalarni qayta ishlash uchun `/etc/supervisor/conf.d/ustago-worker.conf`:
```ini
[program:ustago-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/ustago-backend/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/ustago-backend/storage/logs/worker.log
stopwaitsecs=3600
```
