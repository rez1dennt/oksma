# Реестр замен изображений каталога

Дата обновления: 2026-08-06.

Все опубликованные растровые ассеты конвертированы в WebP с сохранением полного исходного кадра и его пропорций. Кадрирование, удаление фона, дорисовка и генеративная обработка не применялись.

## Фоны разделов

| Ассет | Источник |
|---|---|
| `category-feed.webp` | Архив заказчика: `IMG_0583.HEIC` |
| `category-trailers.webp` | Архив заказчика: `diting_result_108fbdd96a1111f19d5e161ca0c37f38_1.jpeg` |
| `category-tankers.webp` | Фото ПЦ-11В из коммерческого предложения заказчика |
| `category-disinfection.webp` | Архив заказчика: `IMG_20260703_083855.jpg` |
| `category-parts.webp` | [Сервис сельхозтехники «Стартек»](https://startek.su/servis/) |

## Товары

| Модель | Опубликованный ассет | Источник |
|---|---|---|
| Низкорамный прицеп | `products/lowbed/lowbed-trailer-1.webp` | [McCauley 2 Axle Low Loader](https://mccauleys.co.uk/products/agricultural-trailers/low-loader-trailers/2-axle-low-loader) |
| ПГТС-12 | `products/pgts/pgts-12-1.webp` | Архив заказчика: `diting_result_3a291f705da111f1a25202f24ded3856_1.jpeg` |
| ПГТС-3 | `products/pgts/pgts-3-1.webp` | Архив заказчика: `diting_result_3502dbbe6ada11f1a45326b1e90a609c_1.jpeg` |
| ПГТС-6.5 | `products/pgts/pgts-6-5-1.webp` | Архив заказчика: `IMG_1394.JPG` |
| ППТС-12 | `products/ppts/ppts-12-1.webp` | Архив заказчика: `diting_result_108fbdd96a1111f19d5e161ca0c37f38_1.jpeg` |
| ППТС-20 | `products/ppts/ppts-20-1.webp` | Архив заказчика: `diting_result_1619f32a658311f19ffadaa6b8b979a6_1.jpeg` |
| ЗСК-7 | `products/zsk/zsk-7-1.webp` | Архив заказчика: `IMG_0585.HEIC` |
| ЗСК-10 | `products/zsk/zsk-10-1.webp` | [СЕЛАГРИКО — ЗСК-10](https://selagrico.ru/product/pritsepy-i-polupritsepy/zagruzchiki-kormov/zagruzchik-sukhikh-kormov-zsk-10-/) |
| ЗСК-12 | `product-zsk-12-1.webp` | Архив заказчика: `IMG_0591.HEIC` |
| ЗСК-20 | `product-zsk-20-1.webp` | [СпецТехПром — ЗСК-20](https://stpnn.ru/zagruzchik-suhih-kormov-zsk-20-17-19-kub-m-2/) |
| ЗСК-21 | `product-zsk-21-1.webp` | Временный визуальный аналог: другой ракурс ЗСК-20 с сайта производителя; заменить после получения чистого фото ЗСК-21 без рекламного баннера |
| ПЗК-15 | `product-pzk-15-1.webp` | Временный визуальный аналог прицепного загрузчика того же семейства с [карточки ЗСК-10](https://selagrico.ru/product/pritsepy-i-polupritsepy/zagruzchiki-kormov/zagruzchik-sukhikh-kormov-zsk-10-/); заменить после получения фото ПЗК-15 |
| ПЦ-2 | `products/pc/pc-2-1.webp` | Архив заказчика: `diting_result_04a3a29b6bd511f1b7a552e2127ae808_1.jpeg` |

## Правило публикации

- В WebP сохраняется исходное разрешение каждого выбранного файла; принудительное приведение к единому размеру отключено.
- В карточках и галереях используется `object-fit: contain`, поэтому техника видна целиком и не обрезается рамкой компонента.
- Фоны разделов уникальны и не используют один и тот же кадр повторно.
- Временные аналоги не содержат чужих телефонов, цен, водяных знаков или рекламных плашек.
