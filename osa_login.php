<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
</head>
    <style>
    body {
        margin: 0;
        padding: 0;
        font-family: 'Montserrat', sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        background-color: #f4f6f9;
    }
    .logo{
        width: 240px;
        height: 50px;
    }
    .form-container {
        background-color: #ffffff;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        padding: 40px;
        width: 100%;
        max-width: 400px;
        text-align: center;
    }
    .form-container h2 {
        top: 0%;
        color: #333;
        margin-bottom: 15px;
    }
    .form-container label {
        display: block;
        font-weight: bold;
        color: #555;
        text-align: left;
        margin-bottom: 5px;
    }
    .form-container input[type="text"],
    .form-container input[type="password"],
    .form-container select {
        width: 100%;
        padding: 10px;
        margin-bottom: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
        box-sizing: border-box;
    }
    .form-container select {
        appearance: none;
        background: #f9f9f9;
        cursor: pointer;
    }
    .form-container input[type="submit"] {
        margin-top: 10px;
        width: 100%;
        padding: 12px;
        background-color: #B33838;
        color: white;
        font-weight: bold;
        font-size: 16px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }
    .form-container input[type="submit"]:hover {
        background-color: rgb(237, 194, 5);
    }
    .signup-link {
        margin-top: 15px;
        font-size: 14px;
        color: #555;
    }
    .signup-link a {
        color: #B33838;
        text-decoration: none;
        font-weight: bold;
    }
    .signup-link a:hover {
        text-decoration: underline;
    }
    </style>
<body>
    <div class="form-container">
        <img src="/PH_DN_VP_Dashboard/greetings/umdc-logo.png" alt="Logo" class="logo">
        <h2>OSA Login</h2>
        <form action="osa_login_process.php" method="POST">
            ID No.: <input type="text" name="id_no" required><br>
            Password: <input type="password" name="password" required><br>
        <input type="submit" value="Login">
    </form>

    <div class="signup-link">
            Don't have an account? <a href="osa_signup.php">Sign up here</a>
        </div>
    </div>
    
    
</body>
</html>
