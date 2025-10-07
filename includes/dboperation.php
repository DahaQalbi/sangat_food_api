<?php

use phpDocumentor\Reflection\DocBlock\Description;

use function PHPSTORM_META\type;

class DbOperation
{
    private $con;
    function __construct()
    {
        require_once dirname(__FILE__) . '/dbconnect.php';
        $db = new DbConnect();
        $this->con = $db->connect();
    }
    function Login($email, $password, $role)
    {
        $stmt = $this->con->prepare("SELECT `id`, `email`, `role`, `created_at`, `phone`, `name`, `cnic`, `image`, `agrement`, `status`,`password` FROM `users` WHERE email = ?  AND password = ? AND role = ?");
        $stmt->bind_param("sss", $email, $password, $role);
        $stmt->execute();
        $stmt->bind_result($id, $email, $role, $created_at, $phone, $name, $cnic, $image, $agrement, $status,$password);

        $userData = array();
        while ($stmt->fetch()) {
            $data = array(
                'id' => $id,
                'email' => $email,
                'role' => $role,
                'created_at' => $created_at,
                'phone' => $phone,
                'name' => $name,
                'cnic' => $cnic,
                'image' => $image,
                'agrement' => $agrement,
                'status' => $status,
                'password' => $password,
                    );
            array_push($userData, $data);
        }
        return $userData;
    }
        
    function deleteRecord($key, $table,$id)
    {
        if($table === "users" && $id === 1 ){
            return 'Record deleted';
        }
          $stmt = $this->con->prepare("DELETE FROM `$table` WHERE `$key` = ?");
         $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            return 'Record deleted';
        }

        return "Record no deleted";
    }
    function deletedRecord($tableKey, $tid, $tableName, $deletedBy, $create_at)
    {
        $stmt = $this->con->prepare("INSERT INTO `deleteRecord`(`tableKey`, `tid`, `tableName`, `deletedBy`, `create_at`) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sisis", $tableKey, $tid, $tableName, $deletedBy, $create_at);

        if ($stmt->execute()) {
            $orderId = $stmt->insert_id;
            $stmt->close();
            return $orderId;
        }
        $stmt->close();
        return false;
    }
    function addOrder($id, $tableNo, $orderType, $discount, $cost, $sale, $net, $status, $deal_id, $userId, $delivery_fee, $note, $delivery_type, $address, $sgst, $cgst,$orderTakerId,$orderNumber,$create_at)
    {
        $stmt = $this->con->prepare("INSERT INTO `orders`( `id`, `tableNo`, `orderType`, `discount`, `cost`, `sale`, `net`, `status`, `deal_id`, `userId`, `delivery_fee`, `note`, `delivery_type`,`address`,`sgst`, `cgst`, `orderTakerId`, `orderNumber`, `create_at`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("isssssssisssssssiss", $id, $tableNo, $orderType, $discount, $cost, $sale, $net, $status, $deal_id, $userId, $delivery_fee, $note, $delivery_type, $address, $sgst, $cgst,$orderTakerId,$orderNumber,$create_at);

        if ($stmt->execute()) {
            $orderId = $stmt->insert_id;
            $stmt->close();
            return $orderId;
        }
        $stmt->close();
        return false;
    }
    function orderDetails($order_id, $product_id, $size, $cost, $sale, $note,$quantity,$id)
    {
        $stmt = $this->con->prepare("INSERT INTO `order_details`( `order_id`, `product_id`, `size`, `cost`, `sale`, `note`,`quantity`, `id`) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("iisssssi", $order_id, $product_id, $size, $cost, $sale, $note,$quantity,$id);

        if ($stmt->execute()) {
            $orderId = $stmt->insert_id;
            $stmt->close();
            return $orderId;
        }
        $stmt->close();
        return false;
    }
    function updateOrderDetails($id, $product_id, $size, $cost, $sale, $note,$quantity)
    {
        $stmt = $this->con->prepare("UPDATE `order_details` SET `product_id` = ?, `size` = ?, `cost` = ?, `sale` = ?, `note` = ?, `quantity` = ? WHERE `id` = ?");
        $stmt->bind_param("isssssi",  $product_id, $size, $cost, $sale, $note, $quantity, $id);

        if ($stmt->execute()) {
            $orderId = $stmt->insert_id;
            $stmt->close();
            return $orderId;
        }
        $stmt->close();
        return false;
    }
    function getOrderById($id)
    {
        $stmt = $this->con->prepare("SELECT o.*, p.name as product_name, st.type as size_type 
                                  FROM `orders` o 
                                  LEFT JOIN `products` p ON o.product_id = p.id 
                                  LEFT JOIN `sizeType` st ON o.sizeType_id = st.id 
                                  WHERE o.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = array();

        if ($row = $result->fetch_assoc()) {
            $order = $row;
        }

        $stmt->close();
        return $order;
    }

    function getOrdersByTable($tableNo, $status = null)
    {
        $query = "SELECT o.*, p.name as product_name, st.type as size_type 
                 FROM `orders` o 
                 LEFT JOIN `products` p ON o.product_id = p.id 
                 LEFT JOIN `sizeType` st ON o.sizeType_id = st.id 
                 WHERE o.tableNo = ?";

        $params = array($tableNo);
        $types = "s";

        if ($status !== null) {
            $query .= " AND o.status = ?";
            $params[] = $status;
            $types .= "s";
        }

        $query .= " ORDER BY o.order_date DESC";

        $stmt = $this->con->prepare($query);

        if (count($params) > 1) {
            $stmt->bind_param($types, ...$params);
        } else {
            $stmt->bind_param($types, $params[0]);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $orders = array();

        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }

        $stmt->close();
        return $orders;
    }
    function getAllOrdersByID($id)
    {

        $stmt = $this->con->prepare("SELECT `orderTakerId`,`id`, `tableNo`, `orderType`, `discount`, `cost`, `sale`, `net`, `status`, `create_at`, `deal_id`, `userId`, `delivery_fee`, `note`, `delivery_type`, `address`, `sgst`, `cgst`,(SELECT name FROM users WHERE id = userId) AS name,(SELECT name FROM deals WHERE id = deal_id) AS deal_name,(SELECT name FROM users WHERE id = orderTakerId) AS orderTaker,(SELECT role FROM users WHERE id = orderTakerId) AS orderTakerRole,`orderNumber`  FROM `orders` WHERE `orderTakerId` = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($orderTakerId,$id, $tableNo, $orderType, $discount, $cost, $sale, $net, $status, $create_at, $deal_id, $userId, $delivery_fee, $note, $delivery_type, $address, $sgst, $cgst, $name, $deal_name, $orderTaker, $orderTakerRole,$orderNumber);
        $userData = array();
        while ($stmt->fetch()) {
            $userData[$id] = array(
                'orderTakerId' => $orderTakerId,
                'id' => $id,
                'tableNo' => $tableNo,
                'orderType' => $orderType,
                'discount' => $discount,
                'cost' => $cost,
                'orderDetails' => array(),
                'sale' => $sale,
                'net' => $net,
                'status' => $status,
                'create_at' => $create_at,
                'deal_id' => $deal_id,
                'userId' => $userId,
                'delivery_fee' => $delivery_fee,
                'note' => $note,
                'delivery_type' => $delivery_type,
                'address' => $address,
                'sgst' => $sgst,
                'cgst' => $cgst,
                'customerName' => $name,
                'deal_name' => $deal_name,
                'orderTaker' => $orderTaker,
                'orderTakerRole' => $orderTakerRole,
                'orderNumber' => $orderNumber
            );
        }


        $stmt = $this->con->prepare("SELECT `id`, `order_id`,`quantity`, `product_id`, `size`, `cost`, `sale`,`note`, `created_at`,(SELECT image FROM products WHERE id = order_details.`product_id`) AS image,(SELECT name FROM products WHERE id = order_details.`product_id`) AS name FROM `order_details` WHERE `order_id` = ?");
        foreach ($userData as $id => &$item) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->bind_result($id, $order_id, $quantity, $product_id, $size, $cost, $sale, $note, $created_at, $image, $name);
            while ($stmt->fetch()) {
                $sizeData = array(
                    'id' => $id,
                    'order_id' => $order_id,
                    'quantity' => $quantity,
                    'product_id' => $product_id,
                    'size' => $size,
                    'cost' => $cost,
                    'sale' => $sale,
                    'note' => $note,
                    'created_at' => $created_at,
                    'image' => $image,
                    'name' => $name

                );
                $item['orderDetails'][] = $sizeData;
            }
        }

        return array_values($userData);
    }
    function getUnSyncOrders($time)
    {

        $stmt = $this->con->prepare("SELECT `orderTakerId`,`id`, `tableNo`, `orderType`, `discount`, `cost`, `sale`, `net`, `status`, `create_at`, `deal_id`, `userId`, `delivery_fee`, `note`, `delivery_type`, `address`, `sgst`, `cgst`,(SELECT name FROM users WHERE id = userId) AS name,(SELECT name FROM deals WHERE id = deal_id) AS deal_name,(SELECT name FROM users WHERE id = orderTakerId) AS orderTaker,(SELECT role FROM users WHERE id = orderTakerId) AS orderTakerRole,`orderNumber`  FROM `orders` WHERE `create_at` >= ? ");
        $stmt->bind_param("s", $time);
        $stmt->execute();
        $stmt->bind_result($orderTakerId,$id, $tableNo, $orderType, $discount, $cost, $sale, $net, $status, $create_at, $deal_id, $userId, $delivery_fee, $note, $delivery_type, $address, $sgst, $cgst, $name, $deal_name, $orderTaker, $orderTakerRole,$orderNumber);
        $userData = array();
        while ($stmt->fetch()) {
            $userData[$id] = array(
                'orderTakerId' => $orderTakerId,
                'id' => $id,
                'tableNo' => $tableNo,
                'orderType' => $orderType,
                'discount' => $discount,
                'cost' => $cost,
                'orderDetails' => array(),
                'sale' => $sale,
                'net' => $net,
                'status' => $status,
                'create_at' => $create_at,
                'deal_id' => $deal_id,
                'userId' => $userId,
                'delivery_fee' => $delivery_fee,
                'note' => $note,
                'delivery_type' => $delivery_type,
                'address' => $address,
                'sgst' => $sgst,
                'cgst' => $cgst,
                'customerName' => $name,
                'deal_name' => $deal_name,
                'orderTaker' => $orderTaker,
                'orderTakerRole' => $orderTakerRole,
                'orderNumber' => $orderNumber
            );
        }


        $stmt = $this->con->prepare("SELECT `id`, `order_id`,`quantity`, `product_id`, `size`, `cost`, `sale`,`note`, `created_at`,(SELECT image FROM products WHERE id = order_details.`product_id`) AS image,(SELECT name FROM products WHERE id = order_details.`product_id`) AS name FROM `order_details` WHERE `order_id` = ?");
        foreach ($userData as $id => &$item) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->bind_result($id, $order_id, $quantity, $product_id, $size, $cost, $sale, $note, $created_at, $image, $name);
            while ($stmt->fetch()) {
                $sizeData = array(
                    'id' => $id,
                    'order_id' => $order_id,
                    'quantity' => $quantity,
                    'product_id' => $product_id,
                    'size' => $size,
                    'cost' => $cost,
                    'sale' => $sale,
                    'note' => $note,
                    'created_at' => $created_at,
                    'image' => $image,
                    'name' => $name

                );
                $item['orderDetails'][] = $sizeData;
            }
        }

        return array_values($userData);
    }
    function getAllOrders()
    {

        $stmt = $this->con->prepare("SELECT `orderTakerId`,`id`, `tableNo`, `orderType`, `discount`, `cost`, `sale`, `net`, `status`, `create_at`, `deal_id`, `userId`, `delivery_fee`, `note`, `delivery_type`, `address`, `sgst`, `cgst`,(SELECT name FROM users WHERE id = userId) AS name,(SELECT name FROM deals WHERE id = deal_id) AS deal_name,(SELECT name FROM users WHERE id = orderTakerId) AS orderTaker,(SELECT role FROM users WHERE id = orderTakerId) AS orderTakerRole,`orderNumber`  FROM `orders`");
        $stmt->execute();
        $stmt->bind_result( $orderTakerId,$id, $tableNo, $orderType, $discount, $cost, $sale, $net, $status, $create_at, $deal_id, $userId, $delivery_fee, $note, $delivery_type, $address, $sgst, $cgst, $name, $deal_name, $orderTaker, $orderTakerRole,$orderNumber);
        $userData = array();
        while ($stmt->fetch()) {
            $userData[$id] = array(
                'orderTakerId' => $orderTakerId,
                'id' => $id,
                'tableNo' => $tableNo,
                'orderType' => $orderType,
                'discount' => $discount,
                'cost' => $cost,
                'orderDetails' => array(),
                'sale' => $sale,
                'net' => $net,
                'status' => $status,
                'create_at' => $create_at,
                'deal_id' => $deal_id,
                'userId' => $userId,
                'delivery_fee' => $delivery_fee,
                'note' => $note,
                'delivery_type' => $delivery_type,
                'address' => $address,
                'sgst' => $sgst,
                'cgst' => $cgst,
                'customerName' => $name,
                'deal_name' => $deal_name,
                'orderTaker' => $orderTaker,
                'orderTakerRole' => $orderTakerRole,
                'orderNumber' => $orderNumber
            );
        }


        $stmt = $this->con->prepare("SELECT `id`, `order_id`,`quantity`, `product_id`, `size`, `cost`, `sale`,`note`, `created_at`,(SELECT image FROM products WHERE id = order_details.`product_id`) AS image,(SELECT name FROM products WHERE id = order_details.`product_id`) AS name FROM `order_details` WHERE `order_id` = ?");
        foreach ($userData as $id => &$item) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->bind_result($id, $order_id, $quantity, $product_id, $size, $cost, $sale, $note, $created_at, $image, $name);
            while ($stmt->fetch()) {
                $sizeData = array(
                    'id' => $id,
                    'order_id' => $order_id,
                    'quantity' => $quantity,
                    'product_id' => $product_id,
                    'size' => $size,
                    'cost' => $cost,
                    'sale' => $sale,
                    'note' => $note,
                    'created_at' => $created_at,
                    'image' => $image,
                    'name' => $name

                );
                $item['orderDetails'][] = $sizeData;
            }
        }

        return array_values($userData);
    }
    function getPendingOrder()
    {

        $stmt = $this->con->prepare("SELECT `id`, `tableNo`, `orderType`, `discount`, `cost`, `sale`, `net`, `status`, `create_at`, `deal_id`, `userId`, `delivery_fee`, `note`, `delivery_type`, `address`, `sgst`, `cgst`,(SELECT name FROM users WHERE id = userId) AS name,(SELECT name FROM deals WHERE id = deal_id) AS deal_name,(SELECT name FROM users WHERE id = orderTakerId) AS orderTaker,(SELECT role FROM users WHERE id = orderTakerId) AS orderTakerRole,`orderNumber`  FROM `orders` WHERE `isSync` = 'Pending' ORDER By create_at DESC");
        $stmt->execute();
        $stmt->bind_result($id, $tableNo, $orderType, $discount, $cost, $sale, $net, $status, $create_at, $deal_id, $userId, $delivery_fee, $note, $delivery_type, $address, $sgst, $cgst, $name, $deal_name, $orderTaker, $orderTakerRole,$orderNumber);
        $userData = array();
        while ($stmt->fetch()) {
            $userData[$id] = array(
                'id' => $id,
                'tableNo' => $tableNo,
                'orderType' => $orderType,
                'discount' => $discount,
                'cost' => $cost,
                'orderDetails' => array(),
                'sale' => $sale,
                'net' => $net,
                'status' => $status,
                'create_at' => $create_at,
                'deal_id' => $deal_id,
                'userId' => $userId,
                'delivery_fee' => $delivery_fee,
                'note' => $note,
                'delivery_type' => $delivery_type,
                'address' => $address,
                'sgst' => $sgst,
                'cgst' => $cgst,
                'customerName' => $name,
                'deal_name' => $deal_name,
                'orderTaker' => $orderTaker,
                'orderTakerRole' => $orderTakerRole,
                'orderNumber' => $orderNumber
            );
        }


        $stmt = $this->con->prepare("SELECT `id`, `order_id`,`quantity`, `product_id`, `size`, `cost`, `sale`,`note`, `created_at`,(SELECT image FROM products WHERE id = order_details.`product_id`) AS image,(SELECT name FROM products WHERE id = order_details.`product_id`) AS name FROM `order_details` WHERE `order_id` = ?");
        foreach ($userData as $id => &$item) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->bind_result($id, $order_id, $quantity, $product_id, $size, $cost, $sale, $note, $created_at, $image, $name);
            while ($stmt->fetch()) {
                $sizeData = array(
                    'id' => $id,
                    'order_id' => $order_id,
                    'quantity' => $quantity,
                    'product_id' => $product_id,
                    'size' => $size,
                    'cost' => $cost,
                    'sale' => $sale,
                    'note' => $note,
                    'created_at' => $created_at,
                    'image' => $image,
                    'name' => $name

                );
                $item['orderDetails'][] = $sizeData;
            }
        }
        $stmt = $this->con->prepare("UPDATE `orders` SET `isSync`= 'Completed' WHERE id = ?");
        foreach ($userData as $id => &$item) {
            $stmt->bind_param("i", $id);
            $stmt->execute();

        }
        

        return array_values($userData);
    }
    function updateOrderStatus($id, $status)
    {
        $stmt = $this->con->prepare("UPDATE `orders` SET `status` = ? WHERE `id` = ?");
        $stmt->bind_param("si", $status, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    function updateUserStatus($id, $status)
    {
        $stmt = $this->con->prepare("UPDATE `users` SET `status` = ? WHERE `id` = ?");
        $stmt->bind_param("si", $status, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    function updateOrder($id, $tableNo, $orderType, $discount, $cost, $sale, $net, $status, $deal_id, $userId, $delivery_fee, $note, $delivery_type, $address, $sgst, $cgst, $orderTakerId,$orderNumber,$create_at)
    {
        $stmt = $this->con->prepare("UPDATE `orders` SET `tableNo` = ?,`orderType` = ?, `discount` = ?, `cost` = ?, `sale` = ?, `net` = ?, `status` = ?, `deal_id`=?,`userId`=?,`delivery_fee`=?,`note`=?,`delivery_type`=?,`address`=?,`sgst`=?,`cgst`= ?,`orderTakerId`=?,`orderNumber`=?,`create_at`=? WHERE `id` = ?");
        $stmt->bind_param("sssssssssssssssissi", $tableNo, $orderType, $discount, $cost, $sale, $net, $status, $deal_id, $userId, $delivery_fee, $note, $delivery_type, $address, $sgst, $cgst, $orderTakerId,$orderNumber,$create_at, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    function addDeal($name, $expire_at, $cost, $sale, $id,$created_at)
    {
        $stmt = $this->con->prepare("INSERT INTO `deals`(`name`, `expire_at`, `cost`, `sale`,`id`,`create_at`) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("ssssis", $name, $expire_at, $cost, $sale,$id,$created_at);
        if ($stmt->execute()) {
            $stmt->close();
            return true; 
        }
        $stmt->close();
        return false;
    }

    function addDealItem($deal_id, $product_id, $quantity, $sizeType,$id)
    {

      
        $stmt = $this->con->prepare("INSERT INTO `deal_item`(`deal_id`, `product_id`, `quantity`, `sizeType`,`id`) VALUES (?,?,?,?,?)");
        $stmt->bind_param("iiisi", $deal_id, $product_id, $quantity, $sizeType,$id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    function UpdateDealItem($deal_id,$id, $product_id, $quantity, $sizeType)
    {
        $stmt = $this->con->prepare("UPDATE `deal_item` SET `deal_id` = ?, `product_id` = ?, `quantity` = ?, `sizeType` = ? WHERE `id` = ?");
        $stmt->bind_param("iiisi", $deal_id, $product_id, $quantity, $sizeType,$id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    

    // Fetch all deals with their items
// Fetch all deals with their items
function getAllDeals()
{
    $stmt = $this->con->prepare("SELECT `id`, `name`, `expire_at`, `cost`, `sale`, `create_at` 
                                 FROM `deals` ORDER BY id DESC");
    $stmt->execute();
    $stmt->bind_result($id, $name, $expire_at, $cost, $sale, $created_at);

    $deals = array();
    while ($stmt->fetch()) {
        $deals[$id] = array(
            'id' => $id,
            'name' => $name,
            'expire_at' => $expire_at,
            'cost' => $cost,
            'sale' => $sale,
            'created_at' => $created_at,
            'items' => array()
        );
    }
    $stmt->close();

    if (empty($deals)) {
        return array();
    }

    // Load items for each deal
    foreach ($deals as $dealId => &$deal) {
        $itemStmt = $this->con->prepare("SELECT `id`, `deal_id`, `product_id`, `sizeType`, `quantity`,
                                            (SELECT image FROM products WHERE id = deal_item.`product_id`) AS image,
                                            (SELECT name FROM products WHERE id = deal_item.`product_id`) AS name
                                         FROM `deal_item` WHERE `deal_id` = ?");
        $itemStmt->bind_param("i", $dealId);
        $itemStmt->execute();
        $itemStmt->bind_result($item_id, $d_id, $product_id, $sizeType, $quantity, $image, $pname);

        while ($itemStmt->fetch()) {
            $imageBase64 = null;
            if (!empty($image)) {
                $filePath = __DIR__ . "/../" . ltrim($image, '/');
                if (file_exists($filePath)) {
                    $mimeType = mime_content_type($filePath);
                    $fileData = file_get_contents($filePath);
                    $imageBase64 = "data:" . $mimeType . ";base64," . base64_encode($fileData);
                }
            }
            $deal['items'][] = array(
                'id' => $item_id,
                'deal_id' => $d_id,
                'product_id' => $product_id,
                'sizeType' => $sizeType,
                'quantity' => $quantity,
                'image' => $imageBase64,
                'name' => $pname
            );
        }
        $itemStmt->close();
    }

    return array_values($deals);
}
function getUnSyncDeals($time)
{
    $stmt = $this->con->prepare("SELECT `id`, `name`, `expire_at`, `cost`, `sale`, `create_at` 
                                 FROM `deals` WHERE `create_at`  >= ? ORDER BY id DESC");
    $stmt->bind_param("s",$time);                             
    $stmt->execute();
    $stmt->bind_result($id, $name, $expire_at, $cost, $sale, $created_at);

    $deals = array();
    while ($stmt->fetch()) {
        $deals[$id] = array(
            'id' => $id,
            'name' => $name,
            'expire_at' => $expire_at,
            'cost' => $cost,
            'sale' => $sale,
            'created_at' => $created_at,
            'items' => array()
        );
    }
    $stmt->close();

    if (empty($deals)) {
        return array();
    }

    // Load items for each deal
    foreach ($deals as $dealId => &$deal) {
        $itemStmt = $this->con->prepare("SELECT `id`, `deal_id`, `product_id`, `sizeType`, `quantity`,
                                            (SELECT image FROM products WHERE id = deal_item.`product_id`) AS image,
                                            (SELECT name FROM products WHERE id = deal_item.`product_id`) AS name
                                         FROM `deal_item` WHERE `deal_id` = ?");
        $itemStmt->bind_param("i", $dealId);
        $itemStmt->execute();
        $itemStmt->bind_result($item_id, $d_id, $product_id, $sizeType, $quantity, $image, $pname);

        while ($itemStmt->fetch()) {
            $imageBase64 = null;
            if (!empty($image)) {
                $filePath = __DIR__ . "/../" . ltrim($image, '/');
                if (file_exists($filePath)) {
                    $mimeType = mime_content_type($filePath);
                    $fileData = file_get_contents($filePath);
                    $imageBase64 = "data:" . $mimeType . ";base64," . base64_encode($fileData);
                }
            }
            $deal['items'][] = array(
                'id' => $item_id,
                'deal_id' => $d_id,
                'product_id' => $product_id,
                'sizeType' => $sizeType,
                'quantity' => $quantity,
                'image' => $imageBase64,
                'name' => $pname
            );
        }
        $itemStmt->close();
    }

    return array_values($deals);
}

    // Fetch single deal by ID with items
    function getDealById($id)
    {
        $stmt = $this->con->prepare("SELECT `id`, `name`, `expire_at`, `cost`, `sale`, `created_at` FROM `deals` WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($d_id, $name, $expire_at, $cost, $sale, $created_at);
        $deal = array();
        if ($stmt->fetch()) {
            $deal = array(
                'id' => $d_id,
                'name' => $name,
                'expire_at' => $expire_at,
                'cost' => $cost,
                'sale' => $sale,
                'created_at' => $created_at,
                'items' => array()
            );
        }
        $stmt->close();

        if (empty($deal)) {
            return $deal;
        }

        $itemStmt = $this->con->prepare("SELECT `id`, `deal_id`, `product_id`, `sizeType`, `quantity` FROM `deal_item` WHERE `deal_id` = ?");
        $itemStmt->bind_param("i", $id);
        $itemStmt->execute();
        $itemStmt->bind_result($item_id, $deal_id, $product_id, $sizeType, $quantity);
        while ($itemStmt->fetch()) {
            $deal['items'][] = array(
                'id' => $item_id,
                'deal_id' => $deal_id,
                'product_id' => $product_id,
                'sizeType' => $sizeType,
                'quantity' => $quantity
            );
        }
        $itemStmt->close();

        return $deal;
    }

    // Update base deal (does not touch items)
    function updateDeal($id, $name, $expire_at, $cost, $sale,$create_at)
    {
        $stmt = $this->con->prepare("UPDATE `deals` SET `name` = ?, `expire_at` = ?, `cost` = ?, `sale` = ?, `create_at` = ? WHERE `id` = ?");
        $stmt->bind_param("sssssi", $name, $expire_at, $cost, $sale, $create_at, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Delete all items of a deal
    function deleteDealItems($deal_id)
    {
        $stmt = $this->con->prepare("DELETE FROM `deal_item` WHERE `deal_id` = ?");
        $stmt->bind_param("i", $deal_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Delete a deal (use with deleteDealItems first for FK integrity if needed)
    function deleteDeal($id)
    {
        $stmt = $this->con->prepare("DELETE FROM `deals` WHERE `id` = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    function deleteOrder($id)
    {
        $stmt = $this->con->prepare("DELETE FROM `orders` WHERE `id` = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    function deleteOrderItem($id)
    {
        $stmt = $this->con->prepare("DELETE FROM `order_details` WHERE `id` = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }


    function addManager($email, $password, $role, $phone, $name, $cnic, $image, $agrement, $extention,$userid,$created_at)
    {
        // First check if email already exists
        if ($this->isUserExist($email)) {
            return USER_ALREADY_EXIST;
        }
        $cnicPath =$cnic;
        $imagePath =$image;
        $agrementPath =$agrement;
        if ($cnic) {
            date_default_timezone_set("America/Los_Angeles");
            $time = date("ymd");
            $id1 = '' . $time . '' . microtime(true);
            $upload_path1 = "../images/$id1.jpg";
            $cnicPath = substr($upload_path1, 3);
            $cnic = explode(',', $cnic, 2)[1];

            file_put_contents($upload_path1, base64_decode($cnic));
        }
        if ($image) {
            date_default_timezone_set("America/Los_Angeles");
            $time = date("ymd");
            $id2 = '' . $time . '' . microtime(true);
            $upload_path2 = "../images/$id2.jpg";
            $imagePath = substr($upload_path2, 3);
            $image = explode(',', $image, 2)[1];
            file_put_contents($upload_path2, base64_decode($image));
        }
        if ($agrement) {
            date_default_timezone_set("America/Los_Angeles");
            $time = date("ymd");
            $id3 = '' . $time . '' . microtime(true);
            $upload_path3 = "../images/$id3." . $extention;
            $agrementPath = substr($upload_path3, 3);
            $agrement = explode(',', $agrement, 2)[1];
            file_put_contents($upload_path3, base64_decode($agrement));
        }
        $stmt = $this->con->prepare("INSERT INTO `users`(`email`, `password`, `role`, `phone`, `name`,`cnic`, `image`, `agrement`,`id`,`created_at`) VALUES (?,?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssis", $email, $password, $role, $phone, $name, $cnicPath, $imagePath, $agrementPath,$userid,$created_at);

        if ($stmt->execute()) {

            return USER_UPDATED;
        }
        return USER_NOT_UPDATED;
    }

    function getManagerById($id)
    {
        $stmt = $this->con->prepare("SELECT `id`, `email`, `role`, `created_at`, `phone`, `name`,`cnic`, `image`, `agrement`,`createdBy`,(SELECT name FROM users WHERE id = `createdBy` LIMIT 1) AS userName FROM `users` WHERE `createdBy` = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($id, $email, $role, $created_at, $phone, $name, $cnic, $image, $agrement,$createdBy,$userName);

        $manager = array();
        if ($stmt->fetch()) {
            $manager = array(
                'id' => $id,
                'email' => $email,
                'role' => $role,
                'created_at' => $created_at,
                'phone' => $phone,
                'name' => $name,
                'cnic' => $cnic,
                'image' => $image,
                'agrement' => $agrement,
                'createdBy' => $createdBy,
                'userName' => $userName
            );
        }
        $stmt->close();
        return $manager;
    }
    function getAllManager()
    {
        $stmt = $this->con->prepare("SELECT `id`, `email`,`password`, `role`, `created_at`, `phone`, `name`,`cnic`, `image`, `agrement`,`status` FROM `users` Where `role` != 'admin'");
        $stmt->execute();
        $stmt->bind_result($id, $email, $password, $role, $created_at, $phone, $name, $cnic, $image, $agrement, $status);

        $managers = array();
        while ($stmt->fetch()) {
            $imageBase64 = null;
            $agrementBase64 = null;
            $cnicBase64 = null;
        if (!empty($image)) {
            // Build full path to the file (adjust path to your setup)
            $filePath = __DIR__ . "/../" . ltrim($image, '/');

            if (file_exists($filePath)) {
                $mimeType = mime_content_type($filePath);
                $fileData = file_get_contents($filePath);
                $imageBase64 = "data:" . $mimeType . ";base64," . base64_encode($fileData);
            }
        }
        if (!empty($agrement)) {
            // Build full path to the file (adjust path to your setup)
            $filePath = __DIR__ . "/../" . ltrim($agrement, '/');

            if (file_exists($filePath)) {
                $mimeType = mime_content_type($filePath);
                $fileData = file_get_contents($filePath);
                $agrementBase64 = "data:" . $mimeType . ";base64," . base64_encode($fileData);
            }
        }
        if (!empty($cnic)) {
            // Build full path to the file (adjust path to your setup)
            $filePath = __DIR__ . "/../" . ltrim($cnic, '/');

            if (file_exists($filePath)) {
                $mimeType = mime_content_type($filePath);
                $fileData = file_get_contents($filePath);
                $cnicBase64 = "data:" . $mimeType . ";base64," . base64_encode($fileData);
            }
        }
            $manager = array(
                'id' => $id,
                'email' => $email,
                'password' => $password,
                'role' => $role,
                'created_at' => $created_at,
                'phone' => $phone,
                'name' => $name,
                'image'=>$imageBase64,
                'agrement'=>$agrementBase64,
                'cnic'=>$cnicBase64,
                'status' => $status
            );
            array_push($managers, $manager);
        }
        $stmt->close();
        return $managers;
    }
    function getUnSyncManager($time)
    {
        $stmt = $this->con->prepare("SELECT `id`, `email`,`password`, `role`, `created_at`, `phone`, `name`,`cnic`, `image`, `agrement`,`status` FROM `users` Where `role` != 'admin' AND `created_at` >= ? ");
        $stmt->bind_param("s", $time);
        $stmt->execute();
        $stmt->bind_result($id, $email, $password, $role, $created_at, $phone, $name, $cnic, $image, $agrement, $status);

        $managers = array();
        while ($stmt->fetch()) {
            $imageBase64 = null;
            $agrementBase64 = null;
            $cnicBase64 = null;
        // if (!empty($image)) {
        //     // Build full path to the file (adjust path to your setup)
        //     $filePath = __DIR__ . "/../" . ltrim($image, '/');

        //     if (file_exists($filePath)) {
        //         $mimeType = mime_content_type($filePath);
        //         $fileData = file_get_contents($filePath);
        //         $imageBase64 = "data:" . $mimeType . ";base64," . base64_encode($fileData);
        //     }
        // }
        // if (!empty($agrement)) {
        //     // Build full path to the file (adjust path to your setup)
        //     $filePath = __DIR__ . "/../" . ltrim($agrement, '/');

        //     if (file_exists($filePath)) {
        //         $mimeType = mime_content_type($filePath);
        //         $fileData = file_get_contents($filePath);
        //         $agrementBase64 = "data:" . $mimeType . ";base64," . base64_encode($fileData);
        //     }
        // }
        // if (!empty($cnic)) {
        //     // Build full path to the file (adjust path to your setup)
        //     $filePath = __DIR__ . "/../" . ltrim($cnic, '/');

        //     if (file_exists($filePath)) {
        //         $mimeType = mime_content_type($filePath);
        //         $fileData = file_get_contents($filePath);
        //         $cnicBase64 = "data:" . $mimeType . ";base64," . base64_encode($fileData);
        //     }
        // }
            $manager = array(
                'id' => $id,
                'email' => $email,
                'password' => $password,
                'role' => $role,
                'created_at' => $created_at,
                'phone' => $phone,
                'name' => $name,
                'image' => $image,
                'agrement' => $agrement,
                'cnic' => $cnic,
                'status' => $status
            );
            array_push($managers, $manager);
        }
        $stmt->close();
        return $managers;
    }
    function getFinance()
    {
        // Main finance stats
        $stmt = $this->con->prepare("
            SELECT
                (SELECT COUNT(*) FROM orders WHERE DATE(create_at) = CURDATE()) AS todays_orders,
                (SELECT COUNT(*) FROM orders WHERE DATE(create_at) = CURDATE() - INTERVAL 1 DAY) AS yesterdays_orders,
                (SELECT IFNULL(SUM(sale - cost), 0) FROM orders WHERE DATE(create_at) = CURDATE()) AS todays_profit,
                (SELECT IFNULL(SUM(sale - cost), 0) FROM orders WHERE DATE(create_at) = CURDATE() - INTERVAL 1 DAY) AS yesterdays_profit,
                (SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE() AND role = 'consumers') AS todays_customer,
                (SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE() - INTERVAL 1 DAY AND role = 'consumers') AS yesterdays_customer
        ");
        $stmt->execute();
        $stmt->bind_result(
            $todays_orders,
            $yesterdays_orders,
            $todays_profit,
            $yesterdays_profit,
            $todays_customer,
            $yesterdays_customer
        );
    
        $finance = array();
    
        if ($stmt->fetch()) {
            $stmt->close();
    
            // Monthly report
            $stmt2 = $this->con->prepare("
                SELECT 
                    DATE(create_at) AS order_date, 
                    SUM(sale) AS total_sale, 
                    SUM(cost) AS total_cost
                FROM orders
                WHERE YEAR(create_at) = YEAR(CURDATE()) 
                  AND MONTH(create_at) = MONTH(CURDATE())
                GROUP BY DATE(create_at)
                ORDER BY order_date ASC
            ");
            $stmt2->execute();
            $stmt2->bind_result($order_date, $total_sale, $total_cost);
    
            $monthlyReport = array();
            while ($stmt2->fetch()) {
                $monthlyReport[] = array(
                    'order_date' => $order_date,
                    'total_sale' => $total_sale,
                    'total_cost' => $total_cost,
                    'profit'     => $total_sale - $total_cost
                );
            }
            $stmt2->close();
    
            // Top 5 products
            $stmt3 = $this->con->prepare("
                SELECT 
                    od.product_id,
                    p.name AS product_name,
                    p.image AS product_image,
                    SUM(od.quantity) AS total_quantity,
                    SUM(od.sale) AS total_sale
                FROM order_details od
                JOIN products p ON od.product_id = p.id
                GROUP BY od.product_id, p.name
                ORDER BY total_quantity DESC
                LIMIT 5
            ");
            $stmt3->execute();
            $stmt3->bind_result($product_id, $product_name,$product_image, $total_quantity, $product_sale);
    
            $topProducts = array();
            while ($stmt3->fetch()) {
                $topProducts[] = array(
                    'product_id'     => $product_id,
                    'product_name'   => $product_name,
                    'product_image'  => $product_image,
                    'total_quantity' => $total_quantity,
                    'total_sale'     => $product_sale
                );
            }
            $stmt3->close();
    
            // Final finance report
            $finance[] = array(
                'todays_orders'      => $todays_orders,
                'yesterdays_orders'  => $yesterdays_orders,
                'todays_profit'      => $todays_profit,
                'yesterdays_profit'  => $yesterdays_profit,
                'todays_customer'    => $todays_customer,
                'yesterdays_customer'=> $yesterdays_customer,
                'monthly_report'     => $monthlyReport,
                'top_products'       => $topProducts
            );
        } else {
            $stmt->close();
        }
    
        return $finance;
    }
    
    
    
    function updateManager($id, $email, $password, $phone, $name, $created_at,$role)
    {
        // Check if email is being updated and if it already exists
        $currentUser = $this->getManagerById($id);
        if ($currentUser && $currentUser['email'] !== $email && $this->isUserExist($email)) {
            return USER_ALREADY_EXIST;
        }
        

            // Update with password
            $stmt = $this->con->prepare("UPDATE `users` SET `email` = ?, `password` = ?, `phone` = ?, `name` = ?, `created_at` = ?,`role` = ? WHERE `id` = ?");
            $stmt->bind_param("ssssssi", $email, $password, $phone, $name, $created_at,$role, $id);
      

        if ($stmt->execute()) {
            $stmt->close();
            return USER_UPDATED;
        }
        $stmt->close();
        return USER_NOT_UPDATED;
    }

    function deleteManager($id)
    {
        $stmt = $this->con->prepare("DELETE FROM `users` WHERE `id` = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    function isUserExist($email)
    {
        $stmt = $this->con->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
    function isDealItemExist($id)
    {
        $stmt = $this->con->prepare("SELECT id FROM deal_item WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

     function isCategoryIdExist($id)
    {
        $stmt = $this->con->prepare("SELECT id FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
    function isUsersIdExist($id)
    {
        $stmt = $this->con->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
    function isDealsIdExist($id)
    {
        $stmt = $this->con->prepare("SELECT id FROM deals WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
    function isOrderIdExist($id)
    {
        $stmt = $this->con->prepare("SELECT id FROM orders WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
    function isSizeTypeIdExist($id)
    {
        $stmt = $this->con->prepare("SELECT id FROM sizeType WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
    function isProductIdExist($id)
    {
        $stmt = $this->con->prepare("SELECT id FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
    function isGalleryIdExist($id)
    {
        $stmt = $this->con->prepare("SELECT id FROM gallery WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    function  addCategoryWithId($id, $category, $create_at)
    {


        $stmt = $this->con->prepare("INSERT INTO `categories`(`id`, `category`, `create_at`) VALUES (?,?,?)");
        $stmt->bind_param("iss", $id, $category, $create_at);

        if ($stmt->execute()) {
            $stmt->close();
            return CATAGORY_CREATED;
        }
        $stmt->close();
        return CATAGORY_FAILED;
    }
    function getAllCategories()
    {
        $stmt = $this->con->prepare("SELECT * FROM `categories` ORDER BY `create_at` ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        $categories = array();

        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }

        $stmt->close();
        return $categories;
    }
    function getUnSyncCategories($time)
    {
        $stmt = $this->con->prepare("SELECT * FROM `categories` WHERE `create_at` >= ?  ORDER BY `create_at` ASC");
        $stmt->bind_param("s", $time);
        $stmt->execute();
        $result = $stmt->get_result();
        $categories = array();

        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }

        $stmt->close();
        return $categories;
    }
    function getUnSyncDeletedRecord($time)
    {
        $stmt = $this->con->prepare("SELECT * FROM `deleteRecord` WHERE `create_at` >= ?  ORDER BY `create_at` ASC");
        $stmt->bind_param("s", $time);
        $stmt->execute();
        $result = $stmt->get_result();
        $deleteRecords = array();

        while ($row = $result->fetch_assoc()) {
            $deleteRecords[] = $row;
        }

        $stmt->close();
        return $deleteRecords;
    }
    function getCategoryById($id)
    {
        $stmt = $this->con->prepare("SELECT * FROM `categories` WHERE `id` = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $category = array();

        if ($row = $result->fetch_assoc()) {
            $category = $row;
        }

        $stmt->close();
        return $category;
    }

    // updateCategory method is now implemented as UpdateCategory (with capital U) below
    // This is to maintain consistency with the existing codebase naming convention

    function deleteCategory($id)
    {
        // First check if this is the duplicate method at the bottom of the file
        if (func_num_args() === 1 && is_numeric($id)) {
            // This is the correct implementation
            // Check if category is being used in any products
            if ($this->isCategoryInUse($id)) {
                return false;
            }

            $stmt = $this->con->prepare("DELETE FROM `categories` WHERE `id` = ?");
            $stmt->bind_param("i", $id);
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        } else if (func_num_args() === 1 && is_string($id)) {
            // This is the old implementation that should be removed
            $cid = $id;
            $stmt = $this->con->prepare("DELETE FROM `catagory` WHERE `cat_id` = ?");
            $stmt->bind_param("i", $cid);
            if ($stmt->execute()) {
                return CATAGORY_DELETED;
            }
            return CATAGORY_NOT_DELETED;
        }
        return false;
    }
    function deleteAllCategory()
    {
        // First check if this is the duplicate method at the bottom of the file
        $stmt = $this->con->prepare("DELETE FROM `categories`");
        if ($stmt->execute()) {
            return CATAGORY_DELETED;
        }
        return CATAGORY_NOT_DELETED;
    }
    private function isCategoryExist($category)
    {
        $stmt = $this->con->prepare("SELECT id FROM categories WHERE LOWER(category) = LOWER(?)");
        $stmt->bind_param("s", $category);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    private function isAnotherCategoryExist($id, $category)
    {
        $stmt = $this->con->prepare("SELECT id FROM categories WHERE id != ? AND LOWER(category) = LOWER(?)");
        $stmt->bind_param("is", $id, $category);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    private function isCategoryInUse($categoryId)
    {

        $stmt = $this->con->prepare("SELECT id FROM products WHERE category_id = ? LIMIT 1");
        $stmt->bind_param("i", $categoryId);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
    function addGallery($product_id, $image,$ids)
    {
        date_default_timezone_set("America/Los_Angeles");
        $time = date("ymd");
        $id = '' . $time . '' . microtime(true);
        $upload_path = "../images/$id.jpg";
        $upload = substr($upload_path, 3);
        $stmt = $this->con->prepare("INSERT INTO `gallery`(`product_id`, `image`, `id`) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $product_id, $upload, $ids);

        if ($stmt->execute()) {
            file_put_contents($upload_path, base64_decode($image));
            $stmt->close();
            return true;
        }
        $stmt->close();
        return false;
    }
    function updateGallery($product_id, $image,$ids)
    {

       $imagePath =$image;
        $isImageBase64 = (base64_decode($image, true) !== false);
        if ($image &&  $isImageBase64) {
            date_default_timezone_set("America/Los_Angeles");
            $time = date("ymd");
            $id2 = '' . $time . '' . microtime(true);
            $upload_path2 = "../images/$id2.jpg";
            $imagePath = substr($upload_path2, 3);
            file_put_contents($upload_path2, base64_decode($image));
        }
        $stmt = $this->con->prepare("UPDATE `gallery` SET `image` = ? WHERE `product_id` = ? AND `id` = ?");
        $stmt->bind_param("isi", $imagePath, $product_id, $ids);

        if ($stmt->execute()) {
            $stmt->close();
            return true;
        }
        $stmt->close();
        return false;
    }
    function addProduct($category_id, $name, $image, $prepration_time, $isAvailable, $description,$productId,$create_at)
    {
        if ($this->isProductExist($category_id, $name)) {
            return PRODUCT_EXIST;
        }
        $image = explode(',', $image, 2)[1];
        date_default_timezone_set("America/Los_Angeles");
        $time = date("ymd");
        $id = '' . $time . '' . microtime(true);
        $upload_path = "../images/$id.jpg";
        $upload = substr($upload_path, 3);
       
        $stmt = $this->con->prepare("INSERT INTO `products`(`category_id`, `name`, `image`, `prepration_time`, `isAvailable`, `description`, `id`, `create_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssis", $category_id, $name, $upload, $prepration_time, $isAvailable, $description,$productId,$create_at);

        if ($stmt->execute()) {
            file_put_contents($upload_path, base64_decode($image));
            $insertedId = $stmt->insert_id;
            return $insertedId;
        }
        $stmt->close();
        return PRODUCT_FAILED;
    }
    function updateProduct($category_id, $name, $image, $prepration_time, $isAvailable, $description,$productId,$create_at)
    {
        if ($this->isProductExist($category_id, $name)) {
            return PRODUCT_EXIST;
        }
        $imagePath =$image;
        $isImageBase64 = (base64_decode($image, true) !== false);
        if ($image &&  $isImageBase64) {
            date_default_timezone_set("America/Los_Angeles");
            $time = date("ymd");
            $id2 = '' . $time . '' . microtime(true);
            $upload_path2 = "../images/$id2.jpg";
            $imagePath = substr($upload_path2, 3);
            file_put_contents($upload_path2, base64_decode($image));
        }
        $stmt = $this->con->prepare("UPDATE `products` SET `category_id` = ?, `name` = ?, `image` = ?, `prepration_time` = ?, `isAvailable` = ?, `description` = ?, `create_at` = ? WHERE `id` = ?");
        $stmt->bind_param("issssssi", $category_id, $name, $imagePath, $prepration_time, $isAvailable, $description,$create_at,$productId);

        if ($stmt->execute()) {
       
            return true;
        }
        $stmt->close();
        return PRODUCT_FAILED;
    }
    function getAllProducts()
    {

        $stmt = $this->con->prepare("SELECT products.id AS productId,products.name,products.image,products.create_at,categories.category,(SELECT COUNT(*) FROM order_details WHERE order_details.product_id = products.id) AS totalOrder,products.isAvailable,products.description,products.prepration_time FROM `products` JOIN categories ON products.category_id = categories.id");
        $stmt->execute();
        $stmt->bind_result($productId, $name, $image, $create_at, $category, $totalOrder, $isAvailable, $description, $prepration_time);
        $userData = array();
        while ($stmt->fetch()) {
            $imageBase64 = null;

            if (!empty($image)) {
                $filePath = __DIR__ . "/../" . ltrim($image, '/');
            
                if (file_exists($filePath)) {
                    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                    $mimeType = match ($ext) {
                        'jpg', 'jpeg' => 'image/jpeg',
                        'png'         => 'image/png',
                        'gif'         => 'image/gif',
                        default       => 'application/octet-stream',
                    };
            
                    $fileData = file_get_contents($filePath);
                    $imageBase64 = "data:" . $mimeType . ";base64," . base64_encode($fileData);
                }
            }
            $userData[$productId] = array(
                'id' => $productId,
                'productId' => $productId,
                'name' => $name,
                'sizeType' => array(),
                'create_at' => $create_at,
                'category' => $category,
                'totalOrder' => $totalOrder,
                'isAvailable' => $isAvailable,
                'description' => $description,
                'prepration_time' => $prepration_time,
                'image' => $imageBase64
            );
        }

        $stmt = $this->con->prepare("SELECT id AS sizeTypeId,type,cost,sale FROM `sizeType` WHERE product_id = ?");
        foreach ($userData as $productId => &$item) {
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $stmt->bind_result($sizeTypeId, $type, $cost, $sale);
            while ($stmt->fetch()) {
                $sizeData = array(
                    'sizeTypeId' => $sizeTypeId,
                    'type' => $type,
                    'cost' => $cost,
                    'sale' => $sale
                );
                $item['sizeType'][] = $sizeData;
            }
        }
        
        return array_values($userData);
    }
    function getUnSyncProducts($time)
    {

        $stmt = $this->con->prepare("SELECT products.id AS productId,products.name,products.image,products.create_at,categories.category,(SELECT COUNT(*) FROM order_details WHERE order_details.product_id = products.id) AS totalOrder,products.isAvailable,products.description,products.prepration_time FROM `products` JOIN categories ON products.category_id = categories.id WHERE products.create_at >= ? ");
        $stmt->bind_param("s", $time);
        $stmt->execute();
        $stmt->bind_result($productId, $name, $image, $create_at, $category, $totalOrder, $isAvailable, $description, $prepration_time);
        $userData = array();
        while ($stmt->fetch()) {
            $imageBase64 = null;
            if (!empty($image)) {
                $filePath = __DIR__ . "/../" . ltrim($image, '/');
                if (file_exists($filePath)) {
                    $mimeType = mime_content_type($filePath);
                    $fileData = file_get_contents($filePath);
                    $imageBase64 = "data:" . $mimeType . ";base64," . base64_encode($fileData);
                }
            }
            $userData[$productId] = array(
                'id' => $productId,
                'productId' => $productId,
                'name' => $name,
                'sizeType' => array(),

                'image' => $imageBase64,
                'create_at' => $create_at,
                'category' => $category,
                'totalOrder' => $totalOrder,
                'isAvailable' => $isAvailable,
                'description' => $description,
                'prepration_time' => $prepration_time
            );
        }


        $stmt = $this->con->prepare("SELECT id AS sizeTypeId,type,cost,sale FROM `sizeType` WHERE product_id = ?");
        foreach ($userData as $productId => &$item) {
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $stmt->bind_result($sizeTypeId, $type, $cost, $sale);
            while ($stmt->fetch()) {
                $sizeData = array(
                    'sizeTypeId' => $sizeTypeId,
                    'type' => $type,
                    'cost' => $cost,
                    'sale' => $sale
                );
                $item['sizeType'][] = $sizeData;
            }
        }
      
        return array_values($userData);
    }
    function getProductsByCategory($category_id)
    {
        $stmt = $this->con->prepare("SELECT p.*, c.category as category_name FROM `products` p 
                                  LEFT JOIN `categories` c ON p.category_id = c.id 
                                  WHERE p.category_id = ? 
                                  ORDER BY p.name ASC");
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $products = array();

        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }

        $stmt->close();
        return $products;
    }
    function getGalleryImage($product_id)
    {
        $stmt = $this->con->prepare("SELECT * FROM gallery WHERE product_id = ? ");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $products = array();

        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }

        $stmt->close();
        return $products;
    }
    function getProductById($id)
    {
        $stmt = $this->con->prepare("SELECT p.*, c.category as category_name FROM `products` p 
                                  LEFT JOIN `categories` c ON p.category_id = c.id 
                                  WHERE p.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = array();

        if ($row = $result->fetch_assoc()) {
            $product = $row;
        }

        $stmt->close();
        return $product;
    }

    
    function deleteProduct($id)
    {
        $stmt = $this->con->prepare("DELETE FROM `products` WHERE `id` = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    private function isProductExist($category_id, $name)
    {
        $stmt = $this->con->prepare("SELECT id FROM products WHERE category_id = ? AND LOWER(name) = LOWER(?)");
        $stmt->bind_param("is", $category_id, $name);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    private function isAnotherProductExist($id, $category_id, $name)
    {
        $stmt = $this->con->prepare("SELECT id FROM products WHERE id != ? AND category_id = ? AND LOWER(name) = LOWER(?)");
        $stmt->bind_param("iis", $id, $category_id, $name);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }




    function UpdateCategory($name, $id, $create_at)
    {
        // Check if another category with the same name exists
        if ($this->isAnotherCategoryExist($id, $name)) {
            return CATAGORY_EXIST;
        }

        $stmt = $this->con->prepare("UPDATE `categories` SET `category` = ?, `create_at` = ? WHERE `id` = ?");
        $stmt->bind_param("ssi", $name, $create_at, $id);

        if ($stmt->execute()) {
            $stmt->close();
            return CATAGORY_UPDATED;
        }
        $stmt->close();
        return CATAGORY_NOT_UPDATED;
    }


    function addSizeType($product_id, $type, $cost, $sale,$id)
    {
        // Check if size type already exists for this product
        if ($this->isSizeTypeExist($product_id, $type)) {
            return SIZE_TYPE_EXIST;
        }

        $stmt = $this->con->prepare("INSERT INTO `sizeType`(`product_id`, `type`, `cost`, `sale`, `id`) VALUES (?,?,?,?,?)");
        $stmt->bind_param("isssi", $product_id, $type, $cost, $sale,$id);

        if ($stmt->execute()) {
            $stmt->close();
            return SIZE_TYPE_CREATED;
        }
        $stmt->close();
        return SIZE_TYPE_FAILED;
    }

    function getSizeTypeById($id)
    {
        $stmt = $this->con->prepare("SELECT st.*, p.name as product_name 
                                  FROM `sizeType` st 
                                  LEFT JOIN `products` p ON st.product_id = p.id 
                                  WHERE st.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $sizeType = array();

        if ($row = $result->fetch_assoc()) {
            $sizeType = $row;
        }

        $stmt->close();
        return $sizeType;
    }

    function getSizeTypesByProduct($product_id)
    {
        $stmt = $this->con->prepare("SELECT st.*, p.name as product_name 
                                  FROM `sizeType` st 
                                  LEFT JOIN `products` p ON st.product_id = p.id 
                                  WHERE st.product_id = ? 
                                  ORDER BY st.type ASC");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $sizeTypes = array();

        while ($row = $result->fetch_assoc()) {
            $sizeTypes[] = $row;
        }

        $stmt->close();
        return $sizeTypes;
    }

    function updateSizeType($id, $type, $cost, $sale,$product_id)
    {
      
        $stmt = $this->con->prepare("UPDATE `sizeType` SET `type` = ?, `cost` = ?, `sale` = ?,  `product_id` = ? WHERE `id` = ?");
        $stmt->bind_param("ssssi", $type, $cost, $sale, $product_id, $id);

        if ($stmt->execute()) {
            $stmt->close();
            return SIZE_TYPE_UPDATED;
        }
        $stmt->close();
        return SIZE_TYPE_NOT_UPDATED;
    }

    function deleteSizeType($id)
    {
        $stmt = $this->con->prepare("DELETE FROM `sizeType` WHERE `id` = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result ? SIZE_TYPE_DELETED : SIZE_TYPE_NOT_DELETED;
    }

    private function isSizeTypeExist($product_id, $type)
    {
        $stmt = $this->con->prepare("SELECT id FROM sizeType WHERE product_id = ? AND LOWER(type) = LOWER(?)");
        $stmt->bind_param("is", $product_id, $type);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    private function isAnotherSizeTypeExist($id, $product_id, $type)
    {
        $stmt = $this->con->prepare("SELECT id FROM sizeType WHERE id != ? AND product_id = ? AND LOWER(type) = LOWER(?)");
        $stmt->bind_param("iis", $id, $product_id, $type);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
}
