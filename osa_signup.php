<!DOCTYPE html>
<html>
<head>
    <title>Sign Up</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
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
        color: #333;
        margin-bottom: 10px;
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
        background-color: maroon;
    }
</style>
</head>
<body>
    <div class="form-container">
        <img src="/PH_DN_VP_Dashboard/greetings/umdc-logo.png" alt="Logo" class="logo">
        <h2>Sign Up</h2>
        <form action="osa_signup_process.php" method="POST">

            <label for="id_no">ID No.:</label>
            <input type="text" name="id_no" id="id_no" required>
            
            <label for="name">Name:</label>
            <input type="text" name="name" id="name" required>
            
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>
            
            <input type="submit" value="Sign Up">
        </form>
    </div>
</body>
</html>
