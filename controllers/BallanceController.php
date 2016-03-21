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
use app\models\BallanceHistory;
use yii\web\ForbiddenHttpException;
use yii\web\ServerErrorHttpException;


class BallanceController extends \yii\rest\ActiveController
{
    public $modelClass = 'app\models\UserBallance';

    // Exception error code
    private $errorCodes = [
        501 => 'Failed to add  new user',
        502 => 'Delete user failed',
        503 => 'Failed to add ballance to user',
        504 => 'Failed to sub ballance to user',
        505 => 'User has not enough money',
        506 => 'Transfer from user to user failed',
        507 => 'User ID format is not correct',
        508 => 'Ballance format is not correct (use decimal 19.2). (1.04 for example)',
        509 => 'User does not exist',
        510 => 'Failed to sub ballance to user - internal function call',
        511 => 'Delete user failed - non zero ballance',
        512 => 'Failed saving ballance history'
    ];

    /**
     * list of all user ballances
     * @return array|\yii\db\ActiveRecord[]
     * @throws ForbiddenHttpException
     */
    public function actionBallanceAll()
    {
        $this->checkAccess('BallaceAll', $this->modelClass);
        $model = new $this->modelClass();
        return $model::find()->all();
    }

    /**
     * return user ballance
     * @param $uid - integer user id
     * @return array|\yii\db\ActiveRecord
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     */
    public function actionBallanceUser($uid)
    {
        $this->checkAccess('BallaceUser', $this->modelClass);
        $this->userExistsWithEception($uid);

        $model = new $this->modelClass();
        return $model::findOne($uid);
    }

    /**
     * Add new user with zero ballance
     * POST params ['uid' => integer]
     * params in POST method ['uid' => integer]
     * @return array|\yii\db\ActiveRecord
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     */
    public function actionBallanceAddUser()
    {
        $this->checkAccess('BallaceAddUser', $this->modelClass);
        $uid = $this->getRequestUserId();

        if ($this->userExists($uid))
        {
            return $this->actionBallanceUser($uid);
        }
        else
        {
            $user = new $this->modelClass();
            $user->user_id = $uid;

            $connection = \Yii::$app->db;
            $transaction = $connection->beginTransaction();
            try {
                $transactionMD5 =  md5($uid.time());
                $user->save();
                $this->saveToBallanceHistory($uid, 0.00, 'user_add', $transactionMD5);
                $transaction->commit();
                return $this->actionBallanceUser($uid);
            } catch(\Exception $e) {
                $transaction->rollBack();
                throw(new ServerErrorHttpException($this->errorCodes[501] . '('.$e->getMessage().')', 501));
            }

            if ($user->hasErrors())
            {
                throw(new ServerErrorHttpException($this->errorCodes[501], 501));
            }
        }
    }

    /**
     * Delete user with zero ballance
     * @param $uid - integer - user ID
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     */
    public function actionBallanceDeleteUser($uid)
    {
        $this->checkAccess('BallanceDeleteUser', $this->modelClass);
        $this->checkUserIdFormat($uid);

        $this->userExistsWithEception($uid);

        $model = new $this->modelClass();
        $user = $model->findOne($uid);

        if ($user->ballance > 0){
            throw(new ServerErrorHttpException($this->errorCodes[511], 511));
        }

        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            $transactionMD5 =  md5($uid.time());
            $user->delete();
            $this->saveToBallanceHistory($uid, 0.00, 'user_delete', $transactionMD5);
            $transaction->commit();
            $this->sendRequest('User deleted');
        } catch(\Exception $e) {
            $transaction->rollBack();
            throw(new ServerErrorHttpException($this->errorCodes[502] . '('.$e->getMessage().')', 502));
        }


        if ($user->hasErrors())
        {
            throw(new ServerErrorHttpException($this->errorCodes[502], 502));
        }
    }

    /**
     * Add user ballance
     * POST params ['uid' => integer, 'ballance' => decimal(19.2)]
     * @param array $params ['uid' => integer, 'ballance' => decimal(19.2)]
     * @return array|\yii\db\ActiveRecord
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     */
    public function actionBallanceAdd($params = [])
    {
        $this->checkAccess('BallanceAdd', $this->modelClass);

        if (empty($params)) {
            $uid = $this->getRequestUserId();
            $balance = $this->getRequestBallance();
        }
        else
        {
            if (empty($params['uid']) || empty($params['ballance'])
                || !$this->checkUserIdFormat($params['uid'])
                || !$this->checkBallanceFormat($params['ballance'])
            )
            {
                throw(new ServerErrorHttpException($this->errorCodes[510], 510));
            }

            $uid = (int) $params['uid'];
            $balance = (double) $params['ballance'];
        }

        $this->userExistsWithEception($uid);

        $model = new $this->modelClass();
        $record = $model->findOne($uid);

        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            $transactionMD5 = !empty($params['transactionMD5']) ? $params['transactionMD5'] : md5($uid.$balance.time());
            $record->ballance = $record->ballance + $balance;
            $record->save();
            $this->saveToBallanceHistory($uid, $balance, 'ballance_add', $transactionMD5);
            $transaction->commit();
            return $this->actionBallanceUser($uid);
        } catch(\Exception $e) {
            $transaction->rollBack();
            throw(new ServerErrorHttpException($this->errorCodes[503] . '('.$e->getMessage().')', 503));
        }

        if ($record->hasErrors())
        {
            throw(new ServerErrorHttpException($this->errorCodes[503], 503));
        }
    }

    /**
     * Sub user ballance. if not enough monney throws an exception.
     * POST params ['uid' => integer, 'ballance' => decimal(19.2)]
     * @param array $params ['uid' => integer, 'ballance' => decimal(19.2)]
     * @return array|\yii\db\ActiveRecord
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     */
    public function actionBallanceSub($params = [])
    {
        $this->checkAccess('BallaceBallanceSub', $this->modelClass);

        if (empty($params)) {
            $uid = $this->getRequestUserId();
            $balance = $this->getRequestBallance();
        }
        else
        {
            if (empty($params['uid']) || empty($params['ballance'])
                || !$this->checkUserIdFormat($params['uid'])
                || !$this->checkBallanceFormat($params['ballance'])
            )
            {
                throw(new ServerErrorHttpException($this->errorCodes[510], 510));
            }

            $uid = (int) $params['uid'];
            $balance = (double) $params['ballance'];
        }

        $this->userExistsWithEception($uid);

        $model = new $this->modelClass();
        $record = $model->findOne($uid);

        if ((double) $record->ballance - (double) $balance >= 0)
        {
            $connection = \Yii::$app->db;
            $transaction = $connection->beginTransaction();
            try {
                $transactionMD5 = !empty($params['transactionMD5']) ? $params['transactionMD5'] : md5($uid.$balance.time());
                $record->ballance = $record->ballance - $balance;
                $record->save();
                $this->saveToBallanceHistory($uid, $balance, 'ballance_sub', $transactionMD5);
                $transaction->commit();
                return $this->actionBallanceUser($uid);
            } catch(\Exception $e) {
                $transaction->rollBack();
                throw(new ServerErrorHttpException($this->errorCodes[504] . '('.$e->getMessage().')', 504));
            }

            if ($record->hasErrors())
            {
                throw(new ServerErrorHttpException($this->errorCodes[504], 504));
            }
        }
        else
        {
            throw(new ServerErrorHttpException($this->errorCodes[505], 505));
        }
    }

    /**
     * Transfer money from user to user.
     * POST params ['uid' => integer, 'ballance' => decimal(19.2), 'uid2' => integer,]
     * @return array|\yii\db\ActiveRecord[]
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     * @throws \yii\db\Exception
     */
    public function actionBallanceTransfer()
    {
        $this->checkAccess('BallanceTransfer', $this->modelClass);
        $uid1 = $this->getRequestUserId();
        $uid2 = $this->getRequestUserId('uid2');
        $balance = $this->getRequestBallance();

        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            $transactionMD5 = md5($uid1.$uid2.$balance.time());
            $this->actionBallanceSub(['uid' => $uid1, 'ballance' => $balance, 'transactionMD5' => $transactionMD5]);
            $this->actionBallanceAdd(['uid' => $uid2, 'ballance' => $balance, 'transactionMD5' => $transactionMD5]);
            $this->saveToBallanceHistory($uid1, $balance, 'ballance_transfer', $transactionMD5,$uid2);
            $transaction->commit();
            $model = new $this->modelClass();
            return $model::find()->where(['in', 'user_id',[$uid1, $uid2]])->all();
        } catch(\Exception $e) {
            $transaction->rollBack();
            throw(new ServerErrorHttpException($this->errorCodes[506] . '('.$e->getMessage().')', 506));
        }
    }


    /**
     * Get user id from request GET or POST with checking format
     * @param string $uidName - request name of user ID
     * @return int
     * @throws ServerErrorHttpException
     */
    private function getRequestUserId($uidName = 'uid')
    {
        $uid = isset($_GET[$uidName]) ? (int) $_GET[$uidName] : 0;
        $uid = !$uid
            ? (isset($_POST[$uidName])
                ? (int) $_POST[$uidName]
                : 0)
            : 0;

        $this->checkUserIdFormat($uid);

        return $uid;
    }

    /**
     * Checks user format
     * @param integer $uid - user ID
     * @return bool
     * @throws ServerErrorHttpException
     */
    private function checkUserIdFormat($uid)
    {
        if (!is_numeric($uid) || $uid < 1)
        {
            throw(new ServerErrorHttpException($this->errorCodes[507], 507));
        }

        return true;
    }

    /**
     * Get ballance from request GET or POST with checking format
     * @return decimal
     * @throws ServerErrorHttpException
     */
    private function getRequestBallance()
    {
        $ballance = isset($_POST['ballance']) ? $_POST['ballance'] : 0;

        $this->checkBallanceFormat($ballance);

        return $ballance;
    }

    /**
     * Checks ballance format
     * @param decimal $ballance
     * @return bool
     * @throws ServerErrorHttpException
     */
    private function checkBallanceFormat($ballance)
    {
        if (!is_numeric($ballance) || $ballance <= 0 || strpos($ballance, ','))
        {
            throw(new ServerErrorHttpException($this->errorCodes[508], 508));
        }

        return true;
    }

    /**
     * Cheks user exitstance
     * @param integer $uid - user ID
     * @return null|static
     */
    private function userExists($uid)
    {
        $model = new $this->modelClass();
        return $model::findOne($uid);
    }

    /**
     * Cheks user exitstance with raise an exception if it does not exist
     * @param $uid
     * @throws ServerErrorHttpException
     */
    private function userExistsWithEception($uid)
    {
        if (!$this->userExists($uid))
        {
            throw(new ServerErrorHttpException($this->errorCodes[509], 509));
        }
    }

    /**
     * Send a request with message
     * @param string $msg - message
     */
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

    private function saveToBallanceHistory($uid, $sum, $operation, $transaction = '', $uidTo = null)
    {
        $model = new BallanceHistory();
        $this->checkUserIdFormat($uid);

        if (!is_null($uidTo))
        {
            $this->checkUserIdFormat($uidTo);
        }
        else
        {
            $uidTo = 0;
        }

        if ($sum != '0.00')
        {
            $this->checkBallanceFormat($sum);
        }

        $model->user_id = $uid;
        $model->sum = $sum;
        $model->operation = $operation;
        $model->transaction_description = $transaction;
        $model->user_id_to = $uidTo;
        $model->save();

        if ($model->hasErrors())
        {
            throw(new ServerErrorHttpException($this->errorCodes[512], 512));
        }

        return true;
    }
}