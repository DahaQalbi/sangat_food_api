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
    function Login($email, $password,$role)
    {
        $stmt = $this->con->prepare("SELECT `id`, `email`, `role`, `created_at`, `phone`, `name` FROM `users` WHERE email = ?  AND password = ? AND role = ?");
        $stmt->bind_param("sss", $email, $password,$role);
        $stmt->execute();
        $stmt->bind_result($id, $email, $role, $created_at, $phone, $name);

        $userData = array();
        while ($stmt->fetch()) {
            $data = array(
                'id' => $id,
                'email' => $email,
                'role' => $role,
                'created_at' => $created_at,
                'phone' => $phone,
                'name' => $name,
            );
            array_push($userData, $data);
        }
        return $userData;
    }
    function addOrder($tableNo, $orderType, $discount, $cost, $sale, $net, $status, $deal_id,$userId,$delivery_fee,$note,$delivery_type,$address,$sgst,$cgst)
    {
        $stmt = $this->con->prepare("INSERT INTO `orders`( `tableNo`, `orderType`, `discount`, `cost`, `sale`, `net`, `status`, `deal_id`, `userId`, `delivery_fee`, `note`, `delivery_type`,`address`,`sgst`, `cgst` ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssssiissssss", $tableNo, $orderType, $discount, $cost, $sale, $net, $status, $deal_id,$userId,$delivery_fee,$note,$delivery_type,$address,$sgst,$cgst);
        
        if ($stmt->execute()) {
            $orderId = $stmt->insert_id;
            $stmt->close();
            return $orderId;
        }
        $stmt->close();
        return false;
    }
    function orderDetails($order_id, $product_id, $size, $cost, $sale,$note)
    {
        $stmt = $this->con->prepare("INSERT INTO `order_details`( `order_id`, `product_id`, `size`, `cost`, `sale`, `note` ) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("iissss", $order_id, $product_id, $size, $cost, $sale,$note);
        
        if ($stmt->execute()) {
            $orderId = $stmt->insert_id;
            $stmt->close();
            return $orderId;
        }
        $stmt->close();
        return false;
    }
    function updateOrderDetails($id, $product_id, $size, $cost, $sale,$note)
    {
        $stmt = $this->con->prepare("UPDATE `order_details` SET `product_id` = ?, `size` = ?, `cost` = ?, `sale` = ?, `note` = ? WHERE `id` = ?");
        $stmt->bind_param("issssi",  $product_id, $size, $cost, $sale,$note,$id);
        
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
    
    function getAllOrders()
    {
 
       $stmt = $this->con->prepare("SELECT `id`, `tableNo`, `orderType`, `discount`, `cost`, `sale`, `net`, `status`, `create_at`, `deal_id`, `userId`, `delivery_fee`, `note`, `delivery_type`, `address`, `sgst`, `cgst`,(SELECT name FROM users WHERE id = userId) AS name,(SELECT name FROM deals WHERE id = deal_id) AS deal_name FROM `orders`");
       $stmt->execute();
       $stmt->bind_result($id,$tableNo,$orderType,$discount,$cost,$sale,$net,$status,$create_at,$deal_id,$userId,$delivery_fee,$note,$delivery_type,$address,$sgst,$cgst,$name,$deal_name);
       $userData = array();
       while ($stmt->fetch()) {
           $userData[$id] = array(
               'id' => $id,
               'tableNo' => $tableNo,
               'orderType' => $orderType,
               'discount' => $discount,
               'cost' => $cost,
               'orderItems' => array(),
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
               'deal_name' => $deal_name
           );
       }


       $stmt = $this->con->prepare("SELECT `id`, `order_id`, `product_id`, `size`, `cost`, `sale`,`note`, `created_at`,(SELECT image FROM products WHERE id = order_details.`product_id`) AS image,(SELECT name FROM products WHERE id = order_details.`product_id`) AS name FROM `order_details` WHERE `order_id` = ?");
       foreach ($userData as $id => &$item) {
           $stmt->bind_param("i", $id);
           $stmt->execute();
           $stmt->bind_result($id,$order_id,$product_id,$size,$cost,$sale,$note,$created_at,$image,$name);
           while ($stmt->fetch()) {
               $sizeData = array(
                   'id' => $id,
                   'order_id' => $order_id,
                   'product_id' => $product_id,
                   'size' => $size,
                   'cost' => $cost,
                   'sale' => $sale,
                   'note' => $note,
                   'created_at' => $created_at,
                   'image' => $image,
                   'name' => $name

               );
               $item['orderItems'][] = $sizeData;
           }
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
    
    function updateOrder($id, $tableNo, $product_id, $sizeType_id, $quantity, $discount, $cost, $sale, $net, $status,$deal_id,$userId,$delivery_fee,$note,$delivery_type,$address,$sgst,$cgst)
    {
        $stmt = $this->con->prepare("UPDATE `orders` SET `tableNo` = ?, `product_id` = ?, `sizeType_id` = ?, `quantity` = ?, `discount` = ?, `cost` = ?, `sale` = ?, `net` = ?, `status` = ?, `deal_id`=?,`userId`=?,`delivery_fee`=?,`note`=?,`delivery_type`=?,`address`=?,`sgst`=?,`cgst`= ? WHERE `id` = ?");
        $stmt->bind_param("siiisssssiissssssi", $tableNo, $product_id, $sizeType_id, $quantity, $discount, $cost, $sale, $net, $status, $deal_id,$userId,$delivery_fee,$note,$delivery_type,$address,$sgst,$cgst, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    function addDeal($name, $expire_at, $cost, $sale)
    {
        $stmt = $this->con->prepare("INSERT INTO `deals`(`name`, `expire_at`, `cost`, `sale`) VALUES (?,?,?,?)");
        $stmt->bind_param("ssss", $name, $expire_at, $cost, $sale);
        if ($stmt->execute()) {
            $insertId = $stmt->insert_id;
            $stmt->close();
            return $insertId; // return deal ID to be used for adding items
        }
        $stmt->close();
        return false;
    }
    
    function addDealItem($deal_id, $product_id, $quantity, $sizeType)
    {
        $stmt = $this->con->prepare("INSERT INTO `deal_item`(`deal_id`, `product_id`, `quantity`, `sizeType`) VALUES (?,?,?,?)");
        $stmt->bind_param("iiis", $deal_id, $product_id, $quantity, $sizeType);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    // Fetch all deals with their items
    function getAllDeals()
    {
        $stmt = $this->con->prepare("SELECT `id`, `name`, `expire_at`, `cost`, `sale`, `create_at` FROM `deals` ORDER BY id DESC");
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
        $itemStmt = $this->con->prepare("SELECT `id`, `deal_id`, `product_id`, `sizeType`, `quantity`,(SELECT image FROM products WHERE id = deal_item.`product_id`) AS image,(SELECT name FROM products WHERE id = deal_item.`product_id`) AS name FROM `deal_item` WHERE `deal_id` = ?");
        foreach ($deals as $dealId => &$deal) {
            $itemStmt->bind_param("i", $dealId);
            $itemStmt->execute();
            $itemStmt->bind_result($item_id, $d_id, $product_id, $sizeType, $quantity,$image,$name);
            while ($itemStmt->fetch()) {
                $deal['items'][] = array(
                    'id' => $item_id,
                    'deal_id' => $d_id,
                    'product_id' => $product_id,
                    'sizeType' => $sizeType,
                    'quantity' => $quantity,
                    'image' => $image,
                    'name' => $name
                );
            }
        }
        $itemStmt->close();

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
    function updateDeal($id, $name, $expire_at, $cost, $sale)
    {
        $stmt = $this->con->prepare("UPDATE `deals` SET `name` = ?, `expire_at` = ?, `cost` = ?, `sale` = ? WHERE `id` = ?");
        $stmt->bind_param("ssssi", $name, $expire_at, $cost, $sale, $id);
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
    

    function addManager($email, $password, $role, $phone, $name,$cnic,$image,$agrement)
    {
        // First check if email already exists
        if ($this->isUserExist($email)) {
            return USER_ALREADY_EXIST;
        }
        if($cnic){
            date_default_timezone_set("America/Los_Angeles");
            $time = date("ymd");
            $id = '' . $time . '' . microtime(true);
            $upload_path = "../images/$id.jpg";
            $cnic = substr($upload_path, 3);  
            file_put_contents($upload_path, base64_decode($cnic));

        }
        if($image){
            date_default_timezone_set("America/Los_Angeles");
            $time = date("ymd");
            $id = '' . $time . '' . microtime(true);
            $upload_path = "../images/$id.jpg";
            $image = substr($upload_path, 3);  
            file_put_contents($upload_path, base64_decode($image));

        }
        if($agrement){
            date_default_timezone_set("America/Los_Angeles");
            $time = date("ymd");
            $id = '' . $time . '' . microtime(true);
            $upload_path = "../images/$id.jpg";
            $agrement = substr($upload_path, 3);  
            file_put_contents($upload_path, base64_decode($agrement));

        }
        $stmt = $this->con->prepare("INSERT INTO `users`(`email`, `password`, `role`, `phone`, `name`,`cnic`, `image`, `agrement`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $email, $password, $role, $phone, $name,$cnic,$image,$agrement);
        
        if ($stmt->execute()) {

            return USER_UPDATED;
        }
        return USER_NOT_UPDATED;
    }
    
    function getManagerById($id)
    {
        $stmt = $this->con->prepare("SELECT `id`, `email`, `role`, `created_at`, `phone`, `name`,cnic`, `image`, `agrement` FROM `users` WHERE `id` = ? AND `role` = 'manager'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($id, $email, $role, $created_at, $phone, $name,$cnic,$image,$agrement);
        
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
                'agrement' => $agrement
            );
        }
        $stmt->close();
        return $manager;
    }
    function getAllManager()
    {
        $stmt = $this->con->prepare("SELECT `id`, `email`,`password`, `role`, `created_at`, `phone`, `name`,`cnic`, `image`, `agrement` FROM `users` WHERE `role` = 'manager'");
        $stmt->execute();
        $stmt->bind_result($id, $email, $password, $role, $created_at, $phone, $name,$cnic,$image,$agrement);
        
        $managers = array();
        while ($stmt->fetch()) {
            $manager = array(
                'id' => $id,
                'email' => $email,
                'password' => $password,
                'role' => $role,
                'created_at' => $created_at,
                'phone' => $phone,
                'name' => $name,
                'cnic' => $cnic,
                'image' => $image,
                'agrement' => $agrement
            );
            array_push($managers, $manager);
        }
        $stmt->close();
        return $managers;
    }
    function updateManager($id, $email, $password, $phone, $name,$cnic,$image,$agrement)
    {
        // Check if email is being updated and if it already exists
        $currentUser = $this->getManagerById($id);
        if ($currentUser && $currentUser['email'] !== $email && $this->isUserExist($email)) {
            return USER_ALREADY_EXIST;
        }
        $isCnicBase64 = (base64_decode($cnic, true) !== false);
        if($cnic && $isCnicBase64){
            date_default_timezone_set("America/Los_Angeles");
            $time = date("ymd");
            $id = '' . $time . '' . microtime(true);
            $upload_path = "../images/$id.jpg";
            $cnic = substr($upload_path, 3);  
            file_put_contents($upload_path, base64_decode($cnic));

        }
        $isImageBase64 = (base64_decode($image, true) !== false);
        if($image &&  $isImageBase64){
            date_default_timezone_set("America/Los_Angeles");
            $time = date("ymd");
            $id = '' . $time . '' . microtime(true);
            $upload_path = "../images/$id.jpg";
            $image = substr($upload_path, 3);  
            file_put_contents($upload_path, base64_decode($image));

        }
        $isAgrementBase64 = (base64_decode($agrement, true) !== false);
        if($agrement && $isAgrementBase64){
            date_default_timezone_set("America/Los_Angeles");
            $time = date("ymd");
            $id = '' . $time . '' . microtime(true);
            $upload_path = "../images/$id.jpg";
            $agrement = substr($upload_path, 3);  
            file_put_contents($upload_path, base64_decode($agrement));

        }
        if (!empty($password)) {
            // Update with password
            $stmt = $this->con->prepare("UPDATE `users` SET `email` = ?, `password` = ?, `phone` = ?, `name` = ?, `cnic` = ?, `image` = ?, `agrement` = ? WHERE `id` = ?");
            $stmt->bind_param("sssssssi", $email, $password, $phone, $name, $cnic, $image, $agrement, $id);
        } else {
            // Update without changing password
            $stmt = $this->con->prepare("UPDATE `users` SET `email` = ?, `phone` = ?, `name` = ?, `cnic` = ?, `image` = ?, `agrement` = ? WHERE `id` = ?");
            $stmt->bind_param("ssssssi", $email, $phone, $name, $cnic, $image, $agrement, $id);
        }
        
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

    function addWaiter($uid, $name, $phone, $email, $address, $cnic)
    {
        // Check if waiter with same phone, email or CNIC already exists for this user
        if ($this->isWaiterExist($uid, $phone, $email, $cnic)) {
            return USER_ALREADY_EXIST;
        }
        
        $stmt = $this->con->prepare("INSERT INTO `waiters`(`uid`, `name`, `phone`, `email`, `address`, `cnic`) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $uid, $name, $phone, $email, $address, $cnic);
        
        if ($stmt->execute()) {
            $stmt->close();
            return USER_UPDATED;
        }
        $stmt->close();
        return USER_NOT_UPDATED;
    }
    
    function getAllWaiters($uid)
    {
        $stmt = $this->con->prepare("SELECT * FROM `waiters` WHERE `uid` = ? ORDER BY `id` DESC");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $result = $stmt->get_result();
        $waiters = array();
        
        while ($row = $result->fetch_assoc()) {
            $waiters[] = $row;
        }
        
        $stmt->close();
        return $waiters;
    }
    function getAllWaitersWithoutId()
    {
        $stmt = $this->con->prepare("SELECT * FROM `users` WHERE `role` = 'waiter' ORDER BY `id` DESC");
        $stmt->execute();
        $result = $stmt->get_result();
        $waiters = array();
        
        while ($row = $result->fetch_assoc()) {
            $waiters[] = $row;
        }
        
        $stmt->close();
        return $waiters;
    }
    
    function getWaiterById($id, $uid = null)
    {
        $query = "SELECT * FROM `waiters` WHERE `id` = ?";
        $types = "i";
        $params = array($id);
        
        if ($uid !== null) {
            $query .= " AND `uid` = ?";
            $types .= "i";
            $params[] = $uid;
        }
        
        $stmt = $this->con->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $waiter = array();
        
        if ($row = $result->fetch_assoc()) {
            $waiter = $row;
        }
        
        $stmt->close();
        return $waiter;
    }
    
    function updateWaiter($id, $uid, $name, $phone, $email, $address, $cnic)
    {
        // Check if another waiter with same phone, email or CNIC already exists for this user
        if ($this->isAnotherWaiterExist($id, $phone, $email, $cnic, $uid)) {
            return USER_ALREADY_EXIST;
        }
        
        $stmt = $this->con->prepare("UPDATE `waiters` SET `name` = ?, `phone` = ?, `email` = ?, `address` = ?, `cnic` = ? WHERE `id` = ? AND `uid` = ?");
        $stmt->bind_param("sssssii", $name, $phone, $email, $address, $cnic, $id, $uid);
        
        if ($stmt->execute()) {
            $stmt->close();
            return USER_UPDATED;
        }
        $stmt->close();
        return USER_NOT_UPDATED;
    }
    
    function deleteWaiter($id)
    {
        $stmt = $this->con->prepare("DELETE FROM `waiters` WHERE `id` = ? ");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    private function isWaiterExist($uid, $phone, $email, $cnic)
    {
        $stmt = $this->con->prepare("SELECT id FROM waiters WHERE uid = ? AND (phone = ? OR email = ? OR cnic = ?)");
        $stmt->bind_param("isss", $uid, $phone, $email, $cnic);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
    
    private function isAnotherWaiterExist($id, $phone, $email, $cnic, $uid = null)
    {
        $query = "SELECT id FROM waiters WHERE id != ? AND (phone = ? OR email = ? OR cnic = ?";
        $types = "isss";
        $params = array($id, $phone, $email, $cnic);
        
        if ($uid !== null) {
            $query .= ") AND uid = ?";
            $types .= "i";
            $params[] = $uid;
        } else {
            $query .= ")";
        }
        
        $stmt = $this->con->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
    
    function addCategory($category)
    {
        // Check if category already exists
        if ($this->isCategoryExist($category)) {
            return CATAGORY_EXIST;
        }
        
        $stmt = $this->con->prepare("INSERT INTO `categories`(`category`) VALUES (?)");
        $stmt->bind_param("s", $category);

        if ($stmt->execute()) {
            $stmt->close();
            return CATAGORY_CREATED;
        }
        $stmt->close();
        return CATAGORY_FAILED;
    }
    
    function getAllCategories()
    {
        $stmt = $this->con->prepare("SELECT * FROM `categories` ORDER BY `category` ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        $categories = array();
        
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        
        $stmt->close();
        return $categories;
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
        // Check if category is being used in products table
        $stmt = $this->con->prepare("SELECT id FROM products WHERE category_id = ? LIMIT 1");
        $stmt->bind_param("i", $categoryId);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
    function addProduct($category_id, $name,$image)
    {
        // Check if product with same name already exists in this category
        if ($this->isProductExist($category_id, $name)) {
            return PRODUCT_EXIST;
        }
        date_default_timezone_set("America/Los_Angeles");
        $time = date("ymd");
        $id = '' . $time . '' . microtime(true);
        $upload_path = "../images/$id.jpg";
        $upload = substr($upload_path, 3);  
        $stmt = $this->con->prepare("INSERT INTO `products`(`category_id`, `name`, `image`) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $category_id, $name, $upload);
        
        if ($stmt->execute()) {
            file_put_contents($upload_path, base64_decode($image));
            $insertedId = $stmt->insert_id;
            return $insertedId;
        }
        $stmt->close();
        return PRODUCT_FAILED;
    }
    function getAllProducts()
    {

        $stmt = $this->con->prepare("SELECT products.id AS productId,products.name,products.image,products.create_at,categories.category,(SELECT COUNT(*) FROM order_details WHERE order_details.product_id = products.id) AS totalOrder FROM `products` JOIN categories ON products.category_id = categories.id");
        $stmt->execute();
        $stmt->bind_result($productId,$name,$image,$create_at,$category,$totalOrder);
        $userData = array();
        while ($stmt->fetch()) {
            $userData[$productId] = array(
                'productId' => $productId,
                'name' => $name,
                'sizeType' => array(),
                'image' => $image,
                'create_at' => $create_at,
                'category' => $category,
                'totalOrder' => $totalOrder
            );
        }


        $stmt = $this->con->prepare("SELECT id AS sizeTypeId,type,cost,sale FROM `sizeType` WHERE product_id = ?");
        foreach ($userData as $productId => &$item) {
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $stmt->bind_result($sizeTypeId,$type,$cost,$sale);
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
    
    function updateProduct($id, $category_id, $name,$image)
    {
        // Check if another product with the same name exists in this category
        if ($this->isAnotherProductExist($id, $category_id, $name)) {
            return PRODUCT_EXIST;
        }
        $isBase64 = (base64_decode($image, true) !== false);
        if($isBase64){
            date_default_timezone_set("America/Los_Angeles");
            $time = date("ymd");
            $ids = '' . $time . '' . microtime(true);
            $upload_path = "../images/$ids.jpg";
            $upload = substr($upload_path, 3);  
            $stmt = $this->con->prepare("UPDATE `products` SET `category_id` = ?, `name` = ?,`image` = ? WHERE `id` = ?");
            $stmt->bind_param("isss", $category_id, $name,$upload, $id);
            file_put_contents($upload_path, base64_decode($image));
        }else{
            $stmt = $this->con->prepare("UPDATE `products` SET `category_id` = ?, `name` = ? WHERE `id` = ?");
            $stmt->bind_param("isi", $category_id, $name, $id);
        }
        
        if ($stmt->execute()) {
            $stmt->close();
            return PRODUCT_UPDATED;
        }
        $stmt->close();
        return PRODUCT_NOT_UPDATED;
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



   
    function UpdateCategory($name, $id)
    {
        // Check if another category with the same name exists
        if ($this->isAnotherCategoryExist($id, $name)) {
            return CATAGORY_EXIST;
        }
        
        $stmt = $this->con->prepare("UPDATE `categories` SET `category` = ? WHERE `id` = ?");
        $stmt->bind_param("si", $name, $id);
        
        if ($stmt->execute()) {
            $stmt->close();
            return CATAGORY_UPDATED;
        }
        $stmt->close();
        return CATAGORY_NOT_UPDATED;
    }


    function addSizeType($product_id, $type, $cost, $sale)
    {
        // Check if size type already exists for this product
        if ($this->isSizeTypeExist($product_id, $type)) {
            return SIZE_TYPE_EXIST;
        }
        
        $stmt = $this->con->prepare("INSERT INTO `sizeType`(`product_id`, `type`, `cost`, `sale`) VALUES (?,?,?,?)");
        $stmt->bind_param("isss", $product_id, $type, $cost, $sale);
        
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
    
    function updateSizeType($id, $type, $cost, $sale)
    {
        // Get the current size type to check product_id
        $current = $this->getSizeTypeById($id);
        if (empty($current)) {
            return SIZE_TYPE_NOT_FOUND;
        }
        
        // Check if another size type with same type exists for this product
        if ($this->isAnotherSizeTypeExist($id, $current['product_id'], $type)) {
            return SIZE_TYPE_EXIST;
        }
        
        $stmt = $this->con->prepare("UPDATE `sizeType` SET `type` = ?, `cost` = ?, `sale` = ? WHERE `id` = ?");
        $stmt->bind_param("sssi", $type, $cost, $sale, $id);
        
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
