<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddressTable extends Migration
{
    public function up()
    {
        //Criação da tabela address
        $this->forge->addField([
            'id' => [
                'type'=> 'INT',
                'auto_increment' => true
            ],
            'id_contatct' => [
                'type'=> 'INT',
            ],
            'zip_code' => [
                'type'=> 'VARCHAR',
                'constraint' => '10',
            ],
            'country' => [
                'type'=> 'VARCHAR',
                'constraint' => '100',
            ],
            'state' => [
                'type'=> 'VARCHAR',
                'constraint' => '100',
            ],
            'street_address' => [
                'type'=> 'VARCHAR',
                'constraint' => '100',
            ],
            'address_number' => [
                'type'=> 'VARCHAR',
                'constraint' => '100',
            ],
            'city' => [
                'type'=> 'VARCHAR',
                'constraint' => '100',
            ],
            'address_line' => [
                'type'=> 'VARCHAR',
                'constraint' => '100',
            ],
            'neighborhood' => [
                'type'=> 'VARCHAR',
                'constraint' => '100',
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('id_contatct');
        
        $this->forge->createTable('address');
        $this->forge->processIndexes('address');
    }

    public function down()
    {
        //
    }
}
