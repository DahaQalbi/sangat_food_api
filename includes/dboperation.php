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
    function Login($email, $password)
    {
        $stmt = $this->con->prepare("SELECT `id`, `name`, `nickname`, `username` as uname, `image`, `c_id`, `role`, `address`, `sponsor`, `password`, `email`, `pin`, `created_at` FROM `users` WHERE email = ?  AND password = ?");
        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();
        $stmt->bind_result($id, $name, $nickname, $username, $image, $c_id, $role, $address, $sponsor, $password, $email, $pin, $created_at);

        $userData = array();
        while ($stmt->fetch()) {
            $data = array(
                'id' => $id,
                'name' => $name,
                'nickname' => $nickname,
                'username' => $username,
                'image' => "https://thecodingverse.com/safarekaaba/" .  $image,
                'c_id' => $c_id,
                'role' => $role,
                'address' => $address,
                'sponsor' => $sponsor,
                'password' => $password,
                'email' => $email,
                'pin' => $pin,
                'created_at' => $created_at,
            );
            array_push($userData, $data);
        }
        return $userData;
    }

    function AdminLogin($email, $password, $role)
    {
        $stmt = $this->con->prepare("SELECT `id`, `name`, `nickname`, `username` as uname, `image`, `c_id`, `role`, `address`, `sponsor`, `password`, `email`, `pin`, `created_at` FROM `users` WHERE email = ?  AND password = ? AND (role = ? OR role = 'admin')");
        $stmt->bind_param("sss", $email, $password, $role);
        $stmt->execute();
        $stmt->bind_result($id, $name, $nickname, $username, $image, $c_id, $role, $address, $sponsor, $password, $email, $pin, $created_at);

        $userData = array();
        while ($stmt->fetch()) {
            $data = array(
                'id' => $id,
                'name' => $name,
                'nickname' => $nickname,
                'username' => $username,
                'image' => "https://thecodingverse.com/safarekaaba/" .  $image,
                'c_id' => $c_id,
                'role' => $role,
                'address' => $address,
                'sponsor' => $sponsor,
                'password' => $password,
                'email' => $email,
                'pin' => $pin,
                'created_at' => $created_at
            );
            array_push($userData, $data);
        }
        return $userData;
    }

       function adminwallet($uid)
    {
        $stmt = $this->con->prepare("SELECT `awid`, `link1`, `link2`, `link3`, `link4`, `link5`, `l1name`, `l2name`, `l3name`, `l4name`, `l5name` FROM `adminwallet` WHERE `awid` = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $stmt->bind_result($awid, $link1, $link2, $link3, $link4, $link5, $l1name, $l2name, $l3name, $l4name, $l5name);
        $userData = array();
        while ($stmt->fetch()) {
            $data = array(
                'awid' => $awid,
                'link1' => $link1,
                'link2' => $link2,
                'link3' => $link3,
                'link4' => $link4,
                'link5' => $link5,
                'l1name' => $l1name,
                'l2name' => $l2name,
                'l3name' => $l3name,
                'l4name' => $l4name,
                'l5name' => $l5name,
            );
            array_push($userData, $data);
        }
        return $userData[0];
    }
}
