<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ContactsTables extends Migration
{
    public function up()
    {
        //Criação da tabela contacts
        $this->forge->addField([
            'id' => [
                'type'=> 'INT',
                'auto_increment' => true
            ],
            'name' => [
                'type'=> 'VARCHAR',
                'constraint' => '50',
            ],
            'description' => [
                'type'=> 'VARCHAR',
                'constraint' => '100',
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('contacts');
    }

    public function down()
    {
        //
    }
}
