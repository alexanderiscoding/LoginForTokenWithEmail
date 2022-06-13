## 🚀 Usage

Initialize server local

```cmd
php -S localhost:3000
```

Example check user logged

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

Example check connection database

```php
<?php
  try{
      $pdo = new PDO('oci:dbname=databasename', 'username', 'password');
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }catch ( PDOException $e ){
      echo 'Connection Datebase Error: ' . $e->getMessage();
  }
?>
```

Table SQL for access_token

```sql
CREATE TABLE ACCESS_TOKEN (	
  ACCESS_ID VARCHAR2(64), 
  ACCESS_DATE DATE, 
  CONNECT_ID VARCHAR2(64), 
  DEVICE_ID VARCHAR2(64), 
  TOKEN VARCHAR2(64)
);
```

Table SQL for blocked

```sql
CREATE TABLE BLOCKED (	
  BLOCKED_ID VARCHAR2(64), 
  BLOCKED_DATE DATE
);
```

Table SQL for log_access

```sql
CREATE TABLE LOG_ACCESS (	
  LOG_ID VARCHAR2(64), 
  LOG_DATE DATE
);
```
