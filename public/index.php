<?php

use PHPUnit\Framework\Constraint\Count;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/../vendor/autoload.php';

require_once '../includes/dboperation.php';
$app = new \Slim\App([
    'settings' => [
        'displayErrorDetails' => true
    ]
]);

//! login

$app->post('/login', function (Request $request, Response $response) {
    $requestData = json_decode($request->getBody());
    $email = $requestData->email;
    $password = $requestData->password;
    $db = new DbOperation();
    $responseData = array();
    if (count($db->Login($email, $password)) > 0) {
        $responseData['error'] = false;
        $responseData['message'] = "Login Successfully";
        $responseData['data'] = $db->Login($email, $password);
    } else {
        $responseData['error'] = true;
        $responseData['message'] = "Invalid Credential" . count($db->Login($email, $password));
    }
    $response->getBody()->write(json_encode($responseData));
});

$app->post('/adminsitelogin', function (Request $request, Response $response) {
    $requestData = json_decode($request->getBody());
    $email = $requestData->email;
    $password = $requestData->password;
    $role = $requestData->role;
    $db = new DbOperation();
    $responseData = array();
    if (count($db->AdminLogin($email, $password, $role)) > 0) {
        $responseData['error'] = false;
        $responseData['message'] = "Login Successfully";
        $responseData['data'] = $db->AdminLogin($email, $password, $role);
    } else {
        $responseData['error'] = true;
        $responseData['message'] = "Invalid Credential" . count($db->Login($email, $password));
    }
    $response->getBody()->write(json_encode($responseData));
});

$app->run();
