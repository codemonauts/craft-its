<?php

namespace codemonauts\its\migrations;

use craft\db\Migration;
use craft\db\Table;

class Install extends Migration
{
    public function safeUp()
    {
        $this->dropTableIfExists('{{%its_issues}}');
        $this->createTable('{{%its_issues}}', [
            'id' => $this->integer()->notNull(),
            'subject' => $this->string()->notNull(),
            'status' => $this->string(20)->null(),
            'ownerId' => $this->integer()->null(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'dateDeleted' => $this->dateTime()->null(),
            'PRIMARY KEY([[id]])',
        ]);

        $this->createIndex(null, '{{%its_issues}}', ['status']);
        $this->createIndex(null, '{{%its_issues}}', ['ownerId']);
        $this->addForeignKey(null, '{{%its_issues}}', ['id'], Table::ELEMENTS, ['id'], 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropTableIfExists('{{%its_issues}}');
    }
}
