<?php

declare(strict_types=1);

return [
    'name' => 'ОКСМА',
    'legal_name' => 'ООО «СпецТехПром»',
    'description' => 'Производство и поставка промышленной техники с доставкой по России.',
    'base_url' => rtrim((string) (getenv('SITE_URL') ?: 'https://example.ru'), '/'),
    'email' => 'oksmaprom@yandex.ru',
    'phones' => [
        ['display' => '+7 937 435-17-00', 'href' => '+79374351700', 'primary' => true],
        ['display' => '+7 937 435-27-00', 'href' => '+79374352700', 'primary' => false],
        ['display' => '+7 937 445-67-21', 'href' => '+79374456721', 'primary' => false],
    ],
    'requisites' => [
        'inn' => '5258079050',
        'kpp' => '525801001',
        'ogrn' => '1085258005469',
        'legal_address' => '603064, Нижегородская область, г. Нижний Новгород, ул. Новикова-Прибоя, д. 6А, к. 3',
        'postal_address' => '603064, г. Нижний Новгород, а/я 56',
    ],
];
