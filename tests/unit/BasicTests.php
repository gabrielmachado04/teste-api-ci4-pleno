<?php

use CodeIgniter\Test\FeatureTestCase ;
use App\Controllers\Contacts;
use Config\Services;

/**
 * @internal
 */
final class BasicTests extends FeatureTestCase 
{
    //Teste de listagem de todos os contatos
    public function testIndex(): void
    {
        $response = $this->get('/contacts', []);
        $response->assertStatus(200);
        $response->assertJSONFragment(['success' => 'true']);
    }

    //Teste de deleção de um contato
    public function testDelete(): void
    {
        $response = $this->delete('/contacts/1', []);
        $response->assertStatus(200);
        $response->assertJSONFragment(['success' => 'true']);
    }

    //Teste simples de inserção de um novo contato
    public function testInsert()
    {           
        $curl = curl_init('http://localhost/teste-api-ci4-pleno/public/contacts');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode([
            "name" => "João da Silva",
            "description" => "Contato de exemplo para testes na API",
            "zip_code" => "36703-000",
            "country" => "Brasil",
            "state" => "SP",
            "street_address" => "Rua Exemplo",
            "address_number" => "123",
            "city" => "São Paulo",
            "address_line" => "Apartamento 45",
            "neighborhood" => "Bela Vista",
            "phone" => "11999998888",
            "email" => "joao.silva@example.com"
        ]));
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($curl);

        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        $response = json_decode($response);

        $this->assertEquals($response->success, 1);
        $this->assertEquals($status_code, 201);
    }

    //Teste de inserção de um contato com zip_code inválido pelo ViaCep
    public function testInsertZipInvalid()
    {           
        $curl = curl_init('http://localhost/teste-api-ci4-pleno/public/contacts');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode([
            "name" => "João da Silva",
            "description" => "Contato de exemplo para testes na API",
            "zip_code" => "367030000",
            "country" => "Brasil",
            "state" => "SP",
            "street_address" => "Rua Exemplo",
            "address_number" => "123",
            "city" => "São Paulo",
            "address_line" => "Apartamento 45",
            "neighborhood" => "Bela Vista",
            "phone" => "11999998888",
            "email" => "joao.silva@example.com"
        ]));
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($curl);

        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        $response = json_decode($response);
        var_dump($response);

        $this->assertEquals($response->success, 0);
        $this->assertEquals($status_code, 400);
        $this->assertEquals($response->message, 'Invalid zip code from ViaCep');
    }

    //Teste de update de um contato
    public function testUpdate()
    {           
        
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://localhost/teste-api-ci4-pleno/public/contacts/4',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS =>'{
            "name": "João da Silva Santos",
            "description": "Contato de exemplo para testes na API",

            "zip_code": "36703-000",
            "country": "Brasil",
            "state": "SP",
            "street_address": "Rua Exemplo",
            "address_number": "123",
            "city": "São Paulo",
            "address_line": "Apartamento 45",
            "neighborhood": "Bela Vista",

            "phone": "11999998888",
            "email": "joao.silva@example.com"
        }',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);
        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);
        
        $response = json_decode($response);
        var_dump($response);

        $this->assertEquals($response->success, 1);
        $this->assertEquals($status_code, 200);
    }
}