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
 * This is the model class for table "ballance".
 *
 * @property integer $user_id
 * @property string $ballance
 */
class Ballance extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ballance';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id'], 'integer'],
            [['ballance'], 'number']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'user_id' => 'User ID',
            'ballance' => 'Ballance',
        ];
    }
}