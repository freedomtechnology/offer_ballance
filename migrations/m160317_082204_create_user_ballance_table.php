<?php

use yii\db\Migration;

class m160317_082204_create_user_ballance_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('user_ballance', [
            //'user_id' => $this->primaryKey(),
            'user_id' => $this->integer(11),
            'ballance' => $this->decimal('19, 2')->defaultValue('0.00')
        ]);
        $this->addPrimaryKey('user_id', 'user_ballance', ['user_id']);
    }

    public function safeDown()
    {
        $this->dropTable('user_ballance');
    }
}
