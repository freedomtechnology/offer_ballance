<?php
/**
 * Created by PhpStorm.
 * User: marko
 * Date: 17.03.16
 * Time: 14:13
 */

namespace app\controllers;

use Yii;
use app\models\UserBallance;
use yii\web\ForbiddenHttpException;
use yii\web\ServerErrorHttpException;


class BallanceController extends \yii\rest\ActiveController
{
    public $modelClass = 'app\models\UserBallance';
    private $data;


    public function actionBallanceAll()
    {
        $this->checkAccess('BallaceAll', $this->modelClass);
        return UserBallance::find()->all();
    }

    public function actionBallanceOne($uid)
    {
        $this->checkAccess('BallaceOne', $this->modelClass);
        $this->userExistsWithEception($uid);

        return UserBallance::findOne($uid);
    }

    public function actionBallanceAddUser()
    {
        $this->checkAccess('BallaceAddUser', $this->modelClass);
        $uid = $this->getRequestUserId();

        if ($this->userExists($uid))
        {
            return $this->actionBallanceOne($uid);
        }
        else
        {
            $user = new $this->modelClass();
            $user->user_id = $uid;
            $user->save();

            if (!$user->hasErrors())
            {
                return $this->actionBallanceOne($uid);
            }
            else
            {
                throw(new ServerErrorHttpException('Failed to add  new user'));
            }
        }
    }

    public function actionBallanceDeleteUser($uid)
    {
        $this->checkAccess('BallanceDeleteUser', $this->modelClass);
        $this->checkUserIdFormat($uid);

        $this->userExistsWithEception($uid);

        $model = new $this->modelClass();
        $user = $model->findOne($uid);
        $user->delete();

        if (!$user->hasErrors())
        {
            $this->sendRequest('User deleted');
        }
        else
        {
            throw(new ServerErrorHttpException('Delete user failed'));
        }
    }

    public function actionBallanceAdd()
    {
        $this->checkAccess('BallanceAdd', $this->modelClass);

        //throw(new ForbiddenHttpException());
        return UserBallance::find()->all();
    }

    public function actionBallanceSub()
    {
        $this->checkAccess('BallaceBallanceSub', $this->modelClass);
        //throw(new ForbiddenHttpException());
        return UserBallance::find()->all();
    }

    public function actionBallanceTransfer()
    {
        $this->checkAccess('BallanceTransfer', $this->modelClass);
        //throw(new ForbiddenHttpException());
        return UserBallance::find()->all();
    }






    private function getRequestUserId()
    {
        $uid = isset($_GET['uid']) ? (int) $_GET['uid'] : 0;
        $uid = !$uid
            ? (isset($_POST['uid'])
                ? (int) $_POST['uid']
                : 0)
            : 0;

        $this->checkUserIdFormat($uid);

        return $uid;
    }

    private function checkUserIdFormat($uid)
    {
        if (!is_numeric($uid) || $uid < 1)
        {
            throw(new ServerErrorHttpException('User ID  is not correct'));
        }

        return true;
    }

    private function getRequestData()
    {
        $this->data = $_POST;
        $this->data['user_id'] = $this->getRequestUserId();

        return $this->data;
    }

    private function userExists($uid)
    {
        return UserBallance::findOne($uid);
    }

    private function userExistsWithEception($uid)
    {
        if (!$this->userExists($uid))
        {
            throw(new ServerErrorHttpException('User does not exist'));
        }
    }

    private function sendRequest($msg)
    {
        $response = yii::$app->getResponse();
        $response->data = [
            'code' => 0,
            'message' => $msg,
        ];

        $response->setStatusCode(500);
        $response->send();
    }

    /**
     * Проверяет права текущего пользователя.
     *
     * Этот метод должен быть переопределен, чтобы проверить, имеет ли текущий пользователь
     * право выполнения указанного действия над указанной моделью данных.
     * Если у пользователя нет доступа, следует выбросить исключение [[ForbiddenHttpException]].
     *
     * @param string $action ID действия, которое надо выполнить
     * @param \yii\base\Model $model модель, к которой нужно получить доступ. Если null, это означает, что модель, к которой нужно получить доступ, отсутствует.
     * @param array $params дополнительные параметры
     * @throws ForbiddenHttpException если у пользователя нет доступа
     */
    public function checkAccess($action, $model = null, $params = [])
    {
        // проверить, имеет ли пользователь доступ к $action и $model
        // выбросить ForbiddenHttpException, если доступ следует запретить

        if (in_array($action, ['update', 'delete', 'view', 'create', 'index','options']))
        {
            throw(new ForbiddenHttpException());
        }

        return false;
    }
}