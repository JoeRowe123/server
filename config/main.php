<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-server',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'server\controllers',
    'bootstrap' => ['log'],
    'modules' => [],
    'components' => [
        'request' => [
            'class' => 'common\components\request\Request',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
            'csrfParam' => '_csrf-server',
            'enableCsrfValidation' => false,
        ],
        'response' => [
            'charset' => 'UTF-8',
            'class' => '\common\components\response\ApiResponse',
            'on beforeSend' => function ($event) {
                //@var $response \yii\web\Response
                $response = $event->sender;
                $response->getHeaders()->set('Access-Control-Allow-Origin', '*');
                $response->getHeaders()->set('Access-Control-Allow-Headers', 'accept, x-app-id, cache-control, token, content-type, Authorization');
                $response->getHeaders()->set('Access-Control-Allow-Methods', 'GET, POST, PUT,DELETE, OPTIONS');
            },
            'exceptActions' => [

            ]
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-server', 'httpOnly' => true],
        ],
        /*'session' => [
            // this is the name of the session cookie used for login on the server
            'name' => 'advanced-server',
        ],*/
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => false,
            //'suffix' => '.html',
            'rules' => [
                ['class' => 'yii\rest\UrlRule',
                    'controller' => 'user',
                    'extraPatterns'=>[
                        'POST login'=>'login',
                        'GET auth'=>'auth',
//                        'GET no-auth'=>'no-auth',
                    ],
                ],
            ],
        ],
    ],
    'params' => $params,
];
