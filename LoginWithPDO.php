<?php
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
        $pdo = new PDO('oci:dbname=databasename', 'username', 'password'); //change for this server oracle
        $ip = getRealUserIp();
        $date_timezone = new DateTimeZone("America/Sao_Paulo");
        $date_time = new DateTime("now", $date_timezone);
        $date_now = $date_time->format("d/m/Y");
        $log_id = hash('ripemd256', $ip);
        $checklog = $pdo->prepare("SELECT COUNT(*) FROM log_access WHERE log_id=:log_id");
        $checklog->bindParam(':log_id', $log_id);
        $checklog->execute();
        $checklog_count = $checklog->fetchColumn();
        if($checklog_count >= 3){
            $checkdate = $pdo->prepare("SELECT log_date FROM log_access WHERE log_id=:log_id");
            $checkdate->bindParam(':log_id', $log_id);
            $checkdate->execute();
            $checkdate_info = $checkdate->fetchAll();
            $rownum = 0;
            foreach ($checkdate_info as $row) {
                ++$rownum;
                if ($date_now > $row['LOG_DATE']) {
                    //delete ip date expired
                    $pdo->prepare("DELETE FROM log_access WHERE log_id=? AND log_date=?")->execute([$log_id, $row['LOG_DATE']]);
                    return true;
                }
                if($rownum >= 3) {
                    return false;
                }
            }
        }else{
            $saveaccess = $pdo->prepare('INSERT INTO log_access (log_id, log_date) VALUES(:log_id, :log_date)');
            $saveaccess->execute(array(
                ':log_id' => $log_id,
                ':log_date' => $date_now
            ));
            return true;
        }
    }
    function getUser($email){
        $pdo = new PDO('oci:dbname=databasename', 'username', 'password'); //change for this server oracle
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
        $pdo = new PDO('oci:dbname=databasename', 'username', 'password'); //change for this server oracle
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
        //sendEmail($email, $token); activate this server in vercel
    }
    function checkToken($email) {
        $pdo = new PDO('oci:dbname=databasename', 'username', 'password'); //change for this server oracle
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
        $pdo = new PDO('oci:dbname=databasename', 'username', 'password'); //change for this server oracle
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