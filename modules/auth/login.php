<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

// Fetch Company Profile
$stmt = $pdo->query("SELECT name, logo FROM company_profile LIMIT 1");
$company = $stmt->fetch();

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $stmt = $pdo->prepare("SELECT id, name, password, role, avatar FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $user['role'];
                $_SESSION['avatar'] = $user['avatar'];
                
                logAction($pdo, $user['id'], 'User Login', 'users', $user['id'], 'Logged in successfully.');
                
                header("Location: ../dashboard/index.php");
                exit;
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "No account found with that email.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($company['name'] ?? 'Garage Sys'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            background: white;
        }
        .brand-logo {
            text-align: center;
            margin-bottom: 25px;
        }
        .brand-logo img {
            max-width: 120px;
            height: auto;
            margin-bottom: 10px;
        }
        .brand-name {
            font-size: 1.5rem;
            color: #333;
            font-weight: 700;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="brand-logo">
        <?php if(!empty($company['logo']) && file_exists('../../' . $company['logo'])): ?>
            <img src="../../<?php echo htmlspecialchars($company['logo']); ?>" alt="Company Logo">
        <?php endif; ?>
        <div class="brand-name"><?php echo htmlspecialchars($company['name'] ?? 'Garage Sys'); ?></div>
    </div>
    
    <h4 class="text-center mb-4">Sign In</h4>
    
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form action="" method="post">
        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
                <input type="email" name="email" class="form-control" id="email" required>
            </div>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                <input type="password" name="password" class="form-control" id="password" required>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="rememberMe">
                <label class="form-check-label" for="rememberMe">Remember me</label>
            </div>
            <a href="../../reset_password.php" class="text-decoration-none small">Forgot Password?</a>
        </div>
        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg">Login</button>
        </div>
    </form>
</div>

</body>
</html>
