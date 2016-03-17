<?php

use yii\db\Migration;

class m160317_085938_create_ballance_history_table extends Migration
{
    public function up()
    {
        $this->createTable('ballance_history', [
            'id' => $this->primaryKey(),
            'date' => $this->timestamp(),
            'user_id' => $this->integer(11),
            'sum' => $this->decimal('19,2'),
            'operation' => $this->string(),
            'transaction_description' => $this->text(),
            'user_id_to' => $this->integer(11)
        ]);
    }

    public function down()
    {
        $this->dropTable('ballance_history');
    }
}
