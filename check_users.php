<?php
require_once 'vendor/autoload.php';
require_once 'common/config/bootstrap.php';

$config = require 'console/config/main.php';
$app = new yii\console\Application($config);

echo "Checking users in database:\n";
$users = \common\models\User::find()->all();
foreach ($users as $user) {
    echo "ID: {$user->id}, Username: {$user->username}, Email: {$user->email}\n";
}

if (empty($users)) {
    echo "No users found in database\n";
    echo "Creating default user with ID 1...\n";
    
    $user = new \common\models\User();
    $user->id = 1;
    $user->username = 'admin';
    $user->email = 'admin@localhost';
    $user->setPassword('admin123');
    $user->generateAuthKey();
    $user->status = \common\models\User::STATUS_ACTIVE;
    
    if ($user->save()) {
        echo "Default user created successfully\n";
    } else {
        echo "Failed to create default user: " . json_encode($user->errors) . "\n";
    }
} else {
    echo "User ID 1 exists: " . (\common\models\User::findOne(1) ? 'YES' : 'NO') . "\n";
}
