<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EmailTable extends Migration
{
    public function up()
    {
        //Criação da tabela email
        $this->forge->addField([
            'id' => [
                'type'=> 'INT',
                'auto_increment' => true
            ],
            'id_contatct' => [
                'type'=> 'INT',
            ],
            'email' => [
                'type'=> 'VARCHAR',
                'constraint' => '100',
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('id_contatct');
        
        $this->forge->createTable('email');
        $this->forge->processIndexes('email');
    }

    public function down()
    {
        //
    }
}
