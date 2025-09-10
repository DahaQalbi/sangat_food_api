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
        $responseData['message'] = "Invalid Credential" . count($db->Login($email, $password,$role));
    }
    $response->getBody()->write(json_encode($responseData));
});

// Add a new manager
$app->post('/addManager', function (Request $request, Response $response) {
    $requestData = json_decode($request->getBody());
    $email = $requestData->email;
    $password = $requestData->password;
    $role = $requestData->role;
    $phone = $requestData->phone;
    $name = $requestData->name;
    $cnic = $requestData->cnic;
    $image = $requestData->image;
    $agrement = $requestData->agrement;
    $db = new DbOperation();
    $responseData = array();
    if ($db->addManager($email, $password, $role, $phone, $name,$cnic,$image,$agrement) === USER_UPDATED) {
        $responseData['error'] = false;
        $responseData['message'] = "Manager added successfully";
    } else if ($db->addManager($email, $password, $role, $phone, $name,$cnic,$image,$agrement) === USER_ALREADY_EXIST) {
        $responseData['error'] = true;
        $responseData['message'] = "Manager with this email already exists";
    } else {
        $responseData['error'] = true;
        $responseData['message'] = "Failed to add manager";
    }
    $response->getBody()->write(json_encode($responseData));
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


// Update manager details
$app->put('/updateManager', function (Request $request, Response $response) {
    $requestData = json_decode($request->getBody());
    $id = $requestData->id;
    $email = $requestData->email;
    $password = $requestData->password ?? null;
    $phone = $requestData->phone;
    $name = $requestData->name;
    $cnic = $requestData->cnic;
    $image = $requestData->image;
    $agrement = $requestData->agrement;
    $db = new DbOperation();
    $responseData = array();
    
    $result = $db->updateManager($id, $email, $password, $phone, $name,$cnic,$image,$agrement);
    
    if ($result === USER_UPDATED) {
        $responseData['error'] = false;
        $responseData['message'] = "Manager updated successfully";
    } else if ($result === USER_NOT_UPDATED) {
        $responseData['error'] = true;
        $responseData['message'] = "Failed to update manager";
    } else if ($result === USER_ALREADY_EXIST) {
        $responseData['error'] = true;
        $responseData['message'] = "Email already exists";
    }
    
    $response->getBody()->write(json_encode($responseData));
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



// Add a new waiter
$app->post('/addWaiter', function (Request $request, Response $response) {
    $requestData = json_decode($request->getBody());
    $uid = $requestData->uid;
    $name = $requestData->name;
    $phone = $requestData->phone;
    $email = $requestData->email;
    $address = $requestData->address;
    $cnic = $requestData->cnic;
   
    $db = new DbOperation();
    $responseData = array();
    $result = $db->addWaiter($uid, $name, $phone, $email, $address, $cnic);
    
    if ($result === USER_UPDATED) {
        $responseData['error'] = false;
        $responseData['message'] = "Waiter added successfully";
    } else if ($result === USER_ALREADY_EXIST) {
        $responseData['error'] = true;
        $responseData['message'] = "Waiter with this phone, email or CNIC already exists";
    } else {
        $responseData['error'] = true;
        $responseData['message'] = "Failed to add waiter";
    }
    $response->getBody()->write(json_encode($responseData));
});

// Get all waiters for a user
$app->get('/getAllWaiters/{uid}', function (Request $request, Response $response) {
    $uid = $request->getAttribute('uid');
    $db = new DbOperation();
    $result = $db->getAllWaiters($uid);
    $response->getBody()->write(json_encode($result));
});
$app->get('/allWaiters', function (Request $request, Response $response) {
    
    $db = new DbOperation();
    $result = $db->getAllWaitersWithoutId();
    $response->getBody()->write(json_encode($result));
    return $response->withHeader('Content-Type', 'application/json');
});

// Get waiter by ID
$app->get('/getWaiterById/{id}', function (Request $request, Response $response) {
    $id = $request->getAttribute('id');
    $db = new DbOperation();
    $result = $db->getWaiterById($id);
    $response->getBody()->write(json_encode($result));
});

// Update waiter details
$app->put('/updateWaiter', function (Request $request, Response $response) {
    $requestData = json_decode($request->getBody());
    $id = $requestData->id;
    $uid = $requestData->uid;
    $name = $requestData->name;
    $phone = $requestData->phone;
    $email = $requestData->email;
    $address = $requestData->address;
    $cnic = $requestData->cnic;
   
    $db = new DbOperation();
    $responseData = array();
    $result = $db->updateWaiter($id, $uid, $name, $phone, $email, $address, $cnic);
    
    if ($result === USER_UPDATED) {
        $responseData['error'] = false;
        $responseData['message'] = "Waiter updated successfully";
    } else if ($result === USER_ALREADY_EXIST) {
        $responseData['error'] = true;
        $responseData['message'] = "Waiter with this phone, email or CNIC already exists";
    } else {
        $responseData['error'] = true;
        $responseData['message'] = "Failed to update waiter";
    }
    $response->getBody()->write(json_encode($responseData));
});

// Delete waiter
$app->delete('/deleteWaiter/{id}', function (Request $request, Response $response) {
    $id = $request->getAttribute('id');
    
    $db = new DbOperation();
    $responseData = array();
    
    if ($db->deleteWaiter($id)) {
        $responseData['error'] = false;
        $responseData['message'] = "Waiter deleted successfully";
    } else {
        $responseData['error'] = true;
        $responseData['message'] = "Failed to delete waiter";
    }
    
    $response->getBody()->write(json_encode($responseData));
});

// Add a new category
$app->post('/addCategory', function (Request $request, Response $response) {
    $requestData = json_decode($request->getBody());
    $category = $requestData->category;
    $db = new DbOperation();
    $responseData = array();

    $result = $db->addCategory($category);
    
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

// Update category
$app->put('/updateCategory', function (Request $request, Response $response) {
    $requestData = json_decode($request->getBody());
    $id = $requestData->id;
    $category = $requestData->category;
    
    $db = new DbOperation();
    $responseData = array();
    
    $result = $db->updateCategory($id, $category);
    
    if ($result === CATAGORY_UPDATED) {
        $responseData['error'] = false;
        $responseData['message'] = "Category updated successfully";
    } else if ($result === CATAGORY_EXIST) {
        $responseData['error'] = true;
        $responseData['message'] = "Category with this name already exists";
    } else {
        $responseData['error'] = true;
        $responseData['message'] = "Failed to update category";
    }
    
    $response->getBody()->write(json_encode($responseData));
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
    $image = $requestData->image;
    $name = $requestData->name;
    $sizeType= $requestData->sizeType;
    $db = new DbOperation();
    $responseData = array();
    
    $result = $db->addProduct($category_id, $name,$image);
    
    if ($result !== PRODUCT_EXIST) {
        $responseData['error'] = false;
        $responseData['message'] = "Product added successfully";
        foreach ($sizeType as $sizeType) {
            $db->addSizeType($result, $sizeType->type, $sizeType->cost, $sizeType->sale);
        }
    } elseif ($result === PRODUCT_EXIST) {
        $responseData['error'] = true;
        $responseData['message'] = "Product already exists in this category";
    } else {
        $responseData['error'] = true;
        $responseData['message'] = "Failed to add product";
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

// Update Product
$app->put('/updateProduct', function (Request $request, Response $response) {
    $requestData = json_decode($request->getBody());
    $id = $requestData->id;
    $category_id = $requestData->category_id;
    $name = $requestData->name;
    $image = $requestData->image;
    
    $db = new DbOperation();
    $responseData = array();
    
    $result = $db->updateProduct($id, $category_id, $name,$image);
    
    if ($result === PRODUCT_UPDATED) {
        $responseData['error'] = false;
        $responseData['message'] = "Product updated successfully";
    } elseif ($result === PRODUCT_EXIST) {
        $responseData['error'] = true;
        $responseData['message'] = "Product with this name already exists in the selected category";
    } else {
        $responseData['error'] = true;
        $responseData['message'] = "Failed to update product";
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

// Update Size Type
$app->put('/updateSizeType', function (Request $request, Response $response) {
    $requestData = json_decode($request->getBody());
    $id = $requestData->id;
    $type = $requestData->type;
    $cost = $requestData->cost;
    $sale = $requestData->sale;
    
    $db = new DbOperation();
    $responseData = array();
    
    $result = $db->updateSizeType($id, $type, $cost, $sale);
    
    if ($result === SIZE_TYPE_UPDATED) {
        $responseData['error'] = false;
        $responseData['message'] = "Size type updated successfully";
    } elseif ($result === SIZE_TYPE_EXIST) {
        $responseData['error'] = true;
        $responseData['message'] = "This size type already exists for the product";
    } elseif ($result === SIZE_TYPE_NOT_FOUND) {
        $responseData['error'] = true;
        $responseData['message'] = "Size type not found";
    } else {
        $responseData['error'] = true;
        $responseData['message'] = "Failed to update size type";
    }
    
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
    $tableNo = $requestData->tableNo;
    $orderType = $requestData->orderType;
    $discount = $requestData->discount;
    $cost = $requestData->cost;
    $sale = $requestData->sale;
    $net = $requestData->net;
    $status = $requestData->status;
    $deal_id = $requestData->deal_id;
    $userId = $requestData->userId;
    $delivery_fee = $requestData->delivery_fee;
    $note = $requestData->note;
    $delivery_type = $requestData->delivery_type;
    $address = $requestData->address;
    $sgst = $requestData->sgst;
    $cgst = $requestData->cgst;
    $db = new DbOperation();
    $responseData = array();

    $res = $db->addOrder($tableNo, $orderType, $discount, $cost, $sale, $net, $status, $deal_id,$userId,$delivery_fee,$note,$delivery_type,$address,$sgst,$cgst);
    if ($res !== "UserNotAvailable") {
        foreach ($requestData->orderDetails as $orderDetail) {
            $db->orderDetails($res, $orderDetail->product_id, $orderDetail->size, $orderDetail->cost, $orderDetail->sale,$orderDetail->note);
        }
        $responseData['error'] = false;
        $responseData['message'] = "Order Form Submitted";
    } else {
        $responseData['error'] = true;
        $responseData['message'] = "Order Form Not Submitted";
    }
    $response->getBody()->write(json_encode($responseData));
});



// Get order by ID
$app->get('/getOrderById/{id}', function (Request $request, Response $response, $args) {
    $id = $args['id'];
    $db = new DbOperation();
    $order = $db->getOrderById($id);
    
    $responseData = array();
    if (!empty($order)) {
        $responseData['error'] = false;
        $responseData['order'] = $order;
    } else {
        $responseData['error'] = true;
        $responseData['message'] = "Order not found";
    }
    
    $response->getBody()->write(json_encode($responseData));
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

// Update order details
$app->put('/updateOrder', function (Request $request, Response $response) {
    $requestData = json_decode($request->getBody());
    
    $id = $requestData->id;
    $tableNo = $requestData->tableNo;
    $product_id = $requestData->product_id;
    $sizeType_id = $requestData->sizeType_id;
    $quantity = $requestData->quantity;
    $discount = $requestData->discount;
    $cost = $requestData->cost;
    $sale = $requestData->sale;
    $net = $requestData->net;
    $status = $requestData->status;
    $deal_id = $requestData->deal_id;
    $userId = $requestData->userId;
    $delivery_fee = $requestData->delivery_fee;
    $note = $requestData->note;
    $delivery_type = $requestData->delivery_type;
    $address = $requestData->address;
    $sgst = $requestData->sgst;
    $cgst = $requestData->cgst;
    
    $db = new DbOperation();
    $result = $db->updateOrder($id, $tableNo, $product_id, $sizeType_id, $quantity, $discount, $cost, $sale, $net, $status,$deal_id,$userId,$delivery_fee,$note,$delivery_type,$address,$sgst,$cgst);
    
    $responseData = array();
    if ($result) {
        foreach ($requestData->orderDetails as $orderDetail) {
            if($orderDetail->id){
                $db->updateOrderDetails($orderDetail->id, $orderDetail->product_id, $orderDetail->size, $orderDetail->cost, $orderDetail->sale,$orderDetail->note);
            } else {
                $db->orderDetails($id, $orderDetail->product_id, $orderDetail->size, $orderDetail->cost, $orderDetail->sale,$orderDetail->note);
            }
            
        }
        $responseData['error'] = false;
        $responseData['message'] = "Order updated successfully";
    } else {
        $responseData['error'] = true;
        $responseData['message'] = "Failed to update order";
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
$app->post('/addDeal', function (Request $request, Response $response) {
    $requestData = json_decode($request->getBody());
    
    $name = $requestData->name;
    $expire_at = $requestData->expire_at;
    $cost = $requestData->cost;
    $sale = $requestData->sale;
    $dealItem = $requestData->dealItem;
    $db = new DbOperation();
    $result = $db->addDeal($name, $expire_at, $cost, $sale);
    
    $responseData = array();
    if ($result) {
        foreach ($dealItem as $dealItem) {
            // addDealItem expects (deal_id, product_id, quantity, sizeType)
            $db->addDealItem($result, $dealItem->product_id, $dealItem->quantity, $dealItem->sizeType);
        }
        $responseData['error'] = false;
        $responseData['message'] = "Deal added successfully";
    } else {
        $responseData['error'] = true;
        $responseData['message'] = "Failed to add deal";
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

$app->put('/deals/{id}', function (Request $request, Response $response, array $args) {
    $id = (int)$args['id'];
    $requestData = json_decode($request->getBody());
    $db = new DbOperation();

    $updated = $db->updateDeal(
        $id,
        $requestData->name ?? null,
        $requestData->expire_at ?? null,
        $requestData->cost ?? null,
        $requestData->sale ?? null
    );

    if ($updated && isset($requestData->dealItem) && is_array($requestData->dealItem)) {
        // Replace items set if provided
        $db->deleteDealItems($id);
        foreach ($requestData->dealItem as $item) {
            $db->addDealItem($id, $item->product_id, $item->quantity, $item->sizeType);
        }
    }

    $responseData = $updated
        ? ['error' => false, 'message' => 'Deal updated successfully']
        : ['error' => true, 'message' => 'Failed to update deal'];

    $response->getBody()->write(json_encode($responseData));
    return $response->withHeader('Content-Type', 'application/json');
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
