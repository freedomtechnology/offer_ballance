<?php
/**
 * Created by PhpStorm.
 * User: marko
 * Date: 17.03.16
 * Time: 22:15
 */

namespace app\controllers;

use yii\rest\ActiveController;

class UserController extends ActiveController
{
    public $modelClass = 'app\models\User';
}