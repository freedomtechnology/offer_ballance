<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */


namespace app\controllers;

use Yii;

use \Firebase\JWT\JWT;
use yii\web\ForbiddenHttpException;
use yii\web\UnauthorizedHttpException;


class MyRESTActiveController extends \yii\rest\ActiveController
{

    /**
     * Checks the privilege of the current user.
     *
     * This method should be overridden to check whether the current user has the privilege
     * to run the specified action against the specified data model.
     * If the user does not have access, a [[ForbiddenHttpException]] should be thrown.
     *
     * @param string $action the ID of the action to be executed
     * @param object $model the model to be accessed. If null, it means no specific model is being accessed.
     * @param array $params additional parameters
     * @throws ForbiddenHttpException if the user does not have access
     */
    public function checkAccess($action, $model = null, $params = [])
    {
        // проверить, имеет ли пользователь доступ к $action и $model
        // выбросить ForbiddenHttpException, если доступ следует запретить

        //standard REST methods are disabled
        if (in_array($action, ['update', 'delete', 'view', 'create', 'index','options']))
        {
            throw(new ForbiddenHttpException());
        }

        //JWT check
        $headers= Yii::$app->request->getHeaders();
        //var_dump($headers);
        $jwt = $headers->get('Authorization');
        //$jwt = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6IjEyIiwibmFtZSI6IkpvaG4gRG9lIiwiYWRtaW4iOnRydWV9.tjHYSFanSdw5Ahk0N1HkcD-nYGLHt6i2ekOc8NYnBns';
        $key = 'secret';


        try {
            $decoded = JWT::decode($jwt, $key, array('HS256'));
        }
        catch (\Exception $e)
        {
            throw(new UnauthorizedHttpException($e->getMessage(), $e->getCode()));
        }


        if (!empty($decoded->id) && !empty($decoded->admin))
        {
            $userId = $decoded->id;
            $admin = $decoded->admin;
        }
        else
        {
            throw(new ForbiddenHttpException());
        }


        //for regular user allowed only these methods
        if (!$admin && !in_array($action, ['BalaceUser', 'BalaceHistory']))
        {
            throw(new ForbiddenHttpException());
        }

        return ['userId' => $userId, 'admin' => $admin];
    }
}
