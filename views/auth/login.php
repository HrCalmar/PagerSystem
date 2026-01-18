<?php
// views/auth/login.php
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Pager System</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <div class="login-container">
        <h1>Pager System</h1>
        <?php if (isset($_GET['error'])): ?>
            <div class="error">Forkert brugernavn eller adgangskode</div>
        <?php endif; ?>
        <form method="POST" action="/login">
            <?= \App\Core\CSRF::field() ?>
            <div>
                <label>Brugernavn</label>
                <input type="text" name="username" required autofocus>
            </div>
            <div>
                <label>Adgangskode</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Log ind</button>
        </form>
    </div>
</body>
</html>