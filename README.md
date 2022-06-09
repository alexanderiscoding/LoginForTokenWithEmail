## 🚀 Usage

Iniciar servidor PHP Localmente

```cmd
php -S localhost:3000
```
Exemplo de função para enviar e-mail

```php
<?php
  function sendEmail($email, $token) {
      $url = "https://api.sendgrid.com/v3/mail/send";

      $curl = curl_init($url);
      curl_setopt($curl, CURLOPT_URL, $url);
      curl_setopt($curl, CURLOPT_POST, true);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

      $headers = array(
      "Accept: application/json",
      "Authorization: Bearer token-api",
      "Content-Type: application/json",
      );
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

      $data = '{
          "personalizations": [
              {
                  "to": [
                      {
                          "email": "'.$email.'"
                      }
                  ]
              }
          ],
          "from": {
              "email": "email registered in sendgrid",
              "name": "name from preference"
          },
          "subject": "🔐 Token de Acesso via E-mail",
          "content": [
              {
                  "type": "text/html",
                  "value": "<a href=\"https://nameexample.com/AuthWithTokenForEmail.php?token='.$token.'\">Clique aqui para acessar sua conta</a>"
              }
          ]
      }';

      curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
      curl_exec($curl);
      curl_close($curl);
  }
?>
```

Exemplo de vereficação de sessão

```php
<?php
    session_start();
    if ($_SESSION["user"]) {
      echo "user logged";
    } else {
      echo "user not logged";
    }
?>
```

Exemplo para verificar conexão com database

```php
<?php
  try{
      //$pdo = new PDO('oci:dbname=databasename', 'username', 'password'); oracle database
      //$pdo = new PDO('mysql:host=hostdatabase;dbname=databasename', 'username', 'password'); mysql database
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }catch ( PDOException $e ){
      echo 'Connection Datebase Error: ' . $e->getMessage();
  }
?>
```

Exemplo da tabela SQL access_token

```sql
CREATE TABLE `ACCESS_TOKEN` (
  `ACCESS_ID` varchar(64) NOT NULL,
  `ACCESS_DATE` date NOT NULL,
  `CONNECT_ID` varchar(64) NOT NULL,
  `DEVICE_ID` varchar(64) NOT NULL,
  `TOKEN` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
```

Exemplo da tabela SQL blocked

```sql
CREATE TABLE `BLOCKED` (
  `BLOCKED_ID` varchar(64) NOT NULL,
  `BLOCKED_DATE` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
```

Exemplo da tabela SQL log_access

```sql
CREATE TABLE `LOG_ACCESS` (
  `LOG_ID` varchar(64) NOT NULL,
  `LOG_DATE` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
```
