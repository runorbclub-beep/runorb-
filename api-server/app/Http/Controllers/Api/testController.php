<?php


namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Models\UsrUser;
use App\Services\LocalPlayService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class testController extends Controller
{

    public function test()
    {
        Log::info('回调', [$_GET, $_POST, $_REQUEST, file_get_contents("php://input")]);
        $_wx_response_data = array(
            'id' => 'fcf54ae4-fd88-5208-a83c-c5788d9dfd46',
            'create_time' => '2021-06-22T18:17:11+08:00',
            'resource_type' => 'encrypt-resource',
            'event_type' => 'TRANSACTION.SUCCESS',
            'summary' => '支付成功',
            'resource' =>
                array(
                    'original_type' => 'transaction',
                    'algorithm' => 'AEAD_AES_256_GCM',
                    'ciphertext' => '5zZA6BPrb3V9/4bA/H3lUw0RP4ka5oZDv0udo3jlytWaZ062IorZjR9L2YEYiY7gPWXD55ITp5K9/APFWR4gIwGlnui56SdpoD0j7Bi+0gO3bCggKf9AuaurP7yN9zWWzh6OgcS3+d/8QXkOKEVwN4WW5zij8ukaSPJE9KaX1G7aWYnuoiQW1hZe4B6R27IuxNMDSjEzIIzWhO+DdqI3M84A8i1lOmcakZijh6OaLfsAwNxrSb5+AoiWbaq7PRqjw+ZoiESKd5Kkn0Cdr52/fkqoqtfZxqrVUumgqoxRAKCQK/Oan9dOoKNKr5uUize3KxuBfGDDgLno5sU186my9KQhAQT3m5IEduvprqu66MHTbNASbqlob/ZvpSjjU6NZa5d1Oh8V66vqGpWcDcWLX/3DUgupDof1IRUUYOatCWpDWAwQfzvolVJ6tcEQ6oiVDdRoqqeKUM6UNBn1oplXbjvJrGtgzKvwaP8OMOjttnnsW4RNNz/D3GgfjhOmjSykH8gzxWM87iBLFGiwuQOdE0sYlgK5YSggOgl+1077w4SCyqci4iWE99N+PGeC9x24sXurnuBxxgMTkAzZqw62bA==',
                    'associated_data' => 'transaction',
                    'nonce' => 'yl5VqKXNCgZ9',
                ),
        );


        Log::info($_wx_response_data);


        return array(
            "code" => 1,
            "msg" => $_wx_response_data,
            // "time" => date('Y-m-d H:i:s')
        );
    }
    
    public function test2(LocalPlayService $localPlayService)
    {
        try {
            return [
                "code" => 0,
                "msg" => "ok",
                "data" => $localPlayService->handlePlayLogTest((object)['post_play_id' => '27950'])
            ];
        } catch (BusinessException $e) {
            return $e->getMessage();
        }
    }

}
