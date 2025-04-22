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

    //Função para retornar todos os contatos
    public function index()
    {
        //Armazena a data de início do processamento
        $time_start = microtime(true);

        //Busca por todos os contacts
        $data = $this->contactsModel->findAll();

        //Calcula o tempo de processamento total
        $time_duration = round(microtime(true) - $time_start, 4);
        return $this->response->setStatusCode(200)->setJSON([
            'success' => true,
            'contacts'=> $data,
            'processing_time'=> $time_duration
        ]);
    }

    //Função para inserir um novo contato
    public function insert()
    {
        //Armazena a data de início do processamento
        $time_start = microtime(true);

        $data = $this->request->getJSON(); 
        $inserted = $this->contactsModel->insert($data);

        //Calcula o tempo de processamento total
        $time_duration = round(microtime(true) - $time_start, 4);

        if ($inserted){
            return $this->response->setStatusCode(201)->setJSON(array("success"=> true, "message"=> "Contact inserted successfully", "processing_time" => $time_duration));
        }else{
            return $this->response->setStatusCode(400)->setJSON(array("success"=> false, "message"=> "Failed to insert contact"));
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
            //Calcula o tempo de processamento total
            $time_duration = round(microtime(true) - $time_start, 4);
            return $this->response->setStatusCode(404)->setJSON(array("success"=> false, "message"=> "Contact not found", "processing_time" => $time_duration));
        }else{  
            $updated = $this->contactsModel->update($data_id, (array) $data);
            
            //Calcula o tempo de processamento total
            $time_duration = round(microtime(true) - $time_start, 4);
            
            //Caso o updated tenha tido algum problema, retorna false
            if ($updated){
                return $this->response->setStatusCode(200)->setJSON(array("success"=> true, "message"=> "Contact updated successfully", "processing_time" => $time_duration));
            }else{
                return $this->response->setStatusCode(400)->setJSON(array("success"=> false, "message"=> "Failed to update contact", "processing_time" => $time_duration));
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
            //Calcula o tempo de processamento total
            $time_duration = round(microtime(true) - $time_start, 4);

            return $this->response->setStatusCode(404)->setJSON(array("success"=> false, "message"=> "Contact not found", "processing_time" => $time_duration));
        }else{  
            $deleted = $this->contactsModel->delete($id);
            
            //Calcula o tempo de processamento total
            $time_duration = round(microtime(true) - $time_start, 4);

            //Caso o deleted tenha tido algum problema, retorna false
            if ($deleted){
                return $this->response->setStatusCode(200)->setJSON(array("success"=> true, "message"=> "Contact deleted successfully", "processing_time" => $time_duration));
            }else{
                return $this->response->setStatusCode(400)->setJSON(array("success"=> false, "message"=> "Failed to delete contact", "processing_time" => $time_duration));
            }
        }
    }
}
