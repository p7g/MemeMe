<?php

use Phinx\Migration\AbstractMigration;

class InitialMigration extends AbstractMigration {
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
    public function change() {
        $channels = $this->table('channels', [
            'id' => false,
            'primary_key' => 'id',
        ]);
        $channels->addColumn('id', 'string')
            ->addColumn('enabled', 'boolean', ['default' => true])
            ->create();

        $users = $this->table('users', [
            'id' => false,
            'primary_key' => 'id',
        ]);
        $users->addColumn('id', 'string')
            ->addColumn('permissions', 'smallinteger', ['default' => 1]) // USAGE
            ->addColumn('channel_id', 'string')
            ->addForeignKey('channel_id', 'channels', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->create();
    }
}
