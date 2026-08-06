# Каталог техники ОКСМА

Многостраничный сайт-каталог на PHP 8.1+, HTML, CSS и JavaScript. База данных и административная панель не используются: разделы, товары, характеристики и изображения хранятся в `data/catalog.php`.

## Готовые страницы

- главная `/`;
- типовая категория `/catalog/zagruzchiki-suhih-kormov/`;
- типовой товар `/product/zsk-10/`;
- политика конфиденциальности `/privacy/`;
- SEO-файлы `/robots.txt` и `/sitemap.xml`;
- собственная страница 404.

## Локальный запуск

```powershell
php -S 127.0.0.1:8765 router.php
```

Откройте `http://127.0.0.1:8765/`.

## Проверки

```powershell
php tests/php/run.php
node --test tests/js/*.test.mjs
python scripts/validate_tokens.py
python scripts/validate_contrast.py
```

## Где менять контент

- реквизиты, телефоны и email: `config/app.php`;
- товары, категории и характеристики: `data/catalog.php`;
- SMTP без публикации пароля: `config/mail.php`;
- изображения: `assets/images/`.

Подробная инструкция по публикации находится в [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).
