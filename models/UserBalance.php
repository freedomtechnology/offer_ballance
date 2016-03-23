<?php
/**
 * Created by PhpStorm.
 * User: marko
 * Date: 17.03.16
 * Time: 14:11
 */

namespace app\models;

use Yii;

/**
 * This is the model class for table "balance".
 *
 * @property integer $user_id
 * @property string $balance
 */
class UserBalance extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_balance';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id'], 'integer'],
            [['balance'], 'number']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'user_id' => 'User ID',
            'balance' => 'Balance',
        ];
    }
}