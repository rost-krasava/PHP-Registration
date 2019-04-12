<?php 

session_start();

require_once('config/config.php');

$errors = [];

if ($_POST && isset($_POST['submit'])) { 
    
    $username = htmlspecialchars($_POST['username'],ENT_QUOTES);
    $email = htmlspecialchars($_POST['email'],ENT_QUOTES);
    $password = htmlspecialchars($_POST['password'],ENT_QUOTES);
    $confirmation = htmlspecialchars($_POST['confirmation'],ENT_QUOTES);
    $photo = null;
    $is_uploaded = 0;
    
    // POST data validation
    if (empty($username) || !is_string($username)) { 
        $errors['username'] = "Provide username";
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors['username'] = "Username must be atleast 3 characters!";  
    }
    if (empty($email) || !is_string($email)) {
        $errors['email'] = "Provide email";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address!';
    } 
    if (empty($password) || !is_string($password)) { 
        $errors['password'] = "Provide password"; 
    } elseif (strlen($password) < 6) {
        $errors['password'] = "Password must be atleast 6 characters!";  
    } elseif ($password != $confirmation) {
        $errors['password'] = "Passwords must match";
    }
    
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
    
    $sql = "SELECT * FROM users WHERE username = :username OR email = :email LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['username' => $username, 'email' => $email]);
    $user = $stmt->fetch(); 
    
    // Check if user exists in the database
    if ($user) { 
        if ($user['username'] === $username) {
            $errors['username'] = "Username already exists";
        }
        if ($user['email'] === $email) {
            $errors['email'] = "Email already exists";
        }
    }
    
    // Check if file is uploaded
    if (is_uploaded_file($_FILES['photo']['tmp_name'])) {
        
        $target_dir = 'photos/' . $username . '/';
        $file_extension = pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
        $target_file = $target_dir . 'profile.' . $file_extension;
        $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Check if image file is a actual image or fake image
        if (getimagesize($_FILES["photo"]["tmp_name"]) !== false) {
            $is_uploaded = 1;
        } else {
            $errors['photo'] = "File is not an image.";
            $is_uploaded = 0;
        }
        // Check if file already exists
        if (file_exists($target_file)) {
            $errors['photo'] = "Sorry, file already exists.";
            $is_uploaded = 0;
        }
        // Check file size
        if ($_FILES["photo"]["size"] > 500000) {
            $errors['photo'] = "Sorry, your file is too large.";
            $is_uploaded = 0;
        }
        // Allow certain file formats
        if ($image_file_type != "jpg" && $image_file_type != "png"	&& $image_file_type != "gif" ) {
            $errors['photo'] = "Sorry, only JPG, PNG & GIF files are allowed.";
            $is_uploaded = 0;
        }		
    }
    
    // Register user if there are no errors in the form
    if (count($errors) == 0) {
        // Upload photo if there are no validation errors
        if ($is_uploaded === 1) {
            // Creating user directory
            if (!file_exists($target_dir)) {
                try {
                    mkdir($target_dir);
                } catch (Exception $ex) {
                    $errors['photo'] = "Sorry, your file was not uploaded.";
                }
            }
            // Upload image and creating image link path
            if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
                $photo = $username . '/profile.' . $file_extension;
            } else {
                $errors['photo'] = "Sorry, there was an error uploading your file.";
            }
        } else {
            $errors['photo'] = "Sorry, your file was not uploaded.";
        }
        
        //encrypt the password before saving in the database
        $password = md5($password);
        
        $sql = 'INSERT INTO users(username, email, photo, password) VALUES(:username, :email, :photo, :password)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['username' => $username, 'email' => $email, 'photo' => $photo, 'password' => $password]);
        
        $_SESSION['username'] = $username;
        $_SESSION['success'] = "You are now logged in";
        header('location: profile.php');
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Register</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<body>
    <div class="content">
        <h1>Register</h1>
        <form id="form-register" method="post" action="register.php" enctype="multipart/form-data">
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" value="<?php echo $username; ?>" title="Provide username longer than 3 characters" required minlength="3" maxlength="50">
                <div class="error"><?php echo isset($errors['username']) ? $errors['username'] : ''; ?></div>
            </div>
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" value="<?php echo $email; ?>" required title="Your email address must look like this: example@gmail.com ">
                <div class="error"><?php echo isset($errors['email']) ? $errors['email'] : ''; ?></div>
            </div>
            <div class="input-group">
                <label for="photo">Profile Photo</label>
                <div class="file-container">
                    Upload File
                    <input type="file" name="photo" id="photo">
                </div>
                <div class="error"><?php echo isset($errors['photo']) ? $errors['photo'] : ''; ?></div>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required minlength="6">
                <div class="error"><?php echo isset($errors['password']) ? $errors['password'] : ''; ?></div>
            </div>
            <div class="input-group">
                <label for="confirmation">Confirm password</label>
                <input type="password" name="confirmation" id="confirmation" required>
            </div>
            <div class="input-group">
                <button type="submit" class="btn" name="submit" id="submit-register" disabled>Register</button>
                <div class="error"><?php echo isset($errors['msg']) ? $errors['msg'] : ''; ?></div>
            </div>
            <span class="message">Already a member? <a href="login.php">Sign in</a></span>
        </form>
    </div>
    <script>
        const registerForm = document.getElementById('form-register');
        const fieldEmail = document.getElementById('email');
        const fieldUsername = document.getElementById('username');
        const fieldPassword = document.getElementById('password');
        const fieldConfirmation = document.getElementById('confirmation');
        const fieldPhoto = document.getElementById('photo');
        const registerSubmit = document.getElementById('submit-register');
        
        fieldEmail.addEventListener('blur', function (event) {
            const error = fieldEmail.parentNode.querySelector('.error');
            const regex = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            if (fieldEmail.value == "") {
                error.innerHTML = "Provide email";
                registerSubmit.disabled = true;
            } else if (!regex.test(fieldEmail.value)) {
                error.innerHTML = "Please enter a valid email address!";
                registerSubmit.disabled = true;
            } else {
                registerSubmit.disabled = false;
                error.innerHTML = "";
            }
        });

        fieldUsername.addEventListener('blur', function (event) {
            const error = fieldUsername.parentNode.querySelector('.error');
            if (fieldUsername.value == "") {
                error.innerHTML = "Provide username";
                registerSubmit.disabled = true;
            } else if (fieldUsername.value.length < 3 || fieldUsername.value.length > 50) {
                error.innerHTML = "Username must be atleast 3 characters!";
                registerSubmit.disabled = true;
            } else {
                registerSubmit.disabled = false;
                error.innerHTML = "";
            }
        });

        fieldPassword.addEventListener('blur', function (event) {
            const error = fieldPassword.parentNode.querySelector('.error');
            if (fieldPassword.value == "") {
                error.innerHTML = "Provide password";
                registerSubmit.disabled = true;
            } else if (fieldPassword.value.length < 6) {
                error.innerHTML = "Password must be atleast 6 characters!";
                registerSubmit.disabled = true;
            } else {
                registerSubmit.disabled = false;
                error.innerHTML = "";
            }
        });

        fieldConfirmation.addEventListener('blur', function (event) {
            const error = fieldPassword.parentNode.querySelector('.error');
            if (fieldConfirmation.value !== fieldPassword.value) {
                error.innerHTML = "Passwords must match";
                registerSubmit.disabled = true;
            } else {
                registerSubmit.disabled = false;
                error.innerHTML = "";
            }
        });

        fieldPhoto.addEventListener('change', function (event) {
            const error = fieldPhoto.parentNode.parentNode.querySelector('.error');
            if (validate_fileupload(fieldPhoto.value)) {
                error.innerHTML = "";
                registerSubmit.disabled = false;
            } else {
                registerSubmit.disabled = true;
                error.innerHTML = "Sorry, only JPG, PNG & GIF files are allowed.";
            }
        });
        
        registerSubmit.addEventListener('click', function (event) {
            registerForm.submit();
        });

        function validate_fileupload(fileName)
        {
            const allowed_extensions = new Array("jpg","png","gif");
            const file_extension = fileName.split('.').pop().toLowerCase(); // split function will split the filename by dot(.), and pop function will pop the last element from the array which will give you the extension as well. If there will be no extension then it will return the filename.
            for (i = 0; i <= allowed_extensions.length; i++) {
                if (allowed_extensions[i]==file_extension) {
                    return true; // valid file extension
                }
            }
            return false;
        }
    </script>
</body>
</html>
