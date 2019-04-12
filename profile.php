<?php 

session_start(); 

require_once('config/config.php');

// Check if user clicked logout button
if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['username']);
    header("location: index.php");
}

// Check if user logged in
if (!isset($_SESSION['username'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location: login.php');
} else {

    $username = $_SESSION['username'];
    // connect to the database
    $dsn = 'mysql:host='. $db_host.';dbname='. $db_name.';charset=utf8';

    try {
        $pdo = new PDO($dsn, $db_user, $db_pass, [
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]); 
    } catch (PDOException $e){ 
        $errors['msg'] = "Error connecting to database";
    }
    
    $sql = "SELECT * FROM users WHERE username = :username";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(); 
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <title>User Profile</title>
</head>
<body>
    <div class="content">
        <!-- notification message -->
        <?php if (isset($_SESSION['success'])) : ?>
        <div class="success" >
        <?php 
            echo $_SESSION['success']; 
            unset($_SESSION['success']);
        ?>
        </div>
        <?php endif ?>
        <!-- logged in user information -->
        <?php  if (isset($_SESSION['username'])) : ?>
        <h1><?php echo $user['username']; ?> Profile</h1>
        <div class="profile-info">
            <span class="email">Your email: <?php echo $user['email']; ?></span>
            <?php if ($user['photo']): ?>
            <img class="photo" src="<?php echo 'photos/'. $user['photo']; ?>" alt="Profile photo">
            <?php endif ?>
            <div class="block-links">
                <a href="index.php?logout='1'" >logout</a>
            </div>
        </div>
        <?php endif ?>
    </div>
</body>
</html>
