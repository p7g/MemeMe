<?php


use Phinx\Migration\AbstractMigration;

class ChangeUserPrimaryKey extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    addCustomColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Any other destructive changes will result in an error when trying to
     * rollback the migration.
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $this->table('users')->drop()->save();

        $this->table('permissions')
            ->addColumn('user_id', 'string', ['null' => false])
            ->addColumn('channel_id', 'string', ['null' => false])
            ->addColumn('permissions', 'smallinteger', ['default' => 1])
            ->addIndex(['user_id', 'channel_id'], ['unique' => true])
            ->addForeignKey('channel_id', 'channels', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->create();
    }
}
