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
       $role= $requestData->role;
    $db = new DbOperation();
    $responseData = array();
    if (count($db->Login($email, $password,$role)) > 0) {
        $responseData['error'] = false;
        $responseData['message'] = "Login Successfully";
        $responseData['data'] = $db->Login($email, $password,$role);

    } else {
        $responseData['error'] = true;
        $responseData['message'] = "Invalid Credential" ;
    }
    $response->getBody()->write(json_encode($responseData));
});

$app->post('/syncData', function (Request $request, Response $response) {
    $requestData = json_decode($request->getBody());
    $time = $requestData->time;
    $type = $requestData->table;
    $db = new DbOperation();
    if($type == 'users'){
        $unSyncManager = $db->getUnSyncManager($time);
    }else if($type == 'categories'){
        $unSyncCategory = $db->getUnSyncCategories($time);
    }else if($type == 'products'){
        $unSyncProducts = $db->getUnSyncProducts($time);
    }else if($type == 'deals'){
        $unSyncDeals = $db->getUnSyncDeals($time);
    } else if($type == 'orders'){
        $unSyncOrders = $db->getUnSyncOrders($time);
    }

    // Create response object
    $responseData = new stdClass();
    if($type == 'users'){
        $responseData->data = $unSyncManager;
    }else if($type == 'categories'){
        $responseData->data = $unSyncCategory;
    }else if($type == 'products'){
        $responseData->data = $unSyncProducts;
    }else if($type == 'deals'){
        $responseData->data = $unSyncDeals;
    }else if($type == "orders"){
        $responseData->data = $unSyncOrders;
    }

    $response->getBody()->write(json_encode($responseData));
    return $response->withHeader('Content-Type', 'application/json');
});
$app->post('/syncDeletedData', function (Request $request, Response $response) {
    $requestData = json_decode($request->getBody());
    $time = $requestData->time;
    $db = new DbOperation();
    $unSyncDeletedRecord = $db->getUnSyncDeletedRecord($time);
    $response->getBody()->write(json_encode($unSyncDeletedRecord));
    return $response->withHeader('Content-Type', 'application/json');
});
// Add a new manager
$app->post('/addManager', function (Request $request, Response $response) {
    $requestData = json_decode($request->getBody());
    $email = $requestData->email;
    $password = $requestData->password;
    $id = $requestData->id;
    $role = $requestData->role;
    $phone = $requestData->phone;
    $name = $requestData->name;
    $cnic = $requestData->cnic;
    $image = $requestData->image;
    $agrement = $requestData->agrement;
    $extension = $requestData->extention;
    $create_at = $requestData->created_at;
    $db = new DbOperation();
    $responseData = array();
    if($db->isUsersIdExist($id)){
        $result = $db->updateManager($id, $email, $password, $phone, $name,$create_at,$role);
        if($result){
            $responseData['error'] = false;
            $responseData['message'] = "Manager updated successfully";
        } else {
            $responseData['error'] = true;
            $responseData['message'] = "Manager not updated successfully";
        }
    } else {
        $result = $db->addManager($email, $password, $role, $phone, $name,$cnic,$image,$agrement,$extension,$id,$create_at);
        if($result){
            $responseData['error'] = false;
            $responseData['message'] = "Manager added successfully";
        } else {
            $responseData['error'] = true;
            $responseData['message'] = "Manager not added successfully";
        }
    }
    $response->getBody()->write(json_encode($responseData));
    return $response->withHeader('Content-Type', 'application/json');
});


$app->get('/getGalleryImage/{id}', function (Request $request, Response $response) {
    $id = $request->getAttribute('id');
    $db = new DbOperation();
    $result = $db->getGalleryImage($id);
    $response->getBody()->write(json_encode($result));
});
// Get manager by ID
$app->get('/getManagerById/{id}', function (Request $request, Response $response) {
    $id = $request->getAttribute('id');
    $db = new DbOperation();
    $result = $db->getManagerById($id);
    $response->getBody()->write(json_encode($result));
});
$app->get('/allManager', function (Request $request, Response $response) {
   
    $db = new DbOperation();
    $result = $db->getAllManager();
    $response->getBody()->write(json_encode($result));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/getFinance', function (Request $request, Response $response) {
   
    $db = new DbOperation();
    $result = $db->getFinance();
    $response->getBody()->write(json_encode($result));
    return $response->withHeader('Content-Type', 'application/json');
});
// Delete manager by ID
$app->delete('/deleteManager/{id}', function (Request $request, Response $response) {
    $id = $request->getAttribute('id');
    $db = new DbOperation();
    $responseData = array();
    
    if ($db->deleteManager($id)) {
        $responseData['error'] = false;
        $responseData['message'] = "Manager deleted successfully";
    } else {
        $responseData['error'] = true;
        $responseData['message'] = "Failed to delete manager";
    }
    
    $response->getBody()->write(json_encode($responseData));
});

$app->put('/deleteRecord', function (Request $request, Response $response) {
    $requestData = json_decode($request->getBody());
    $id = (int)$requestData->id;
    $key = $requestData->key;
    $table = $requestData->table;
    $deletedBy = $requestData->deletedBy;
    $created_at = $requestData->created_at;
    $db = new DbOperation();
    $responseData = array();
    
    if ($db->deleteRecord($key,$table,$id)) {
        $db->deletedRecord($key,$id,$table,$deletedBy,$created_at);
        $responseData['error'] = false;
        $responseData['message'] = "Record deleted successfully";
    } else {
        $responseData['error'] = true;
        $responseData['message'] = "Failed to delete Record";
    }
    
    $response->getBody()->write(json_encode($responseData));
});

// Add a new category
$app->post('/addCategory', function (Request $request, Response $response) {
    $requestData = json_decode($request->getBody());
    $id = $requestData->id;
    $category = $requestData->category;
    $create_at = $requestData->create_at;
    $db = new DbOperation();
    $responseData = array();
    if($db->isUserExist($id)){
        $result = $db->UpdateCategory($category,$id, $create_at);
        if ($result === CATAGORY_CREATED) {
            $responseData['error'] = false;
            $responseData['message'] = "Category added successfully";

        } else if ($result === CATAGORY_EXIST) {
            $responseData['error'] = true;
            $responseData['message'] = "Category already exists";
        } else {
            $responseData['error'] = true;
            $responseData['message'] = "Failed to add category";
        }
        $response->getBody()->write(json_encode($responseData));
    } else {
        $result = $db->addCategoryWithId($id,$category, $create_at);
        if ($result === CATAGORY_CREATED) {
            $responseData['error'] = false;
            $responseData['message'] = "Category added successfully";
        } else if ($result === CATAGORY_EXIST) {
            $responseData['error'] = true;
            $responseData['message'] = "Category already exists";
        } else {
            $responseData['error'] = true;
            $responseData['message'] = "Failed to add category";
        }
        $response->getBody()->write(json_encode($responseData));
    }  
});

// Get all categories
$app->get('/getAllCategories', function (Request $request, Response $response) {
    $db = new DbOperation();
    $result = $db->getAllCategories();
    $response->getBody()->write(json_encode($result));
});

// Get category by ID
$app->get('/getCategoryById/{id}', function (Request $request, Response $response) {
    $id = $request->getAttribute('id');
    $db = new DbOperation();
    $result = $db->getCategoryById($id);
    $response->getBody()->write(json_encode($result));
});

// Delete category
$app->delete('/deleteCategory/{id}', function (Request $request, Response $response) {
    $id = $request->getAttribute('id');
    $db = new DbOperation();
    $responseData = array();
    
    if ($db->deleteCategory($id)) {
        $responseData['error'] = false;
        $responseData['message'] = "Category deleted successfully";
    } else {
        $responseData['error'] = true;
        $responseData['message'] = "Failed to delete category. It might be in use.";
    }
    
    $response->getBody()->write(json_encode($responseData));
});
// Add Product
$app->post('/addProduct', function (Request $request, Response $response) {
    $requestData = json_decode($request->getBody());
    $category_id = $requestData->category_id;
    $id = $requestData->id;
    $image = $requestData->image;
    $name = $requestData->name;
    $sizeType= $requestData->sizeType;
    $gallery= $requestData->gallery;
    $prepration_time = $requestData->prepration_time;
    $isAvailable = $requestData->isAvailable;
    $description = $requestData->description;
    $hasVariant = $requestData->hasVariant;
    $create_at = $requestData->create_at;
    $db = new DbOperation();
    $responseData = array();
    if($db->isProductIdExist($id)){
        $result = $db->updateProduct($category_id, $name,$image,$prepration_time,$isAvailable,$description,$id,$create_at);
        if ($result) {
            if(Count($gallery) > 0){
                foreach ($gallery as $gallery) {
                    if($db->isGalleryIdExist($gallery->id)){
                        $db->updateGallery($id, $gallery->image,$gallery->id);
                    }else {
                        $db->addGallery($id, $gallery->image,$gallery->id);
                    }
                }
            }
            if($hasVariant){
                foreach ($sizeType as $sizeType) {
                    if($db->isSizeTypeIdExist($sizeType->id)){
                        $db->updateSizeType($id, $sizeType->type, $sizeType->cost, $sizeType->sale,$sizeType->id);
                    }else {
                        $db->addSizeType($id, $sizeType->type, $sizeType->cost, $sizeType->sale,$sizeType->id);
                    }
                }
            }
        }
        $responseData['error'] = false;
        $responseData['message'] = "Product updated successfully";
    }  else {
        $result = $db->addProduct($category_id, $name,$image,$prepration_time,$isAvailable,$description,$id,$create_at);
        if ($result !== PRODUCT_EXIST) {
            if(Count($gallery) > 0){
                foreach ($gallery as $gallery) {
                    $db->addGallery($id, $gallery->image,$gallery->id);
                }
            }
        }
        $responseData['error'] = false;
        $responseData['message'] = "Product added successfully";
        if($hasVariant){
            foreach ($sizeType as $sizeType) {
                $db->addSizeType($id, $sizeType->type, $sizeType->cost, $sizeType->sale,$sizeType->id);
            }
        }
    }
    
    $response->getBody()->write(json_encode($responseData));
});

// Get All Products
$app->get('/getAllProducts', function (Request $request, Response $response) {
    $db = new DbOperation();
    $products = $db->getAllProducts();
    $responseData = array();
    
    $responseData['error'] = false;
    $responseData['products'] = $products;
    
    $response->getBody()->write(json_encode($responseData));
});

// Get Products by Category
$app->get('/getProductsByCategory/{category_id}', function (Request $request, Response $response, $args) {
    $category_id = $args['category_id'];
    $db = new DbOperation();
    $products = $db->getProductsByCategory($category_id);
    $responseData = array();
    
    $responseData['error'] = false;
    $responseData['products'] = $products;
    
    $response->getBody()->write(json_encode($responseData));
});

// Get Product by ID
$app->get('/getProductById/{id}', function (Request $request, Response $response, $args) {
    $id = $args['id'];
    $db = new DbOperation();
    $product = $db->getProductById($id);
    $responseData = array();
    
    if (!empty($product)) {
        $responseData['error'] = false;
        $responseData['product'] = $product;
    } else {
        $responseData['error'] = true;
        $responseData['message'] = "Product not found";
    }
    
    $response->getBody()->write(json_encode($responseData));
});



// Delete Product
$app->delete('/deleteProduct/{id}', function (Request $request, Response $response, $args) {
    $id = $args['id'];
    $db = new DbOperation();
    $responseData = array();
    
    if ($db->deleteProduct($id)) {
        $responseData['error'] = false;
        $responseData['message'] = "Product deleted successfully";
    } else {
        $responseData['error'] = true;
        $responseData['message'] = "Failed to delete product";
    }
    
    $response->getBody()->write(json_encode($responseData));
});

// Get Size Types by Product
$app->get('/getSizeTypesByProduct/{product_id}', function (Request $request, Response $response, $args) {
    $product_id = $args['product_id'];
    $db = new DbOperation();
    $sizeTypes = $db->getSizeTypesByProduct($product_id);
    $responseData = array();
    
    $responseData['error'] = false;
    $responseData['sizeTypes'] = $sizeTypes;
    
    $response->getBody()->write(json_encode($responseData));
});


// Delete Size Type
$app->delete('/deleteSizeType/{id}', function (Request $request, Response $response, $args) {
    $id = $args['id'];
    $db = new DbOperation();
    $responseData = array();
    
    $result = $db->deleteSizeType($id);
    
    if ($result === SIZE_TYPE_DELETED) {
        $responseData['error'] = false;
        $responseData['message'] = "Size type deleted successfully";
    } else {
        $responseData['error'] = true;
        $responseData['message'] = "Failed to delete size type";
    }
    
    $response->getBody()->write(json_encode($responseData));
});

$app->post('/addOrder', function (Request $request, Response $response) {
    $requestData = json_decode($request->getBody());
    $id = $requestData->id;
    $tableNo = $requestData->tableNo;
    $orderType = $requestData->orderType;
    $discount = $requestData->discount;
    $cost = $requestData->cost;
    $sale = $requestData->sale;
    $net = $requestData->net;
    $orderNumber = $requestData->orderNumber;
    $status = $requestData->status;
    $deal_id = $requestData->deal_id;
    $userId = $requestData->userId;
    $delivery_fee = $requestData->delivery_fee;
    $note = $requestData->note;
    $delivery_type = $requestData->delivery_type;
    $address = $requestData->address;
    $sgst = $requestData->sgst;
    $cgst = $requestData->cgst;
    
    $orderTakerId = $requestData->orderTakerId;
    $create_at = $requestData->create_at;
    $db = new DbOperation();
    $responseData = array();
    if($db->isOrderIdExist($id)){
         $result = $db->updateOrder($id, $tableNo, $orderType, $discount, $cost, $sale, $net, $status,$deal_id,$userId,$delivery_fee,$note,$delivery_type,$address,$sgst,$cgst,$orderTakerId,$orderNumber,$create_at);
         if($result){
            foreach ($requestData->orderDetails as $orderDetail) {
                if($orderDetail->id){
                    $db->updateOrderDetails($orderDetail->id, $orderDetail->product_id, $orderDetail->size, $orderDetail->cost, $orderDetail->sale,$orderDetail->note,$orderDetail->quantity);
                } else {
                    $db->orderDetails($id, $orderDetail->product_id, $orderDetail->size, $orderDetail->cost, $orderDetail->sale,$orderDetail->note,$orderDetail->quantity,$orderDetail->id);
                }
            }
            $responseData['error'] = false;
            $responseData['message'] = "Order Form Submitted";
        }else{
            $responseData['error'] = true;
            $responseData['message'] = "Order Form Not Submitted";
        }
    } else {
        $res = $db->addOrder($id, $tableNo, $orderType, $discount, $cost, $sale, $net, $status, $deal_id,$userId,$delivery_fee,$note,$delivery_type,$address,$sgst,$cgst,$orderTakerId,$orderNumber,$create_at);
        if ($res !== "UserNotAvailable") {
        foreach ($requestData->orderDetails as $orderDetail) {
            $db->orderDetails($id, $orderDetail->product_id, $orderDetail->size, $orderDetail->cost, $orderDetail->sale,$orderDetail->note,$orderDetail->quantity,$orderDetail->id);
        }
        $responseData['error'] = false;
        $responseData['message'] = "Order Form Submitted";
    } else {
        $responseData['error'] = true;
        $responseData['message'] = "Order Form Not Submitted";
    }
    }
    
    $response->getBody()->write(json_encode($responseData));
});



// Get order by ID
$app->get('/getOrderById/{id}', function (Request $request, Response $response, $args) {
    $id = $args['id'];
    $db = new DbOperation();
    $order = $db->getAllOrdersByID($id);
    $response->getBody()->write(json_encode($order));
    return $response->withHeader('Content-Type', 'application/json');
});

// Get all orders with optional status filter
$app->get('/getAllOrders', function (Request $request, Response $response, $args) {
  
    $db = new DbOperation();
    $orders = $db->getAllOrders();
    
    $responseData = array(
        'error' => false,
        'count' => count($orders),
        'orders' => $orders
    );
    
    $response->getBody()->write(json_encode($responseData));
    return $response->withHeader('Content-Type', 'application/json');
});
$app->get('/getPendingOrder', function (Request $request, Response $response, $args) {
  
    $db = new DbOperation();
    $orders = $db->getPendingOrder();
    
    $responseData = array(
        'error' => false,
        'count' => count($orders),
        'orders' => $orders
    );
    
    $response->getBody()->write(json_encode($responseData));
    return $response->withHeader('Content-Type', 'application/json');
});

// Get orders by table number with optional status filter
$app->get('/getOrdersByTable/{tableNo}', function (Request $request, Response $response, $args) {
    $tableNo = $args['tableNo'];

    
    $db = new DbOperation();
    $orders = $db->getOrdersByTable($tableNo);
    
    $responseData = array(
        'error' => false,
        'count' => count($orders),
        'orders' => $orders
    );
    
    $response->getBody()->write(json_encode($responseData));
    return $response->withHeader('Content-Type', 'application/json');
});
$app->put('/updateUserStatus', function (Request $request, Response $response) {
    $requestData = json_decode($request->getBody());
    $id = $requestData->id;
    $status = $requestData->status;
    
    $db = new DbOperation();
    $result = $db->updateUserStatus($id, $status);
    
    $responseData = array();
    if ($result) {
        $responseData['error'] = false;
        $responseData['message'] = "User status updated successfully";
    } else {
        $responseData['error'] = true;
        $responseData['message'] = "Failed to update user status";
    }
    
    $response->getBody()->write(json_encode($responseData));
    return $response->withHeader('Content-Type', 'application/json');
});
// Update order status
$app->put('/updateOrderStatus', function (Request $request, Response $response) {
    $requestData = json_decode($request->getBody());
    $id = $requestData->id;
    $status = $requestData->status;
    
    $db = new DbOperation();
    $result = $db->updateOrderStatus($id, $status);
    
    $responseData = array();
    if ($result) {
        $responseData['error'] = false;
        $responseData['message'] = "Order status updated successfully";
    } else {
        $responseData['error'] = true;
        $responseData['message'] = "Failed to update order status";
    }
    
    $response->getBody()->write(json_encode($responseData));
    return $response->withHeader('Content-Type', 'application/json');
});



// Delete order
$app->delete('/deleteOrder/{id}', function (Request $request, Response $response, $args) {
    $id = $args['id'];
    $db = new DbOperation();
    $result = $db->deleteOrder($id);
    
    $responseData = array();
    if ($result) {
        $responseData['error'] = false;
        $responseData['message'] = "Order deleted successfully";
    } else {
        $responseData['error'] = true;
        $responseData['message'] = "Failed to delete order";
    }
    
    $response->getBody()->write(json_encode($responseData));
    return $response->withHeader('Content-Type', 'application/json');
});
$app->delete('/deleteOrderItem/{id}', function (Request $request, Response $response, $args) {
    $id = $args['id'];
    $db = new DbOperation();
    $result = $db->deleteOrderItem($id);
    
    $responseData = array();
    if ($result) {
        $responseData['error'] = false;
        $responseData['message'] = "Order item deleted successfully";
    } else {
        $responseData['error'] = true;
        $responseData['message'] = "Failed to delete order item";
    }
    
    $response->getBody()->write(json_encode($responseData));
    return $response->withHeader('Content-Type', 'application/json');
});
$app->post('/addDeal', function (Request $request, Response $response) {
    $requestData = json_decode($request->getBody());
    
    $name = $requestData->name;
    $expire_at = $requestData->expire_at;
    $cost = $requestData->cost;
    $sale = $requestData->sale;
    $id = $requestData->id;
    $dealItem = $requestData->items;
    $created_at = $requestData->create_at;

    $db = new DbOperation();
    $responseData = array();
    if($db->isDealsIdExist($id)){
        $updated = $db->updateDeal(
            $requestData->id ?? null,
            $requestData->name ?? null,
            $requestData->expire_at ?? null,
            $requestData->cost ?? null,
            $requestData->sale ?? null,
            $requestData->create_at ?? null
        );
    
     
            foreach ($requestData->items as $item) {
                if($db->isDealItemExist($item->id)){
                    $db->UpdateDealItem(
                        $id ,
                        $item->id,
                        $item->product_id,
                        $item->quantity,

                        $item->sizeType
                    );
                } else {
                    $db->addDealItem($requestData->id , $item->product_id, $item->quantity, $item->sizeType, $item->id);
                }
            }
   
        $responseData['error'] = false;
        $responseData['message'] = "Deal updated successfully";
    }else{
        $result = $db->addDeal($name, $expire_at, $cost, $sale, $id,$created_at);
        if ($result) {
            foreach ($dealItem as $dealItem) {
                $db->addDealItem($id, $dealItem->product_id, $dealItem->quantity, $dealItem->sizeType, $dealItem->id);
            }
            $responseData['error'] = false;
            $responseData['message'] = "Deal added successfully";
        } else {
            $responseData['error'] = true;
            $responseData['message'] = "Failed to add deal".$result ;
        }
    }
    
  
    
    
    $response->getBody()->write(json_encode($responseData));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/deals', function (Request $request, Response $response) {
    $db = new DbOperation();
    $deals = $db->getAllDeals();
    $response->getBody()->write(json_encode($deals));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/deals/{id}', function (Request $request, Response $response, array $args) {
    $id = (int)$args['id'];
    $db = new DbOperation();
    $deal = $db->getDealById($id);
    if (!empty($deal)) {
        $response->getBody()->write(json_encode($deal));
        return $response->withHeader('Content-Type', 'application/json');
    }
    $response->getBody()->write(json_encode(['error' => true, 'message' => 'Deal not found']));
    return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
});

$app->delete('/deleteDeal/{id}', function (Request $request, Response $response, array $args) {
    $id = (int)$args['id'];
    $db = new DbOperation();
    // delete items first to satisfy FK constraints (if any)
    $db->deleteDealItems($id);
    $deleted = $db->deleteDeal($id);
    $responseData = $deleted
        ? ['error' => false, 'message' => 'Deal deleted successfully']
        : ['error' => true, 'message' => 'Failed to delete deal'];
    $response->getBody()->write(json_encode($responseData));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
