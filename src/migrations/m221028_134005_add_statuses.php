<?php

namespace codemonauts\its\migrations;

use craft\db\Migration;

/**
 * m221028_134005_add_statuses migration.
 */
class m221028_134005_add_statuses extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn('{{%its_issuetypes}}', 'statuses', $this->text()->after('handle')->null());
        $this->renameColumn('{{%its_issues}}', 'status', 'state');

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropColumn('{{%its_issuetypes}}', 'statuses');
        $this->renameColumn('{{%its_issues}}', 'state', 'status');

        return true;
    }
}
