<?php

use yii\db\Migration;

class m160317_082204_create_user_ballance_table extends Migration
{
    public function up()
    {
        $this->createTable('user_ballance', [
            'user_id' => $this->primaryKey(),
            'ballance' => $this->decimal('19, 2')
        ]);
    }

    public function down()
    {
        $this->dropTable('user_ballance');
    }
}
