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
        $data = $this->contactsModel->findAll();
        return $this->response->setJSON($data);
    }

    //Função para inserir um novo contato
    public function insert()
    {
        $data = $this->request->getJSON(); 
        $inserted = $this->contactsModel->insert($data);
        if ($inserted){
            return $this->response->setJSON(array("success"=> true, "message"=> "Contact inserted successfully"));
        }else{
            return $this->response->setJSON(array("success"=> false, "message"=> "Failed to insert contact"));
        }
    }

    //Função para atualizar um contato
    public function update($id = null)
    {
        //Armazena os dados enviados via json
        $data = $this->request->getJSON(); 

        //Busca se existe algum contato com esse id
        $data_id = $this->contactsModel->find($id);

        //Caso exista, faz a atualização do registro ou retorna que não foi encontrado
        if(empty($data_id)){
            return $this->response->setJSON(array("success"=> false, "message"=> "Contact not found"));
        }else{  
            $updated = $this->contactsModel->update($data_id, (array) $data);
            //Caso o updated tenha tido algum problema, retorna false
            if ($updated){
                return $this->response->setJSON(array("success"=> true, "message"=> "Contact updated successfully"));
            }else{
                return $this->response->setJSON(array("success"=> false, "message"=> "Failed to update contact"));
            }
        }
    }

    public function delete($id = null){
        //Busca se existe algum contato com esse id
        $data_id = $this->contactsModel->find($id);

        //Caso exista, faz a deleção do registro ou retorna que não foi encontrado
        if(empty($data_id)){
            return $this->response->setJSON(array("success"=> false, "message"=> "Contact not found"));
        }else{  
            $deleted = $this->contactsModel->delete($id);
            //Caso o deleted tenha tido algum problema, retorna false
            if ($deleted){
                return $this->response->setJSON(array("success"=> true, "message"=> "Contact deleted successfully"));
            }else{
                return $this->response->setJSON(array("success"=> false, "message"=> "Failed to delete contact"));
            }
        }
    }
}
