<?php
// Template untuk hosting. Salin file ini menjadi main-local.php di server hosting,
// lalu isi kredensial database hosting.
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=GANTI_NAMA_DB;port=3306',
            'username' => 'GANTI_USER_DB',
            'password' => 'GANTI_PASSWORD_DB',
            'charset' => 'utf8',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            'useFileTransport' => true,
        ],
    ],
];
