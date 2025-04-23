<?php

namespace App\Controllers;

use App\Controllers\BaseController;

use App\Models\ContactsModel;
use App\Models\AddressModel;
use App\Models\EmailModel;
use App\Models\PhoneModel;
use Config\Email;

class Contacts extends BaseController
{   
    private $contactsModel;
    private $addressModel;
    private $phoneModel;
    private $emailModel;

    //Carregando Model para ser utilizado em todas as funções.
    public function __construct() 
    {
        $this->contactsModel = new ContactsModel();
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

        $this->addressModel = new AddressModel();
        $this->emailModel   = new EmailModel();
        $this->phoneModel   = new PhoneModel();

        //Busca por todos os contacts
        $data_contacts = $this->contactsModel->findAll();
        $all_contacts = [];
        foreach ($data_contacts as $contact)
        {
            $contact_id = $contact['id'];
            $contact_name = $contact['name'];
            $contact_description = $contact['description'];

            $data_address = $this->addressModel->where('id_contatct', $contact_id)->first();
            $data_email   = $this->emailModel->where('id_contatct', $contact_id)->first();
            $data_phone   = $this->phoneModel->where('id_contatct', $contact_id)->first();

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

        return $this->response->setStatusCode(200)->setJSON([
            'success' => true,
            'contacts'=> $all_contacts,
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
                "processing_time" => $this->calculate_processing_time($time_start)
            ));
        }
        $inserted = $this->contactsModel->insert($data);
        if ($inserted)
        {
            //Captura o id inserido no banco
            $inserted_id = $this->contactsModel->getInsertID();
            
            $this->addressModel = new AddressModel();
            $this->emailModel   = new EmailModel();
            $this->phoneModel   = new PhoneModel();

            $addressValid = $this->validate($this->addressModel->validationRules);
            $emailValid = $this->validate($this->emailModel->validationRules);
            $phoneValid = $this->validate($this->phoneModel->validationRules);
            if(!$addressValid || !$emailValid && !$phoneValid)
            {
                return $this->response->setStatusCode(422)->setJSON(array(
                    "success"=> false, 
                    "message"=> "Validation error", 
                    "Errors"=> $this->validator->getErrors(),
                    "processing_time" => $this->calculate_processing_time($time_start)
                ));
            }else{
                //Validando zip_code com a api do ViaCep
                $data_zip_code_valid = $this->addressModel->validate_viacep($data->zip_code);
                if(isset($data_zip_code_valid['erro']))
                {
                    return $this->response->setStatusCode(400)->setJSON(array(
                        "success"=> false, "message"=> 
                        "Invalid zip code from ViaCep", 
                        "processing_time" => $this->calculate_processing_time($time_start)
                    ));
                }else
                {
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
    
                    if($inserted_address && $inserted_phone && $inserted_email)
                    {
                        return $this->response->setStatusCode(201)->setJSON(array(
                            "success"=> true, 
                            "message"=> "Contact inserted successfully", 
                            "processing_time" => $this->calculate_processing_time($time_start)
                        ));
                    }else{
                        return $this->response->setStatusCode(400)->setJSON(array(
                            "success"=> false, "message"=> 
                            "Failed to insert contact", 
                            "processing_time" => $this->calculate_processing_time($time_start)
                        ));
                    }
                }
            }
            
        }else{
            return $this->response->setStatusCode(400)->setJSON(array(
                "success"=> false, "message"=> 
                "Failed to insert contact", 
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
                "processing_time" => $this->calculate_processing_time($time_start)
            ));
        }

        //Busca se existe algum contato com esse id
        $data_id = $this->contactsModel->find($id);

        //Caso exista, faz a atualização do registro ou retorna que não foi encontrado
        if(empty($data_id)){
            return $this->response->setStatusCode(404)->setJSON(array(
                "success"=> false, 
                "message"=> "Contact not found", 
                "processing_time" => $this->calculate_processing_time($time_start)
            ));
        }else{  
            $updated = $this->contactsModel->update($data_id, (array) $data);
            
            //Caso o updated tenha tido algum problema, retorna false
            if ($updated){
                $this->addressModel = new AddressModel();
                $this->emailModel   = new EmailModel();
                $this->phoneModel   = new PhoneModel();

                $addressValid = $this->validate($this->addressModel->validationRules);
                $emailValid   = $this->validate($this->emailModel->validationRules);
                $phoneValid   = $this->validate($this->phoneModel->validationRules);
                if(!$addressValid || !$emailValid && !$phoneValid)
                {
                    return $this->response->setStatusCode(422)->setJSON(array(
                        "success"=> false, 
                        "message"=> "Validation error", 
                        "Errors"=> $this->validator->getErrors(),
                        "processing_time" => $this->calculate_processing_time($time_start)
                    ));
                }else
                {
                    //Validando zip_code com a api do ViaCep
                    $data_zip_code_valid = $this->addressModel->validate_viacep($data->zip_code);
                    if(isset($data_zip_code_valid['erro']))
                    {
                        return $this->response->setStatusCode(400)->setJSON(array(
                            "success"=> false, "message"=> 
                            "Invalid zip code from ViaCep", 
                            "processing_time" => $this->calculate_processing_time($time_start)
                        ));
                    }else
                    {
                        $data_address = [
                            //'id_contatct'    => $data_id,
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
                            //'id_contatct'    => $data_id,
                            'phone'          => $data->phone,
                        ];
                        $updated_phone = $this->phoneModel->where('id_contatct', $id)->set($data_phone)->update();  
        
                        $data_email = [
                            //'id_contatct'    => $data_id,
                            'email'          => $data->email,
                        ];
                        $updated_email = $this->emailModel->where('id_contatct', $id)->set($data_email)->update(); 
        
                        if($updated_address && $updated_phone && $updated_email)
                        {
                            return $this->response->setStatusCode(200)->setJSON(array(
                                "success"=> true, 
                                "message"=> "Contact updated successfully", 
                                "processing_time" => $this->calculate_processing_time($time_start)
                            ));
                        }else{
                            return $this->response->setStatusCode(400)->setJSON(array(
                                "success"=> false, "message"=> 
                                "Failed to update contact", 
                                "processing_time" => $this->calculate_processing_time($time_start)
                            ));
                        }
                    }
                }
            }else{
                return $this->response->setStatusCode(400)->setJSON(array(
                    "success"=> false, "message"=> "Failed to update contact", 
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
                "processing_time" => $this->calculate_processing_time(($time_start))
            ));
        }else{  
            $deleted = $this->contactsModel->delete($id);
            
            //Calcula o tempo de processamento total
            $time_duration = round(microtime(true) - $time_start, 4);

            //Caso o deleted tenha tido algum problema, retorna false
            if ($deleted){
                return $this->response->setStatusCode(200)->setJSON(array(
                    "success"=> true, 
                    "message"=> "Contact deleted successfully", 
                    "processing_time" => $this->calculate_processing_time($time_start)
                ));
            }else{
                return $this->response->setStatusCode(400)->setJSON(array(
                    "success"=> false, 
                    "message"=> "Failed to delete contact", 
                    "processing_time" => $this->calculate_processing_time($time_start)
                ));
            }
        }
    }
}
