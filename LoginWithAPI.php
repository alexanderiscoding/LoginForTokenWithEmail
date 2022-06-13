<?php
    function sendPost($crud, $table_name, $column_id, $column_name, $column_value, $column_ip, $column_date, $column_email, $column_device, $column_token) {

        $host = "http://localhost:3000";
        //Change for host from CRUDWithTokenForFirebase in https://github.com/alexanderiscoding
        switch ($crud) {
            case 0:
                $url = "$host/api/create";
                $data = '{
                    "table": {
                        "name": "'.$table_name.'"
                    },
                    "column": {
                        "log_id": "'.$column_ip.'",
                        "log_date": "'.$column_date.'"
                    }
                }';
                break;
            case 1:
                $url = "$host/api/read";
                $data = '{
                    "table": {
                        "name": "'.$table_name.'"
                    },
                    "column": {
                        "id": "'.$column_id.'"
                    }
                }';
                break;
            case 2:
                $url = "$host/api/update"; // not utilized
                break;
            case 3:
                $url = "$host/api/delete";
                $data = '{
                    "table": {
                        "name": "'.$table_name.'"
                    },
                    "column": {
                        "id": "'.$column_id.'"
                    }
                }';
                break;
            case 4:
                $url = "$host/api/read";
                $data = '{
                    "table": {
                        "name": "'.$table_name.'"
                    },
                    "column": {
                        "where": "true",
                        "name": "'.$column_name.'",
                        "operator": "==",
                        "value": "'.$column_value.'"
                    }
                }';
                break;
            case 5:
                $url = "$host/api/create";
                $data = '{
                    "table": {
                        "name": "'.$table_name.'"
                    },
                    "column": {
                        "access_id": "'.$column_ip.'",
                        "access_date": "'.$column_date.'",
                        "connect_id": "'.$column_email.'",
                        "device_id": "'.$column_device.'",
                        "token": "'.$column_token.'"
                    }
                }';
                break;
            case 6:
                $url = "$host/api/delete";
                $data = '{
                    "table": {
                        "name": "'.$table_name.'"
                    },
                    "column": {
                        "where": "true",
                        "name": "'.$column_name.'",
                        "operator": "==",
                        "value": "'.$column_value.'"
                    }
                }';
                break;
        }
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
            "Accept: application/json",
            "Authorization: Bearer 7f59ba5f69d5b4e588e0ab0d4f8e1634",
            "Content-Type: application/json",
        );
        //Change after Bearer for same token from CRUDWithTokenForFirebase in https://github.com/alexanderiscoding
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        //for debug only!
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $resp = curl_exec($curl);
        curl_close($curl);
        $data = json_decode($resp);
        return $data;
    }
    //Use this https://github.com/alexanderiscoding/SendEmailWithToken for sends emails
    function sendEmail($email, $token) {
        $url = "https://examplename.vercel.app/mailjet";
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $data = '{
            from_email: "github@alexanderiscoding.com",
            from_name: "Alexanderiscoding",
            to_email: "'.$email.'",
            to_name: "Alexanderiscoding",
            subject_email: "🔐 Token de Acesso via E-mail",
            content_email:  "<a href=\"https://nameexample.com/LoginWithAPI.php?token='.$token.'\">Clique aqui para acessar sua conta</a>",
            token: "a8919de3b6f0f44a8799c8854deb3e43",
        }';
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_exec($curl);
        curl_close($curl);
    }
    function getRealUserIp(){
        switch(true){
            case (!empty($_SERVER['HTTP_X_REAL_IP'])) : return $_SERVER['HTTP_X_REAL_IP'];
            case (!empty($_SERVER['HTTP_CLIENT_IP'])) : return $_SERVER['HTTP_CLIENT_IP'];
            case (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) : return $_SERVER['HTTP_X_FORWARDED_FOR'];
            default : return $_SERVER['REMOTE_ADDR'];
        }
    }
    function saveAccess(){
        $ip = getRealUserIp();
        $date_timezone = new DateTimeZone("America/Sao_Paulo");
        $date_time = new DateTime("now", $date_timezone);
        $date_now = $date_time->format("d/m/Y");
        $log_id = hash('ripemd256', $ip);
        $checklog = sendPost(4, "log_access", null, "log_id", $log_id, null, null, null, null, null);
        $checklog_data = (array)$checklog;
        $checklog_count = count($checklog_data);
        if($checklog_count >= 3){
            $rownum = 0;
            foreach ($checklog_data as $checklog_data_key => $checklog_data_key_value) {
                ++$rownum;
                if ($date_now > $checklog_data_key_value->log_date) {
                    sendPost(3, "log_access", $checklog_data_key, null, null, null, null, null, null, null); //delete this log access for this log_id is more 24 hours
                    return true;
                }
                if($rownum >= 3) {
                    return false;
                }
            }
        }else{
            sendPost(0, "log_access", null, null, null, $log_id, $date_now, null, null, null); //insert new log access for this log_id
            return true;
        }
    }
    function getUser($email){
        $date_timezone = new DateTimeZone("America/Sao_Paulo");
        $date_time = new DateTime("now", $date_timezone);
        $date_now = $date_time->format("d/m/Y");
        $blocked_id = hash('ripemd256', $email);
        $checkuser = sendPost(4, "blocked", null, "blocked_id", $blocked_id, null, null, null, null, null);
        $checkuser_data = (array)$checkuser;
        $checkuser_count = count($checkuser_data);
        if($checkuser_count > 0){ //check blocked exist
            $checkuser_data_key = array_key_first($checkuser_data);
            if ($date_now > $checkuser_data[$checkuser_data_key]->blocked_date) {
                sendPost(3, "blocked", $checkuser_data_key, null, null, null, null, null, null, null); //delete blocked for this user
                return true;
            }else{
                return false;
            }
        }else{
            return true;
        }
    }
    function newToken($connect_id) {
        $date_timezone = new DateTimeZone("America/Sao_Paulo");
        $date_time = new DateTime("now", $date_timezone);
        $date_time->modify("+10 minutes");
        $access_date = $date_time->format("d/m/Y H:i");
        $ip = getRealUserIp();
        $access_id = hash('ripemd256', $ip);
        $device_id = hash('ripemd256', $_SERVER['HTTP_USER_AGENT']);
        $token = bin2hex(random_bytes(32));
        sendPost(5, "access_token", null, null, null, $access_id, $access_date, $connect_id, $device_id, $token); //insert new generate token for this user
        //sendEmail($email, $token); activate this server in vercel
    }
    function checkToken($email) {
        $date_timezone = new DateTimeZone("America/Sao_Paulo");
        $date_time = new DateTime("now", $date_timezone);
        $date_now = $date_time->format("d/m/Y H:i");
        $connect_id = hash('ripemd256', $email);
        $checktoken = sendPost(4, "access_token", null, "connect_id", $connect_id, null, null, null, null, null);
        $checktoken_data = (array)$checktoken;
        $checktoken_count = count($checktoken_data);
        if($checktoken_count > 0){ //check token from user exist
            $checktoken_data_key = array_key_first($checktoken_data);
            if ($date_now > $checktoken_data[$checktoken_data_key]->access_date) {
                sendPost(3, "access_token", $checktoken_data_key, null, null, null, null, null, null, null); //delete this token expired is 10min
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
        $date_timezone = new DateTimeZone("America/Sao_Paulo");
        $date_time = new DateTime("now", $date_timezone);
        $date_now = $date_time->format("d/m/Y H:i");
        $token = filter_var($_GET["token"], FILTER_SANITIZE_ENCODED);
        $checktoken = sendPost(4, "access_token", null, "token", $token, null, null, null, null, null);
        $checktoken_data = (array)$checktoken;
        $checktoken_count = count($checktoken_data);
        if($checktoken_count > 0){ //check token from user exist
            $checktoken_data_key = array_key_first($checktoken_data);
            if ($date_now < $checktoken_data[$checktoken_data_key]->access_date) {
                $ip = getRealUserIp();
                $user_access = hash('ripemd256', $ip);
                $user_device = hash('ripemd256', $_SERVER['HTTP_USER_AGENT']);
                $user_id = $checktoken_data[$checktoken_data_key]->connect_id;
                if($user_access === $checktoken_data[$checktoken_data_key]->access_id && $user_device === $checktoken_data[$checktoken_data_key]->device_id){
                    session_start();
                    $_SESSION["user"]=$user_id;
                    sendPost(3, "access_token", $checktoken_data_key, null, null, null, null, null, null, null); //delete token for this user
                    sendPost(6, "log_access", null, "log_id", $user_access, null, null, null, null, null); //delete all log access for this user
                    return true;
                }else{
                    sendPost(0, "blocked", null, null, null, $user_id, $date_now, null, null, null); //block account for this user
                    sendPost(3, "access_token", $checktoken_data_key, null, null, null, null, null, null, null); //delete token for this user
                    return false;
                }
            }else{
                sendPost(3, "access_token", $checktoken_data_key, null, null, null, null, null, null, null); //delete token for this user
                return false;
            }
        }else{
            return false;
        }
    }
    if ($_POST) {
        $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
        if (filter_var($email, FILTER_VALIDATE_EMAIL) && saveAccess()) {
            if(getUser($email)){
                if(checkToken($email)){
                    header("Location: /autorizado"); 
                    exit();
                }else{
                    header("Location: /reenviar");
                    exit();
                }
            }else{
                header("Location: /contabloqueada"); 
                exit();
            }
        } else {
            header("Location: /bloqueado"); 
            exit();
        }
    }else if ($_GET) {
        if (ctype_xdigit($_GET["token"]) && saveAccess() && validateToken()) {
            if(getUser($_SESSION["user"])){
                header("Location: /autorizado"); 
                exit();
            }else{
                unset($_SESSION["user"]);
                header("Location: /contabloqueado"); 
                exit();
            }
        } else {
            header("Location: /bloqueado"); 
            exit();
        }
    }else{
        session_start();
        if($_SESSION["user"]){
            header("Location: /autorizado"); 
            exit();
        }else{
            header("Location: /reenviar"); 
            exit();
        }
    }
?>
