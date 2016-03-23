<?php
/**
 * Created by PhpStorm.
 * User: marko
 * Date: 17.03.16
 * Time: 14:13
 */

namespace app\controllers;

use Yii;
use app\models\UserBalance;
use app\models\BalanceHistory;
use yii\web\ForbiddenHttpException;
use yii\web\ServerErrorHttpException;


class BalanceController extends \yii\rest\ActiveController
{
    public $modelClass = 'app\models\UserBalance';

    // Exception error code
    private $errorCodes = [
        501 => 'Failed to add  new user',
        502 => 'Delete user failed',
        503 => 'Failed to add balance to user',
        504 => 'Failed to sub balance to user',
        505 => 'User has not enough money',
        506 => 'Transfer from user to user failed',
        507 => 'User ID format is not correct',
        508 => 'Balance format is not correct (use decimal 19.2). (1.04 for example)',
        509 => 'User does not exist',
        510 => 'Failed to sub balance to user - internal function call',
        511 => 'Delete user failed - non zero balance',
        512 => 'Failed saving balance history'
    ];

    /**
     * list of all user balances
     * @return array|\yii\db\ActiveRecord[]
     * @throws ForbiddenHttpException
     */
    public function actionBalanceAll()
    {
        $this->checkAccess('BalaceAll', $this->modelClass);
        $model = new $this->modelClass();
        return $model::find()->all();
    }

    /**
     * return user balance
     * @param $uid - integer user id
     * @return array|\yii\db\ActiveRecord
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     */
    public function actionBalanceUser($uid)
    {
        $this->checkAccess('BalaceUser', $this->modelClass);
        $this->userExistsWithEception($uid);

        $model = new $this->modelClass();
        return $model::findOne($uid);
    }

    /**
     * Add new user with zero balance
     * POST params ['uid' => integer]
     * params in POST method ['uid' => integer]
     * @return array|\yii\db\ActiveRecord
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     */
    public function actionBalanceAddUser()
    {
        $this->checkAccess('BalaceAddUser', $this->modelClass);
        $uid = $this->getRequestUserId();

        if ($this->userExists($uid))
        {
            return $this->actionBalanceUser($uid);
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
                $this->saveToBalanceHistory($uid, 0.00, 'user_add', $transactionMD5);
                $transaction->commit();
                return $this->actionBalanceUser($uid);
            } catch(\Exception $e) {
                $transaction->rollBack();
                throw(new ServerErrorHttpException($this->errorCodes[501] . '('.$e->getMessage().')', 501));
            }
        }
    }

    /**
     * Delete user with zero balance
     * @param $uid - integer - user ID
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     */
    public function actionBalanceDeleteUser($uid)
    {
        $this->checkAccess('BalanceDeleteUser', $this->modelClass);
        $this->checkUserIdFormat($uid);

        $this->userExistsWithEception($uid);

        $model = new $this->modelClass();
        $user = $model->findOne($uid);

        if ($user->balance > 0){
            throw(new ServerErrorHttpException($this->errorCodes[511], 511));
        }

        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            $transactionMD5 =  md5($uid.time());
            $user->delete();
            $this->saveToBalanceHistory($uid, 0.00, 'user_delete', $transactionMD5);
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
     * Add user balance
     * POST params ['uid' => integer, 'balance' => decimal(19.2)]
     * @param array $params ['uid' => integer, 'balance' => decimal(19.2)]
     * @return array|\yii\db\ActiveRecord
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     */
    public function actionBalanceAdd($params = [])
    {
        $this->checkAccess('BalanceAdd', $this->modelClass);

        if (empty($params)) {
            $uid = $this->getRequestUserId();
            $balance = $this->getRequestBalance();
        }
        else
        {
            if (empty($params['uid']) || empty($params['balance'])
                || !$this->checkUserIdFormat($params['uid'])
                || !$this->checkBalanceFormat($params['balance'])
            )
            {
                throw(new ServerErrorHttpException($this->errorCodes[510], 510));
            }

            $uid = (int) $params['uid'];
            $balance = (double) $params['balance'];
        }

        $this->userExistsWithEception($uid);

        $model = new $this->modelClass();
        $record = $model->findOne($uid);

        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            $transactionDesc = !empty($_POST['trans_desc']) ? $_POST['trans_desc'] :
                (!empty($params['transactionDesc']) ? $params['transactionDesc'] : md5($uid.$balance.time()));
            $record->balance = $record->balance + $balance;
            $record->save();
            $this->saveToBalanceHistory($uid, $balance, 'balance_add', $transactionDesc);
            $transaction->commit();
            return $this->actionBalanceUser($uid);
        } catch(\Exception $e) {
            $transaction->rollBack();
            throw(new ServerErrorHttpException($this->errorCodes[503] . '('.$e->getMessage().')', 503));
        }
    }

    /**
     * Sub user balance. if not enough monney throws an exception.
     * POST params ['uid' => integer, 'balance' => decimal(19.2)]
     * @param array $params ['uid' => integer, 'balance' => decimal(19.2)]
     * @return array|\yii\db\ActiveRecord
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     */
    public function actionBalanceSub($params = [])
    {
        $this->checkAccess('BalanceSub', $this->modelClass);

        if (empty($params)) {
            $uid = $this->getRequestUserId();
            $balance = $this->getRequestBalance();
        }
        else
        {
            if (empty($params['uid']) || empty($params['balance'])
                || !$this->checkUserIdFormat($params['uid'])
                || !$this->checkBalanceFormat($params['balance'])
            )
            {
                throw(new ServerErrorHttpException($this->errorCodes[510], 510));
            }

            $uid = (int) $params['uid'];
            $balance = (double) $params['balance'];
        }

        $this->userExistsWithEception($uid);

        $model = new $this->modelClass();
        $record = $model->findOne($uid);

        if ((double) $record->balance - (double) $balance >= 0)
        {
            $connection = \Yii::$app->db;
            $transaction = $connection->beginTransaction();
            try {
                $transactionDesc = !empty($_POST['trans_desc']) ? $_POST['trans_desc'] :
                    (!empty($params['transactionDesc']) ? $params['transactionDesc'] : md5($uid.$balance.time()));
                $record->balance = $record->balance - $balance;
                $record->save();
                $this->saveToBalanceHistory($uid, $balance, 'balance_sub', $transactionDesc);
                $transaction->commit();
                return $this->actionBalanceUser($uid);
            } catch(\Exception $e) {
                $transaction->rollBack();
                throw(new ServerErrorHttpException($this->errorCodes[504] . '('.$e->getMessage().')', 504));
            }
        }
        else
        {
            throw(new ServerErrorHttpException($this->errorCodes[505], 505));
        }
    }

    /**
     * Transfer money from user to user.
     * POST params ['uid' => integer, 'balance' => decimal(19.2), 'uid2' => integer,]
     * @return array|\yii\db\ActiveRecord[]
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     * @throws \yii\db\Exception
     */
    public function actionBalanceTransfer()
    {
        $this->checkAccess('BalanceTransfer', $this->modelClass);
        $uid1 = $this->getRequestUserId();
        $uid2 = $this->getRequestUserId('uid2');
        $balance = $this->getRequestBalance();

        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            $transactionDesc = !empty($_POST['trans_desc']) ? $_POST['trans_desc'] : md5($uid1.$uid2.$balance.time());
            $this->actionBalanceSub(['uid' => $uid1, 'balance' => $balance, 'transactionDesc' => $transactionDesc]);
            $this->actionBalanceAdd(['uid' => $uid2, 'balance' => $balance, 'transactionDesc' => $transactionDesc]);
            $this->saveToBalanceHistory($uid1, $balance, 'balance_transfer', $transactionDesc,$uid2);
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
     * Get balance from request GET or POST with checking format
     * @return decimal
     * @throws ServerErrorHttpException
     */
    private function getRequestBalance()
    {
        $balance = isset($_POST['balance']) ? $_POST['balance'] : 0;

        $this->checkBalanceFormat($balance);

        return $balance;
    }

    /**
     * Checks balance format
     * @param decimal $balance
     * @return bool
     * @throws ServerErrorHttpException
     */
    private function checkBalanceFormat($balance)
    {
        if (!is_numeric($balance) || $balance <= 0 || strpos($balance, ','))
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

    private function saveToBalanceHistory($uid, $sum, $operation, $transaction = '', $sender_id = null)
    {
        $model = new BalanceHistory();
        $this->checkUserIdFormat($uid);

        if (!is_null($sender_id))
        {
            $this->checkUserIdFormat($sender_id);
        }
        else
        {
            $sender_id = 0;
        }

        if ($sum != '0.00')
        {
            $this->checkBalanceFormat($sum);
        }

        $model->receiver_id = $uid;
        $model->sum = $sum;
        $model->operation = $operation;
        $model->transaction_description = $transaction;
        $model->sender_id = $sender_id;
        $model->save();

        if ($model->hasErrors())
        {
            throw(new ServerErrorHttpException($this->errorCodes[512], 512));
        }

        return true;
    }
}