<?php

namespace App\Controllers;

use App\Controllers\BaseController;

use App\Models\ContactsModel;
use App\Models\AddressModel;
use App\Models\EmailModel;
use App\Models\PhoneModel;
use Predis\Client;

class Contacts extends BaseController
{   
    private $contactsModel;
    private $addressModel;
    private $phoneModel;
    private $emailModel;
    private $isFromCache = false;
    private $cache;

    //Carregando Model para ser utilizado em todas as funções.
    public function __construct() 
    {
        $this->contactsModel = new ContactsModel();
        $this->cache = new Client();
    }

    //Função para calcular o tempo de processamento
    public function calculate_processing_time($time_start=0)
    {
        return round(microtime(true) - $time_start, 4);
    }

    //Função para retornar todos os contatos
    public function index()
    {   
        //Armazena a data de início do processamento
        $time_start = microtime(true);

        //Busca pelo cache com a última consulta
        $data_cache = $this->cache->get('consulta_sql_cache');

        //Verifica se existe algum cache dessa request
        if (!$data_cache) {
            $this->addressModel = new AddressModel();
            $this->emailModel   = new EmailModel();
            $this->phoneModel   = new PhoneModel();

            //Busca por todos os contatos existentes
            $data_contacts = $this->contactsModel->findAll();
            
            //Geração de um campo contendo as informações dos contatos e suas informações adicionais
            $all_contacts = [];
            foreach ($data_contacts as $contact)
            {
                $contact_id = $contact['id'];
                $contact_name = $contact['name'];
                $contact_description = $contact['description'];

                //Busca pelas informações adicionais do contato
                $data_address = $this->addressModel->where('id_contatct', $contact_id)->first();
                $data_email   = $this->emailModel->where('id_contatct', $contact_id)->first();
                $data_phone   = $this->phoneModel->where('id_contatct', $contact_id)->first();

                //Reune todas essas informações para serem exibidas juntas
                $all_contacts[] = [
                    'id'             => $contact_id,
                    'name'           => $contact_name,
                    'description'    => $contact_description,

                    'zip_code'       => $data_address['zip_code'],
                    'country'        => $data_address['country'],
                    'state'          => $data_address['state'],
                    'street_address' => $data_address['street_address'],
                    'address_number' => $data_address['address_number'],
                    'city'           => $data_address['city'],
                    'address_line'   => $data_address['address_line'],
                    'neighborhood'   => $data_address['neighborhood'],

                    'phone'          => $data_phone['phone'],
                    'email'          => $data_email['email'],
                ];
            }
            //Armazena as informações em cache por 3 minutos
            $this->isFromCache = false;
            $this->cache->setex('consulta_sql_cache', 180, json_encode($all_contacts));
        }else{
            //Caso exista um cache, utiliza essas informações sem precisar consultar
            $this->isFromCache = true;
            $all_contacts = json_decode($data_cache);
        }

        //Retorna todas as informações dos contatos
        return $this->response->setStatusCode(200)->setJSON([
            'success' => true,
            'contacts'=> $all_contacts,
            'isFromCache' => $this->isFromCache,
            'processing_time'=> $this->calculate_processing_time($time_start),
        ]);
    }

    //Função para inserir um novo contato
    public function insert()
    {
        //Armazena a data de início do processamento
        $time_start = microtime(true);

        $data = $this->request->getJSON(); 
        
        //Verifica e valida todos os campos obrigatórios
        if(!$this->validate($this->contactsModel->validationRules))
        {
            return $this->response->setStatusCode(422)->setJSON(array(
                "success"=> false, 
                "message"=> "Validation error", 
                "Errors"=> $this->validator->getErrors(),
                'isFromCache' => $this->isFromCache,
                "processing_time" => $this->calculate_processing_time($time_start),
            ));
        }

        $db = \Config\Database::connect();
        $db->transStart();

        //Executa a inserção dos dados do contato
        $inserted = $this->contactsModel->insert($data);
        if ($inserted)
        {
            //Captura o id inserido no banco
            $inserted_id = $this->contactsModel->getInsertID();
            
            //Tabelas adicionais
            $this->addressModel = new AddressModel();
            $this->emailModel   = new EmailModel();
            $this->phoneModel   = new PhoneModel();

            //Executa as validações adicionais em cada Model secundário
            $addressValid = $this->validate($this->addressModel->validationRules);
            $emailValid   = $this->validate($this->emailModel->validationRules);
            $phoneValid   = $this->validate($this->phoneModel->validationRules);

            if(!$addressValid || !$emailValid || !$phoneValid)
            {
                //Caso alguma validação tenha falhado, desfaz as alterações no banco
                $db->transRollback();
                return $this->response->setStatusCode(422)->setJSON(array(
                    "success"=> false, 
                    "message"=> "Validation error", 
                    "Errors"=> $this->validator->getErrors(),
                    'isFromCache' => $this->isFromCache,
                    "processing_time" => $this->calculate_processing_time($time_start)
                ));
            }else{
                //Validando zip_code com a api do ViaCep
                $data_zip_code_valid = $this->addressModel->validate_viacep($data->zip_code);
                if(isset($data_zip_code_valid['erro']))
                {
                    //Caso falhe a validação pelo ViaCep, desfaz as alterações no banco
                    $db->transRollback();
                    return $this->response->setStatusCode(400)->setJSON(array(
                        "success"=> false, "message"=> 
                        "Invalid zip code from ViaCep", 
                        'isFromCache' => $this->isFromCache,
                        "processing_time" => $this->calculate_processing_time($time_start)
                    ));
                }else
                {
                    //Reune as informações de cada tabela adicional e efetua a inserção dos dados
                    $data_address = [
                        'id_contatct'    => $inserted_id,
                        'zip_code'       => $data->zip_code,
                        'country'        => $data->country,
                        'state'          => $data->state,
                        'street_address' => $data->street_address,
                        'address_number' => $data->address_number,
                        'city'           => $data->city,
                        'address_line'   => $data->address_line,
                        'neighborhood'   => $data->neighborhood,  
                    ];
                    $inserted_address = $this->addressModel->insert($data_address);
    
                    $data_phone = [
                        'id_contatct'    => $inserted_id,
                        'phone'          => $data->phone,
                    ];
                    $inserted_phone = $this->phoneModel->insert($data_phone);
    
                    $data_email = [
                        'id_contatct'    => $inserted_id,
                        'email'          => $data->email,
                    ];
                    $inserted_email = $this->emailModel->insert($data_email);
    
                    //Caso todas as inserções tenham sido realizadas com sucesso, confirma as alterações no banco
                    if($inserted_address && $inserted_phone && $inserted_email)
                    {
                        $db->transCommit();

                        $this->cache->del('consulta_sql_cache');
                        return $this->response->setStatusCode(201)->setJSON(array(
                            "success"=> true, 
                            "message"=> "Contact inserted successfully", 
                            'isFromCache' => $this->isFromCache,
                            "processing_time" => $this->calculate_processing_time($time_start)
                        ));
                    }else{
                        //Caso falhe alguma inserção, desfaz todas as alterações do banco
                        $db->transRollback();
                        return $this->response->setStatusCode(400)->setJSON(array(
                            "success"=> false, "message"=> 
                            "Failed to insert contact", 
                            'isFromCache' => $this->isFromCache,
                            "processing_time" => $this->calculate_processing_time($time_start)
                        ));
                    }
                }
            }
            
        }else{
            //Caso falhe a inserção do contato inicial, desfaz todas as alterações do banco
            $db->transRollback();
            return $this->response->setStatusCode(400)->setJSON(array(
                "success"=> false, "message"=> 
                "Failed to insert contact", 
                'isFromCache' => $this->isFromCache,
                "processing_time" => $this->calculate_processing_time($time_start)
            ));
        }
    }

    //Função para atualizar um contato
    public function update($id = null)
    {   
        //Armazena a data de início do processamento
        $time_start = microtime(true);

        //Armazena os dados enviados via json
        $data = $this->request->getJSON(); 

        //Verifica e valida todos os campos obrigatórios
        if(!$this->validate($this->contactsModel->validationRules))
        {
            return $this->response->setStatusCode(422)->setJSON(array(
                "success"=> false, 
                "message"=> "Validation error", 
                "Errors"=> $this->validator->getErrors(),
                'isFromCache' => $this->isFromCache,
                "processing_time" => $this->calculate_processing_time($time_start)
            ));
        }

        $db = \Config\Database::connect();
        $db->transStart();

        //Busca se existe algum contato com esse id
        $data_id = $this->contactsModel->find($id);

        //Caso exista, faz a atualização do registro ou retorna que não foi encontrado
        if(empty($data_id)){
            $db->transRollback();
            return $this->response->setStatusCode(404)->setJSON(array(
                "success"=> false, 
                "message"=> "Contact not found", 
                'isFromCache' => $this->isFromCache,
                "processing_time" => $this->calculate_processing_time($time_start)
            ));
        }else{  
            //Executa a alteração dos registros em contacts
            $updated = $this->contactsModel->update($data_id, (array) $data);
            
            //Caso o updated tenha tido algum problema, retorna false
            if ($updated){
                $this->addressModel = new AddressModel();
                $this->emailModel   = new EmailModel();
                $this->phoneModel   = new PhoneModel();
                
                //Executa as validações dos Models adicionais
                $addressValid = $this->validate($this->addressModel->validationRules);
                $emailValid   = $this->validate($this->emailModel->validationRules);
                $phoneValid   = $this->validate($this->phoneModel->validationRules);
                if(!$addressValid || !$emailValid && !$phoneValid)
                {
                    //Caso a validação falhe, cancela as modificações feitas
                    $db->transRollback();
                    return $this->response->setStatusCode(422)->setJSON(array(
                        "success"=> false, 
                        "message"=> "Validation error", 
                        "Errors"=> $this->validator->getErrors(),
                        'isFromCache' => $this->isFromCache,
                        "processing_time" => $this->calculate_processing_time($time_start)
                    ));
                }else
                {
                    //Validando zip_code com a api do ViaCep
                    $data_zip_code_valid = $this->addressModel->validate_viacep($data->zip_code);
                    if(isset($data_zip_code_valid['erro']))
                    {
                        //Caso não passe na validação do ViaCep, cancela as modificações feitas
                        $db->transRollback();
                        return $this->response->setStatusCode(400)->setJSON(array(
                            "success"=> false, "message"=> 
                            "Invalid zip code from ViaCep", 
                            'isFromCache' => $this->isFromCache,
                            "processing_time" => $this->calculate_processing_time($time_start)
                        ));
                    }else
                    {
                        //Monta a $data de acordo com os dados das tabelas adicionais e executa as alterações
                        $data_address = [
                            'zip_code'       => $data->zip_code,
                            'country'        => $data->country,
                            'state'          => $data->state,
                            'street_address' => $data->street_address,
                            'address_number' => $data->address_number,
                            'city'           => $data->city,
                            'address_line'   => $data->address_line,
                            'neighborhood'   => $data->neighborhood,  
                        ];
                        $updated_address = $this->addressModel->where('id_contatct', $id)->set($data_address)->update();  
        
                        $data_phone = [
                            'phone'          => $data->phone,
                        ];
                        $updated_phone = $this->phoneModel->where('id_contatct', $id)->set($data_phone)->update();  
        
                        $data_email = [
                            'email'          => $data->email,
                        ];
                        $updated_email = $this->emailModel->where('id_contatct', $id)->set($data_email)->update(); 
        
                        //Caso todas as alterações tenham sido realizadas com sucesso, confirma as modificações no banco
                        if($updated_address && $updated_phone && $updated_email)
                        {
                            $db->transCommit();
                            $this->cache->del('consulta_sql_cache');
                            return $this->response->setStatusCode(200)->setJSON(array(
                                "success"=> true, 
                                "message"=> "Contact updated successfully", 
                                'isFromCache' => $this->isFromCache,
                                "processing_time" => $this->calculate_processing_time($time_start)
                            ));
                        }else{
                            //Caso algum update tenha falhado, desfaz todas as modificações no banco
                            $db->transRollback();
                            return $this->response->setStatusCode(400)->setJSON(array(
                                "success"=> false, "message"=> 
                                "Failed to update contact", 
                                'isFromCache' => $this->isFromCache,
                                "processing_time" => $this->calculate_processing_time($time_start)
                            ));
                        }
                    }
                }
            }else{
                $db->transRollback();
                return $this->response->setStatusCode(400)->setJSON(array(
                    "success"=> false, "message"=> "Failed to update contact", 
                    'isFromCache' => $this->isFromCache,
                    "processing_time" => $this->calculate_processing_time($time_start)
                ));
            }
        }
    }

    public function delete($id = null){
        //Armazena a data de início do processamento
        $time_start = microtime(true);

        //Busca se existe algum contato com esse id
        $data_id = $this->contactsModel->find($id);

        //Caso exista, faz a deleção do registro ou retorna que não foi encontrado
        if(empty($data_id)){
            return $this->response->setStatusCode(404)->setJSON(array(
                "success"=> false, 
                "message"=> "Contact not found", 
                'isFromCache' => $this->isFromCache,
                "processing_time" => $this->calculate_processing_time(($time_start))
            ));
        }else{  
            $this->addressModel = new AddressModel();
            $this->emailModel   = new EmailModel();
            $this->phoneModel   = new PhoneModel();

            $deleted_contact = $this->contactsModel->delete($id);
            $deleted_address = $this->addressModel->where("id_contatct", $id)->delete();
            $deleted_email = $this->emailModel->where("id_contatct", $id)->delete();
            $deleted_phone = $this->phoneModel->where("id_contatct", $id)->delete();
            
            //Calcula o tempo de processamento total
            $time_duration = round(microtime(true) - $time_start, 4);

            //Caso o deleted tenha tido algum problema, retorna false
            if ($deleted_contact && $deleted_address && $deleted_email || $deleted_phone) 
            {
                return $this->response->setStatusCode(200)->setJSON(array(
                    "success"=> true, 
                    "message"=> "Contact deleted successfully", 
                    'isFromCache' => $this->isFromCache,
                    "processing_time" => $this->calculate_processing_time($time_start)
                ));
            }else{
                return $this->response->setStatusCode(400)->setJSON(array(
                    "success"=> false, 
                    "message"=> "Failed to delete contact", 
                    'isFromCache' => $this->isFromCache,
                    "processing_time" => $this->calculate_processing_time($time_start)
                ));
            }
        }
    }
}
