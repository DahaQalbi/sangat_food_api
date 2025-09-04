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
    function addOrder($tableNo, $product_id, $sizeType_id, $quantity, $discount, $cost, $sale, $net, $status)
    {
        $stmt = $this->con->prepare("INSERT INTO `orders`(`tableNo`, `product_id`, `sizeType_id`, `quantity`, `discount`, `cost`, `sale`, `net`, `status`, `order_date`) VALUES (?,?,?,?,?,?,?,?,?, NOW())");
        $stmt->bind_param("siissssss", $tableNo, $product_id, $sizeType_id, $quantity, $discount, $cost, $sale, $net, $status);
        
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
    
    function getAllOrders($status = null, $limit = 100)
    {
        $query = "SELECT o.*, p.name as product_name, st.type as size_type 
                 FROM `orders` o 
                 LEFT JOIN `products` p ON o.product_id = p.id 
                 LEFT JOIN `sizeType` st ON o.sizeType_id = st.id";
        
        $params = array();
        $types = "";
        
        if ($status !== null) {
            $query .= " WHERE o.status = ?";
            $params[] = $status;
            $types .= "s";
        }
        
        $query .= " ORDER BY o.order_date DESC LIMIT ?";
        $params[] = $limit;
        $types .= "i";
        
        $stmt = $this->con->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $orders = array();
        
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        
        $stmt->close();
        return $orders;
    }
    
    function updateOrderStatus($id, $status)
    {
        $stmt = $this->con->prepare("UPDATE `orders` SET `status` = ? WHERE `id` = ?");
        $stmt->bind_param("si", $status, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    function updateOrder($id, $tableNo, $product_id, $sizeType_id, $quantity, $discount, $cost, $sale, $net, $status)
    {
        $stmt = $this->con->prepare("UPDATE `orders` SET `tableNo` = ?, `product_id` = ?, `sizeType_id` = ?, `quantity` = ?, `discount` = ?, `cost` = ?, `sale` = ?, `net` = ?, `status` = ? WHERE `id` = ?");
        $stmt->bind_param("siiisssssi", $tableNo, $product_id, $sizeType_id, $quantity, $discount, $cost, $sale, $net, $status, $id);
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
    

    function addManager($email, $password, $role, $phone, $name)
    {
        // First check if email already exists
        if ($this->isUserExist($email)) {
            return USER_ALREADY_EXIST;
        }
        
        $stmt = $this->con->prepare("INSERT INTO `users`(`email`, `password`, `role`, `phone`, `name`) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $email, $password, $role, $phone, $name);
        
        if ($stmt->execute()) {
            return USER_UPDATED;
        }
        return USER_NOT_UPDATED;
    }
    
    function getManagerById($id)
    {
        $stmt = $this->con->prepare("SELECT `id`, `email`, `role`, `created_at`, `phone`, `name` FROM `users` WHERE `id` = ? AND `role` = 'manager'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($id, $email, $role, $created_at, $phone, $name);
        
        $manager = array();
        if ($stmt->fetch()) {
            $manager = array(
                'id' => $id,
                'email' => $email,
                'role' => $role,
                'created_at' => $created_at,
                'phone' => $phone,
                'name' => $name
            );
        }
        $stmt->close();
        return $manager;
    }
    
    function updateManager($id, $email, $password, $phone, $name)
    {
        // Check if email is being updated and if it already exists
        $currentUser = $this->getManagerById($id);
        if ($currentUser && $currentUser['email'] !== $email && $this->isUserExist($email)) {
            return USER_ALREADY_EXIST;
        }
        
        if (!empty($password)) {
            // Update with password
            $stmt = $this->con->prepare("UPDATE `users` SET `email` = ?, `password` = ?, `phone` = ?, `name` = ? WHERE `id` = ? AND `role` = 'manager'");
            $stmt->bind_param("ssssi", $email, $password, $phone, $name, $id);
        } else {
            // Update without changing password
            $stmt = $this->con->prepare("UPDATE `users` SET `email` = ?, `phone` = ?, `name` = ? WHERE `id` = ? AND `role` = 'manager'");
            $stmt->bind_param("sssi", $email, $phone, $name, $id);
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
        $stmt = $this->con->prepare("DELETE FROM `users` WHERE `id` = ? AND `role` = 'manager'");
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
    
    function deleteWaiter($id, $uid)
    {
        $stmt = $this->con->prepare("DELETE FROM `waiters` WHERE `id` = ? AND `uid` = ?");
        $stmt->bind_param("ii", $id, $uid);
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
    function addProduct($category_id, $name)
    {
        // Check if product with same name already exists in this category
        if ($this->isProductExist($category_id, $name)) {
            return PRODUCT_EXIST;
        }
        
        $stmt = $this->con->prepare("INSERT INTO `products`(`category_id`, `name`) VALUES (?,?)");
        $stmt->bind_param("is", $category_id, $name);
        
        if ($stmt->execute()) {
            $stmt->close();
            return PRODUCT_CREATED;
        }
        $stmt->close();
        return PRODUCT_FAILED;
    }
    
    function getAllProducts()
    {
        $stmt = $this->con->prepare("SELECT p.*, c.category as category_name FROM `products` p 
                                  LEFT JOIN `categories` c ON p.category_id = c.id 
                                  ORDER BY p.name ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        $products = array();
        
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        
        $stmt->close();
        return $products;
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
    
    function updateProduct($id, $category_id, $name)
    {
        // Check if another product with the same name exists in this category
        if ($this->isAnotherProductExist($id, $category_id, $name)) {
            return PRODUCT_EXIST;
        }
        
        $stmt = $this->con->prepare("UPDATE `products` SET `category_id` = ?, `name` = ? WHERE `id` = ?");
        $stmt->bind_param("isi", $category_id, $name, $id);
        
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



    function getusergitwallet($uid)
    {
        $stmt = $this->con->prepare("SELECT amount FROM `giftwallet` WHERE username = ? ORDER BY gwid DESC LIMIT 1;");
        $stmt->bind_param("s", $uid);
        $stmt->execute();
        $stmt->bind_result($amount);
        $stmt->fetch();

        $walletData = (object) array(
            'amount' => $amount
        );

        return $walletData;
    }

    function getuserReciveGift($uid)
    {
        $stmt = $this->con->prepare("SELECT gift.price,gift.gift_id, gift.title, gift.image, users.name AS reciver_name, users.image AS reciver_image, giftorder.date AS send_date, giftorder.goid FROM `giftorder` JOIN gift ON gift.gift_id = giftorder.gift_id JOIN users ON users.username = giftorder.sender WHERE giftorder.reciver = ?;");
        $stmt->bind_param("s", $uid);
        $stmt->execute();
        $stmt->bind_result($price, $gift_id, $title, $image, $reciver_name, $reciver_image, $send_date, $goid);
        $userData = array();

        while ($stmt->fetch()) {
            $data = array(
                '   ' => $price,
                'gift_id' => $gift_id,
                'title' => $title,
                'image' => "https://thecodingverse.com/safarekaaba/" . $image,
                'reciver_name' => $reciver_name,
                'reciver_image' => "https://thecodingverse.com/safarekaaba/" . $reciver_image,
                'send_date' => $send_date,
                'goid' => $goid,
            );
            array_push($userData, $data);
        }

        return $userData;
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
