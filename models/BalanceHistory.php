<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "balance_history".
 *
 * @property integer $id
 * @property string $date
 * @property integer $user_id
 * @property string $sum
 * @property string $operation
 * @property string $transaction_description
 * @property integer $user_id_to
 */
class BalanceHistory extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'balance_history';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['date'], 'safe'],
            [['receiver_id', 'sender_id'], 'integer'],
            [['sum'], 'number'],
            [['transaction_description'], 'string'],
            [['operation'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date' => 'Date',
            'receiver_id' => 'Receiver ID',
            'sum' => 'Sum',
            'operation' => 'Operation',
            'transaction_description' => 'Transaction Description',
            'sender_id' => 'Sender Id To',
        ];
    }
}