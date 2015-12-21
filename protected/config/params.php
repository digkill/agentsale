<?php

return [
    'city' => 'perm',
    'openauth_long' => array(
        'client_id' => 'SALE_AGENT',
        'city' => 'perm',
        'secret' => 'qazxswdbnfkbq-tial4421asdfgh',
        'grant_type' => 'password',
        'response_type$c' => 'wc_xml'
    ),
    'mongo_host' => $_ENV['MONGO_PORT_27017_TCP_ADDR'],
    // идентификаторы продуктов
    'services' => array(
        5 => 'Интернет',
        12 => 'Кабельное ТВ',
        32 => 'Цифровое ТВ',
        53 => 'Дом.ru TV',
        31 => 'Телефония'
    ),
    'devices' => array(
        1 => '9000',
        2 => 'DMT',
        3 => 'CAM'
    )
];