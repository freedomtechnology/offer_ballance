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
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\UnprocessableEntityHttpException;


class BalanceController extends \yii\rest\ActiveController
{
    public $modelClass = 'app\models\UserBalance';

    // Exception error code
    private $errorCodes = [
        501 => 'Failed to add  new user',
        502 => 'Delete user failed',
        503 => 'Failed to add/sub balance to user',
        504 => 'Operation type mismatch. Failed to add/sub balance to user',
        505 => 'User has not enough money',
        506 => '',
        507 => 'User ID format is not correct',
        508 => 'Balance format is not correct (use decimal 19.2). (1.04 for example)',
        509 => 'User does not exist',
        510 => 'Failed to add/sub balance to user - internal function call',
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
        $access = $this->checkAccess('BalaceUser', $this->modelClass);
        $this->userExistsWithEception($uid);

        if ($uid != $access['userId'] && !$access['admin']) {
            throw(new ForbiddenHttpException());
        }

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
    public function actionAddUser()
    {
        $this->checkAccess('AddUser', $this->modelClass);
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
    public function actionDeleteUser($uid)
    {
        $this->checkAccess('DeleteUser', $this->modelClass);
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
    public function actionBalanceAddSub($params = [])
    {
        $this->checkAccess('BalanceAddSub', $this->modelClass);

        $request = Yii::$app->request;

        if (empty($params)) {
            $uid = $this->getRequestUserId();
            $balance = $this->getRequestBalance();
            $operation = $request->post('operation', '');
            $transactionDesc = $request->post('trans_desc', '');
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
            $operation = !empty($params['operation']) ? $params['operation'] : '';
            $transactionDesc = !empty($params['transactionDesc']) ? $params['transactionDesc'] : '';
        }

        $transactionDesc = !empty($transactionDesc) ? $transactionDesc : md5($uid.$balance.time());

        $this->userExistsWithEception($uid);

        $model = new $this->modelClass();
        $record = $model->findOne($uid);

        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();

        try {

            switch ($operation)
            {
                case 'add':
                {
                    $record->balance = $record->balance + $balance;
                    break;
                }

                case 'sub':
                {
                    if ((double) $record->balance - (double) $balance >= 0)
                    {
                            $record->balance = $record->balance - $balance;
                    }
                    else
                    {
                        throw(new ServerErrorHttpException($this->errorCodes[505], 505));
                    }
                    break;
                }

                default:
                    throw(new UnprocessableEntityHttpException($this->errorCodes[504], 504));
            }

            $record->save();

            if ($record->hasErrors())
            {
                throw(new ServerErrorHttpException($this->errorCodes[503], 503));
            }

            $this->saveToBalanceHistory($uid, $balance, 'balance_'.$operation, $transactionDesc);
            $transaction->commit();
            return $this->actionBalanceUser($uid);
        } catch(\Exception $e) {
            $transaction->rollBack();
            throw(new ServerErrorHttpException($e->getMessage(), $e->getCode()));
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
        $request = Yii::$app->request;
        $senderId = $this->getRequestUserId('sender_id');
        $receiverId = $this->getRequestUserId('receiver_id');
        $balance = $this->getRequestBalance();
        $transactionDesc = $request->post('trans_desc', md5($senderId . $receiverId . $balance . time()));

        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            $this->actionBalanceAddSub(['operation' => 'sub', 'uid' => $senderId, 'balance' => $balance, 'transactionDesc' => $transactionDesc]);
            $this->actionBalanceAddSub(['operation' => 'add', 'uid' => $receiverId, 'balance' => $balance, 'transactionDesc' => $transactionDesc]);
            $this->saveToBalanceHistory($senderId, $balance, 'balance_transfer', $transactionDesc,$receiverId);
            $transaction->commit();
            $model = new $this->modelClass();
            return $model::find()->where(['in', 'user_id',[$senderId, $receiverId]])->all();
        } catch(\Exception $e) {
            $transaction->rollBack();
            throw(new ServerErrorHttpException($e->getMessage(), $e->getCode()));
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
        $request = Yii::$app->request;
        $uid = (int) $request->get($uidName, 0);
        $uid = !$uid ? (int) $request->post($uidName, 0) : $uid;

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
            throw(new UnprocessableEntityHttpException($this->errorCodes[507], 507));
        }

        return true;
    }

    /**
     * Get balance from request GET or POST with checking format
     * @return float decimal
     * @throws ServerErrorHttpException
     */
    private function getRequestBalance()
    {
        $request = Yii::$app->request;
        $balance = $request->post('balance', 0);

        $this->checkBalanceFormat($balance);

        return $balance;
    }

    /**
     * Checks balance format
     * @param float decimal $balance
     * @return bool
     * @throws ServerErrorHttpException
     */
    private function checkBalanceFormat($balance)
    {
        if (!is_numeric($balance) || $balance <= 0 || strpos($balance, ','))
        {
            throw(new UnprocessableEntityHttpException($this->errorCodes[508], 508));
        }

        return true;
    }

    /**
     * Cheks user exitstance
     * @param integer $uid - user ID
     * @return array|\yii\db\ActiveRecord
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
     * @param int $status
     * @param int $code
     */
    private function sendRequest($msg, $status = 200, $code = 0)
    {
        $response = yii::$app->getResponse();
        $response->data = [
            'code' => $code,
            'message' => $msg,
        ];

        $response->setStatusCode($status);
        $response->send();
    }

    /**
     * Saving balance operation to history table
     * @param integer $uid
     * @param float decimal $sum
     * @param string $operation
     * @param string $transaction
     * @param integer $sender_id
     * @return bool
     * @throws ServerErrorHttpException
     * @throws UnprocessableEntityHttpException
     */
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