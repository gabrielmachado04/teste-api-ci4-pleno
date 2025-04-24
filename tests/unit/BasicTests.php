<?php

use CodeIgniter\Test\FeatureTestCase ;
use App\Controllers\Contacts;
use Config\Services;

/**
 * @internal
 */
final class BasicTests extends FeatureTestCase 
{
    public function testIndex(): void
    {
        $response = $this->get('/contacts', []);
        echo $response->getBody();
        $response->assertStatus(200);
        $response->assertJSONFragment(['success' => 'true']);
    }

    //public function testDelete(): void
    //{
    //    $response = $this->delete('/contacts/3', []);
   //     $response->assertStatus(200);
    //    $response->assertJSONFragment(['success' => 'true']);
    //}

}