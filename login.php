<?php 

session_start();

require_once('config/config.php');

$errors = [];

if (isset($_POST['submit'])) {
    
    $username = htmlspecialchars($_POST['username'], ENT_QUOTES);
    $password = htmlspecialchars($_POST['password'], ENT_QUOTES);
    
    // POST data validation
    if (empty($username) || !is_string($username)) {
        $errors['username'] = "Provide username";
    }
    if (empty($password) || !is_string($password)) {
        $errors['password'] = "Provide password";
    }
    
    if (count($errors) == 0) {
        
        // connect to the database via PDO
        $dsn = 'mysql:host='. $db_host.';dbname='. $db_name.';charset=utf8';
        
        try {
            $pdo = new PDO($dsn, $db_user, $db_pass, [
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]); 
        } catch(PDOException $e) { 
            $errors['msg'] = "Error connecting to database";
        }
        
        //encrypt the password before saving in the database
        $password = md5($password);
        
        $sql = "SELECT * FROM users WHERE username = :username AND password = :password";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['username' => $username, 'password' => $password]);
        $user = $stmt->fetch(); 
        
        // Redirect to profile if user logged in
        if ($user) {
            $_SESSION['username'] = $username;
            $_SESSION['success'] = "You are now logged in";
            header('location: profile.php');
        } else {
            $errors['password'] = "Wrong username/password combination";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Login</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<body>
    <div class="content">
        <h1>Login</h1>
        <form method="post" action="login.php" id="form-login">
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" value="<?php echo $username; ?>">
                <div class="error"><?php echo isset($errors['username']) ? $errors['username'] : ''; ?></div>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password">
                <div class="error"><?php echo isset($errors['password']) ? $errors['password'] : ''; ?></div>
            </div>
            <div class="input-group">
                <button type="submit" class="btn" name="submit" id="submit-login">Login</button>
                <div class="error"><?php echo isset($errors['msg']) ? $errors['msg'] : ''; ?></div>
            </div>
            <span class="message">Not yet a member? <a href="register.php">Sign up</a></span>
        </form>
    </div>
    <script>
        const registerForm = document.getElementById('form-login');
        const fieldUsername = document.getElementById('username');
        const fieldPassword = document.getElementById('password');
        const loginSubmit = document.getElementById('submit-login');
        
        fieldUsername.addEventListener('blur', function (event) {
            const error = fieldUsername.parentNode.querySelector('.error');
            if (fieldUsername.value == "") {
                error.innerHTML = "Provide username";
                loginSubmit.disabled = true;
            } else if (fieldUsername.value.length < 3 || fieldUsername.value.length > 50) {
                error.innerHTML = "Username must be atleast 3 characters!";
                loginSubmit.disabled = true;
            } else {
                loginSubmit.disabled = false;
                error.innerHTML = "";
            }
        });

        fieldPassword.addEventListener('blur', function (event) {
            const error = fieldPassword.parentNode.querySelector('.error');
            if (fieldPassword.value == "") {
                error.innerHTML = "Provide password";
                loginSubmit.disabled = true;
            } else if (fieldPassword.value.length < 6) {
                error.innerHTML = "Password must be atleast 6 characters!";
                loginSubmit.disabled = true;
            } else {
                loginSubmit.disabled = false;
                error.innerHTML = "";
            }
        });

        loginSubmit.addEventListener('click', function (event) {
            registerForm.submit();
        });
    </script>
</body>
</html>
