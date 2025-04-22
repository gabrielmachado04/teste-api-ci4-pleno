<?php

namespace App\Controllers;

use App\Controllers\BaseController;

use App\Models\ContactsModel;

class Contacts extends BaseController
{   
    private $contactsModel;

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

        $validationRules = [
            'name'           => 'required|min_length[3]',
            'description'    => 'required|min_length[3]',
            'zip_code'       => 'required|min_length[3]',
            'country'        => 'required|min_length[3]',
            'state'          => 'required|min_length[2]',
            'street_address' => 'required|min_length[2]',
            'address_number' => 'required|min_length[2]',
            'city'           => 'required|min_length[2]',
            'address_line'   => 'required|min_length[2]',
            'neighborhood'   => 'required|min_length[2]',
            'phone'          => 'required|min_length[2]',
            'email'          => 'required|valid_email',
        ];

        if(!$this->validate($validationRules))
        {
            return $this->response->setStatusCode(422)->setJSON(array(
                "success"=> false, 
                "message"=> "Validation error", 
                "Errors"=> $this->validator->getErrors(),
                "processing_time" => $this->calculate_processing_time($time_start)
            ));
        }
        $inserted = $this->contactsModel->insert($data);
        if ($inserted){
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

    //Função para atualizar um contato
    public function update($id = null)
    {   
        //Armazena a data de início do processamento
        $time_start = microtime(true);

        //Armazena os dados enviados via json
        $data = $this->request->getJSON(); 

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
