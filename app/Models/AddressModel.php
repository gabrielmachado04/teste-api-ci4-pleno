<?php

namespace App\Models;

use CodeIgniter\Model;

class AddressModel extends Model
{
    protected $table            = 'address';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id_contatct',
        'zip_code',
        'country',
        'state',
        'street_address',
        'address_number',
        'city',
        'address_line',
        'neighborhood',
    ];

    protected bool $allowEmptyInserts = false;

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [
        'zip_code'       => 'required|min_length[8]',
        'country'        => 'required|min_length[3]',
        'state'          => 'required|min_length[2]',
        'street_address' => 'required|min_length[3]',
        'address_number' => 'required|min_length[2]',
        'city'           => 'required|min_length[3]',
        'address_line'   => 'required|min_length[3]',
        'neighborhood'   => 'required|min_length[3]',
    ];

    public function validate_viacep($cep)
    {
        $client_request = \Config\Services::curlrequest();
        $response  = $client_request->get('viacep.com.br/ws/'.$cep.'/json/');
        if($response->getStatusCode() == 200)
        {
            $dados = json_decode($response->getBody(), true);
            return $dados;
        }
    }
}
