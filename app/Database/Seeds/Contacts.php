<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Faker\Factory;

class Contacts extends Seeder
{
    public function run()
    {
        //Gerando dados fake para popular as tabelas
        $faker = Factory::create();   
        for ($i = 0; $i < 10; $i++) {
            $name        = $faker->name;
            $description = $faker->text;

            $zip_code       = $faker->postcode;
            $country        = $faker->country;
            $state          = $faker->state;
            $street_address = $faker->streetAddress;
            $address_number = $faker->address;
            $city           = $faker->city;
            $address_line1  = $faker->address;
            $neighborhood   = $faker->address;

            $email = $faker->email;

            $phone = $faker->phoneNumber;
            
            //Populando a tabela contacts
            $this->db->table("contacts")->insert([
                'name' => $name,
                'description'=> $description
            ]);

            $id_contact = $this->db->insertID();

            //Populando a tabela address
            $this->db->table("address")->insert([
                'id_contatct ' => $id_contact,
                'zip_code' => $zip_code,
                'country'=> $country,
                'state'=> $state,
                'street_address'=> $street_address,
                'address_number'=> $address_number,
                'city'=> $city,
                'address_line'=> $address_line1,
                'neighborhood'=> $neighborhood,
            ]);

            //Populando a tabela email
            $this->db->table("email")->insert([
                'id_contatct ' => $id_contact,
                'email'        => $email,
            ]);

            //Populando a tabela phone
            $this->db->table("phone")->insert([
                'id_contatct ' => $id_contact,
                'phone'        => $phone,
            ]);
        }
    }
}
