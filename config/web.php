<?php

$params = require(__DIR__ . '/params.php');

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'IKqUfQgyuEeeJHZEEJWWgL3MtMB_7yro',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'urlManager' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => false,
            'rules' => [
                'GET balance' => 'balance/balance-all',
                'GET,HEAD balance/<uid:\d+>' => 'balance/balance-user',
                'POST balance_add_user' => 'balance/balance-add-user',
                'DELETE balance_delete_user/<uid:\d+>' => 'balance/balance-delete-user',
                'POST balance_add' => 'balance/balance-add',
                'POST balance_sub' => 'balance/balance-sub',
                'POST balance_transfer' => 'balance/balance-transfer',

                //balance history routes
                'GET history/uid=<uid:\d+>&date-start=<dateStart:\d{4}\-{1}\d{2}\-{1}\d{2}>&date-end=<dateEnd:\d{4}\-{1}\d{2}\-{1}\d{2}>' => 'history/balance-history',
                'GET history/date-start=<dateStart:\d{4}\-{1}\d{2}\-{1}\d{2}>&date-end=<dateEnd:\d{4}\-{1}\d{2}\-{1}\d{2}>' => 'history/balance-history',
                'GET history/uid=<uid:\d+>&date-start=<dateStart:\d{4}\-{1}\d{2}\-{1}\d{2}>' => 'history/balance-history',
                'GET history/date-start=<dateStart:\d{4}\-{1}\d{2}\-{1}\d{2}>' => 'history/balance-history',
                'GET history/uid=<uid:\d+>' => 'history/balance-history',
                'GET history' => 'history/balance-history',
            ]
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
            'useFileTransport' => true,
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
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;
