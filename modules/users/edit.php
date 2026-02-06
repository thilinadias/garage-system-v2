<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/functions.php';


checkRole(['admin']);

if(!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $id]);
$user = $stmt->fetch();

if(!$user) {
    die("User not found.");
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = $_POST['password'];

    if(empty($name) || empty($email)) {
        $error = "Name and Email are required.";
    } else {
        $avatar = $user['avatar'];
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png'];
            $filename = $_FILES['avatar']['name'];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if (in_array(strtolower($ext), $allowed)) {
                $new_filename = uniqid('profile_') . '.' . $ext;
                $upload_path = '../../assets/uploads/profiles/' . $new_filename;
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                    // Delete old avatar if exists
                    if ($user['avatar'] && file_exists('../../assets/uploads/profiles/' . $user['avatar'])) {
                        unlink('../../assets/uploads/profiles/' . $user['avatar']);
                    }
                    $avatar = $new_filename;
                }
            }
        }

        if(!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET name = :name, email = :email, role = :role, password = :password, avatar = :avatar WHERE id = :id";
            $params = ['name' => $name, 'email' => $email, 'role' => $role, 'password' => $hashed_password, 'avatar' => $avatar, 'id' => $id];
        } else {
            $sql = "UPDATE users SET name = :name, email = :email, role = :role, avatar = :avatar WHERE id = :id";
            $params = ['name' => $name, 'email' => $email, 'role' => $role, 'avatar' => $avatar, 'id' => $id];
        }

        $stmt = $pdo->prepare($sql);
        if($stmt->execute($params)) {
             // Update session if editing self
             if ($id == $_SESSION['user_id']) {
                 $_SESSION['avatar'] = $avatar;
                 $_SESSION['name'] = $name;
             }
             logAction($pdo, $_SESSION['user_id'], 'Updated User Profile', 'users', $id, "User: $name");
             header("Location: index.php?msg=updated");
             exit;
        }
 else {
            $error = "Error updating user.";
        }
    }
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>


<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4>Edit User</h4>
            </div>
            <div class="card-body">
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                     <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="technician" <?php echo $user['role'] == 'technician' ? 'selected' : ''; ?>>Technician</option>
                            <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <small class="text-muted">(Leave blank to keep current)</small></label>
                        <input type="password" name="password" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Profile Photo</label>
                        <?php if($user['avatar']): ?>
                            <div class="mb-2">
                                <img src="../../assets/uploads/profiles/<?php echo $user['avatar']; ?>" class="rounded-circle shadow-sm" style="width: 60px; height: 60px; object-fit: cover;">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="avatar" class="form-control" accept="image/*">
                        <small class="text-muted">Allowed: JPG, PNG. Leave blank to keep current.</small>
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
