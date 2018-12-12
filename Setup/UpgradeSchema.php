<?php declare(strict_types=1);

namespace Magento\MysqlMq\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface as Db;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * Backport of etc/db_schema.xml
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    const VARCHAR_LENGTH = 255;
    const MIN_LONGTEXT_LENGTH = 16777217;

    /**
     * @throws \Zend_Db_Exception
     */
    private function createQueueTable(SchemaSetupInterface $setup): void
    {
        $tableName = 'queue';
        $table = $setup->getConnection()->newTable($setup->getTable($tableName));
        $table->addColumn('id', Table::TYPE_INTEGER, 10, [
            'unsigned' => true,
            'nullable' => false,
            'identity' => true,
            'primary'  => true,
        ]);
        $table->addColumn('name', Table::TYPE_TEXT, self::VARCHAR_LENGTH, ['nullable' => true]);
        $table->addIndex(
            $setup->getIdxName($tableName, ['name'], Db::INDEX_TYPE_UNIQUE),
            ['name'],
            ['type' => Db::INDEX_TYPE_UNIQUE]
        );
        $setup->getConnection()->createTable($table);
    }

    /**
     * @throws \Zend_Db_Exception
     */
    private function createQueueMessageTable(SchemaSetupInterface $setup): void
    {
        $tableName = 'queue_message';
        $table = $setup->getConnection()->newTable($setup->getTable($tableName));
        $table->addColumn('id', Table::TYPE_BIGINT, 20, [
            'unsigned' => true,
            'nullable' => false,
            'identity' => true,
            'primary'  => true,
        ]);
        $table->addColumn('topic_name', Table::TYPE_TEXT, self::VARCHAR_LENGTH, ['nullable' => true]);
        $table->addColumn('body', Table::TYPE_TEXT, self::MIN_LONGTEXT_LENGTH, ['nullable' => true]);
        $setup->getConnection()->createTable($table);
    }

    /**
     * @throws \Zend_Db_Exception
     */
    private function createQueueMessageStatusTable(SchemaSetupInterface $setup): void
    {
        $tableName = 'queue_message_status';
        $table = $setup->getConnection()->newTable($setup->getTable($tableName));
        $table->addColumn('id', Table::TYPE_BIGINT, 20, [
            'unsigned' => true,
            'nullable' => false,
            'identity' => true,
            'primary'  => true,
        ]);
        $table->addColumn('queue_id', Table::TYPE_INTEGER, 10, ['unsigned' => true, 'nullable' => false]);
        $table->addColumn('message_id', Table::TYPE_BIGINT, 20, ['unsigned' => true, 'nullable' => false]);
        $table->addColumn('updated_at', Table::TYPE_TIMESTAMP, null, [
            'nullable' => false,
            'default'  => Table::TIMESTAMP_INIT_UPDATE,
        ]);
        $table->addColumn('status', Table::TYPE_SMALLINT, 5, ['unsigned' => true, 'nullable' => false]);
        $table->addColumn('number_of_trials', Table::TYPE_SMALLINT, 5, [
            'unsigned' => true,
            'nullable' => false,
            'default'  => 0,
        ]);
        $table->addForeignKey(
            $setup->getFkName('queue_message_status', 'message_id', 'queue_message', 'id'),
            'message_id',
            'queue_message',
            'id',
            Db::FK_ACTION_CASCADE
        );
        $table->addForeignKey(
            $setup->getFkName('queue_message_status', 'queue_id', 'queue', 'id'),
            'queue_id',
            'queue',
            'id',
            Db::FK_ACTION_CASCADE
        );
        $table->addIndex(
            $setup->getIdxName('queue_message_status', ['queue_id', 'message_id'], Db::INDEX_TYPE_UNIQUE),
            ['queue_id', 'message_id'],
            ['type' => Db::INDEX_TYPE_UNIQUE]
        );
        $table->addIndex(
            $setup->getIdxName('queue_message_status', ['status', 'updated_at']),
            ['status', 'updated_at']
        );
        $setup->getConnection()->createTable($table);
    }

    /**
     * @throws \Zend_Db_Exception
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '2.0.0', '<')) {
            $this->createQueueTable($setup);
            $this->createQueueMessageTable($setup);
            $this->createQueueMessageStatusTable($setup);
        }
    }
}
