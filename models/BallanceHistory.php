<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ballance_history".
 *
 * @property integer $id
 * @property string $date
 * @property integer $user_id
 * @property string $sum
 * @property string $operation
 * @property string $transaction_description
 * @property integer $user_id_to
 */
class BallanceHistory extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ballance_history';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['date'], 'safe'],
            [['user_id', 'user_id_to'], 'integer'],
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
            'user_id' => 'User ID',
            'sum' => 'Sum',
            'operation' => 'Operation',
            'transaction_description' => 'Transaction Description',
            'user_id_to' => 'User Id To',
        ];
    }
}