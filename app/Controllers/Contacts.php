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

        //Busca por todos os contacts
        $data = $this->contactsModel->findAll();

        return $this->response->setStatusCode(200)->setJSON([
            'success' => true,
            'contacts'=> $data,
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
                return $this->response->setStatusCode(200)->setJSON(array(
                    "success"=> true, 
                    "message"=> "Contact updated successfully", 
                    "processing_time" => $this->calculate_processing_time($time_start)
                ));
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
