<?php

use yii\db\Migration;

class m160317_085938_create_balance_history_table extends Migration
{
    public function up()
    {
        $this->createTable('balance_history', [
            'id' => $this->primaryKey(),
            'date' => $this->timestamp(),
            'receiver_id' => $this->integer(11),
            'sum' => $this->decimal('19,2'),
            'operation' => $this->string(),
            'transaction_description' => $this->text(),
            'sender_id' => $this->integer(11)
        ]);
    }

    public function down()
    {
        $this->dropTable('balance_history');
    }
}
