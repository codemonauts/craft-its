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
            'subject' => $this->string(),
            'status' => $this->string(20),
            'typeId' => $this->integer()->notNull(),
            'deletedWithIssueType' => $this->boolean()->null(),
            'creatorId' => $this->integer()->null(),
            'ownerId' => $this->integer()->null(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'dateDeleted' => $this->dateTime()->null(),
            'PRIMARY KEY([[id]])',
        ]);

        $this->dropTableIfExists('{{%its_history}}');
        $this->createTable('{{%its_history}}', [
            'id' => $this->primaryKey(),
            'issueId' => $this->integer()->notNull(),
            'event' => $this->string(),
            'initiatorName' => $this->string(),
            'initiatorId' => $this->integer()->null(),
            'data' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->dropTableIfExists('{{%its_issuetypes}}');
        $this->createTable('{{%its_issuetypes}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'fieldLayoutId' => $this->integer(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'dateDeleted' => $this->dateTime()->null(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, '{{%its_issues}}', ['status']);
        $this->createIndex(null, '{{%its_issues}}', ['typeId']);
        $this->createIndex(null, '{{%its_issues}}', ['ownerId']);
        $this->createIndex(null, '{{%its_issues}}', ['creatorId']);
        $this->createIndex(null, '{{%its_history}}', ['issueId', 'dateCreated']);
        $this->createIndex(null, '{{%its_issuetypes}}', ['fieldLayoutId']);

        $this->addForeignKey(null, '{{%its_issues}}', ['id'], Table::ELEMENTS, ['id'], 'CASCADE');
        $this->addForeignKey(null, '{{%its_issues}}', ['typeId'], '{{%its_issuetypes}}', ['id'], 'CASCADE');
        $this->addForeignKey(null, '{{%its_history}}', ['issueId'], Table::ELEMENTS, ['id'], 'CASCADE');
        $this->addForeignKey(null, '{{%its_history}}', ['initiatorId'], Table::ELEMENTS, ['id'], 'SET NULL');
        $this->addForeignKey(null, '{{%its_issuetypes}}', ['fieldLayoutId'], Table::FIELDLAYOUTS, ['id'], 'SET NULL');
    }

    public function safeDown()
    {
        $this->dropTableIfExists('{{%its_issues}}');
        $this->dropTableIfExists('{{%its_history}}');
        $this->dropTableIfExists('{{%its_issuetypes}}');
    }
}
