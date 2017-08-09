<?php
$params = require(__DIR__ . '/params.php');

$config = [
    'id' => 'requestService',
    'basePath' => dirname(__DIR__),
    'language' => 'ru-RU',
    'bootstrap' => ['log'],
    'modules' => [
        'debug' => [
            'class' => 'yii\debug\Module',
            'allowedIPs' => ['*']
        ]
    ],
    'components' => [
        'session' => [
            'name' => 'PHPSESSID',
            'class' => 'yii\web\CacheSession',
            'cache' => 'sessionCache',
            'cookieParams' => [
                'lifetime' => 60 * 60 * 24 * 40,
            ],
            'timeout' => 60 * 60 * 24 * 40,
        ],        

        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'df32ew32s@#2w45e_*^*%*&87',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => false,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],

        ],
        'db' => require(__DIR__ . '/db.php'),
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => array(
                '/' => 'site/index',
                'login' => 'site/login',
                'logout' => 'site/logout',
                'confirm' => 'site/confirm',
                'request' => 'site/request',
                'dummy' => 'site/dummy',
                'get-streets' => 'site/getstreets',
                'get-houses' => 'site/gethouses',
                'check-address' => 'site/checkaddress',
                'get-devices' => 'site/getdevices',
                'get-packages' => 'site/getpackages',
                'check-agreement' => 'site/checkagreement',
                '<module:\w+>/<controller:\w+>/<action:\w+>/<id:\d+>' => '<module>/<controller>/<action>',
                '<module:\w+>/<controller:\w+>/<action:\w+>' => '<module>/<controller>/<action>',
                '<controller:\w+>/<id:\d+>' => '<controller>/view',
                '<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
                '<controller:\w+>/<action:\w+>' => '<controller>/<action>'
            ),
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug']['class'] = 'yii\debug\Module';

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = 'yii\gii\Module';

    $config['modules']['debug']['allowedIPs'] = ['*'];
}

return $config;
