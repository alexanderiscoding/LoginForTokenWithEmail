<?php
    function getRealUserIp(){
        switch(true){
            case (!empty($_SERVER['HTTP_X_REAL_IP'])) : return $_SERVER['HTTP_X_REAL_IP'];
            case (!empty($_SERVER['HTTP_CLIENT_IP'])) : return $_SERVER['HTTP_CLIENT_IP'];
            case (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) : return $_SERVER['HTTP_X_FORWARDED_FOR'];
            default : return $_SERVER['REMOTE_ADDR'];
        }
    }
    function saveAccess(){
        require('connection.php'); //$pdo connection variable
        $ip = getRealUserIp();
        $log_id = hash('ripemd256', $ip);
        $checklogs = $pdo->prepare("CALL AUTHWITHTOKEN.DELETE_OLDERS_ACCESS()");
        $checklogs->execute();// execute procedure delete olders logs access
        $checkuser = $pdo->prepare("SELECT AUTHWITHTOKEN.GET_ACCESS_USER(:log_id) AS AUTORIZATION FROM dual");
        $checkuser->bindParam(':log_id', $log_id);
        $checkuser->execute();// execute function logs is more 3 access return 0 is not ok, 1 is ok.
        $checkuser_info = $checkuser->fetch(PDO::FETCH_OBJ);
        if($checkuser_info->AUTORIZATION == 1){
            $insertlog = $pdo->prepare("CALL AUTHWITHTOKEN.INSERT_ACCESS_USER(:log_id)");
            $insertlog->bindParam(':log_id', $log_id);
            $insertlog->execute();// execute procedure insert log new access
            return true;
        }else{
            return false;
        }
    }
    function getUser($email){
        require('connection.php'); //$pdo connection variable
        $date_timezone = new DateTimeZone("America/Sao_Paulo");
        $date_time = new DateTime("now", $date_timezone);
        $date_now = $date_time->format("d/m/Y");
        $blocked_id = hash('ripemd256', $email);
        $checkuser = $pdo->prepare("SELECT blocked_date FROM blocked WHERE blocked_id=:blocked_id");
        $checkuser->bindParam(':blocked_id', $blocked_id);
        $checkuser->execute();
        $checkuser_info = $checkuser->fetch(PDO::FETCH_OBJ);
        if($checkuser_info){ //check blocked exist
            if ($date_now > $checkuser_info->BLOCKED_DATE) {
                //delete blocked for user
                $pdo->prepare("DELETE FROM blocked WHERE blocked_id=?")->execute([$blocked_id]);
                return true;
            }else{
                return false;
            }
        }else{
            return true;
        }
    }
    function newToken($connect_id) {
        require('connection.php'); //$pdo connection variable
        $date_timezone = new DateTimeZone("America/Sao_Paulo");
        $date_time = new DateTime("now", $date_timezone);
        $date_time->modify("+10 minutes");
        $access_date = $date_time->format("d/m/Y H:i");
        $ip = getRealUserIp();
        $access_id = hash('ripemd256', $ip);
        $device_id = hash('ripemd256', $_SERVER['HTTP_USER_AGENT']);
        $token = bin2hex(random_bytes(32));
        $savetoken = $pdo->prepare('INSERT INTO access_token (access_id, access_date, connect_id, device_id, token) VALUES(:access_id, :access_date, :connect_id, :device_id, :token)');
        $savetoken->execute(array(
            ':access_id' => $access_id,
            ':access_date' => $access_date,
            ':connect_id' => $connect_id,
            ':device_id' => $device_id,
            ':token' => $token
        ));
        //function send email
        //require('send_email.php'); 
        //sendEmail($email, $token);
    }
    function checkToken($email) {
        require('connection.php'); //$pdo connection variable
        $date_timezone = new DateTimeZone("America/Sao_Paulo");
        $date_time = new DateTime("now", $date_timezone);
        $date_now = $date_time->format("d/m/Y H:i");
        $connect_id = hash('ripemd256', $email);
        $checktoken = $pdo->prepare("SELECT access_date FROM access_token WHERE connect_id=:connect_id");
        $checktoken->bindParam(':connect_id', $connect_id);
        $checktoken->execute();
        $checktoken_info = $checktoken->fetch(PDO::FETCH_OBJ);
        if($checktoken_info){ //check token exist
            if ($date_now > $checktoken_info->ACCESS_DATE) {
                //delete token expired
                $pdo->prepare("DELETE FROM access_token WHERE connect_id=?")->execute([$connect_id]);
                newToken($connect_id);
                return true;
            }else{
                return false;
            }
        }else{
            newToken($connect_id);
            return true;
        }
    }
    function validateToken(){
        require('connection.php'); //$pdo connection variable
        $date_timezone = new DateTimeZone("America/Sao_Paulo");
        $date_time = new DateTime("now", $date_timezone);
        $date_now = $date_time->format("d/m/Y H:i");
        $token = filter_var($_GET["token"], FILTER_SANITIZE_ENCODED);
        $checkuser = $pdo->prepare("SELECT access_id, access_date, connect_id, device_id FROM access_token WHERE token=:token");
        $checkuser->bindParam(':token', $token);
        $checkuser->execute();
        $checkuser_info = $checkuser->fetch(PDO::FETCH_OBJ);
        if($checkuser_info){ //check token exist
            if ($date_now < $checkuser_info->ACCESS_DATE) {
                $ip = getRealUserIp();
                $user_access = hash('ripemd256', $ip);
                $user_device = hash('ripemd256', $_SERVER['HTTP_USER_AGENT']);
                if($user_access === $checkuser_info->ACCESS_ID && $user_device === $checkuser_info->DEVICE_ID){
                    session_start();
                    $_SESSION["user"]=$checkuser_info->CONNECT_ID;
                    $pdo->prepare("DELETE FROM access_token WHERE token=?")->execute([$token]);
                    $pdo->prepare("DELETE FROM log_access WHERE log_id=?")->execute([$user_access]);
                    return true;
                }else{
                    $blockaccount = $pdo->prepare('INSERT INTO blocked (blocked_id, blocked_date) VALUES(:blocked_id, :blocked_date)');
                    $blockaccount->execute(array(
                        ':blocked_id' => $checkuser_info->CONNECT_ID,
                        ':blocked_date' => $date_now
                    ));
                    $pdo->prepare("DELETE FROM access_token WHERE token=?")->execute([$token]);
                    return false;
                }
            }else{
                $pdo->prepare("DELETE FROM access_token WHERE token=?")->execute([$token]);
                return false;
            }
        }else{
          return false;
        }
    }
    if ($_POST) {
        $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
        if (filter_var($email, FILTER_VALIDATE_EMAIL) && saveAccess() && getUser($email) && checkToken($email)) {
            echo "autorized new token";
        } else {
            echo "blocked access";
        }
    }
    if ($_GET) {
        if (ctype_xdigit($_GET["token"]) && saveAccess() && validateToken()) {
            if(getUser($_SESSION["user"])){
                echo "autorized access";
            }else{
                unset($_SESSION["user"]);
                echo "user blocked";
            }
        } else {
            echo "blocked access";
        }
    }
?>