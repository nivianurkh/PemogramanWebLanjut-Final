<?php
session_start();
require_once("koneksi.php");

$error_top = '';       // Untuk error umum di atas form
$error_checkbox = '';  // Untuk error khusus checkbox
$errors = [];          // Array untuk error per field (opsional)

$firstname = '';
$lastname = '';
$email = '';
$no_hp = '';
$username = '';
$password = '';
$terms_checked = false;

if (isset($_POST['register_pelanggan'])) {
    // Ambil dan simpan nilai input
    $firstname = trim(filter_input(INPUT_POST, 'firstname', FILTER_SANITIZE_STRING));
    $lastname = trim(filter_input(INPUT_POST, 'lastname', FILTER_SANITIZE_STRING));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));
    $no_hp = trim(filter_input(INPUT_POST, 'no_hp', FILTER_SANITIZE_STRING));
    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));
    $password = $_POST["password"];
    $terms_checked = isset($_POST['terms']);

    // Validasi setiap field, simpan error di array $errors dengan key nama field
    if (!$firstname) {
        $errors['firstname'] = 'First name is required.';
    }

    if (!$lastname) {
        $errors['lastname'] = 'Last name is required.';
    }

    if (!$email) {
        $errors['email'] = 'Email is not valid.';
    }

    if (!preg_match('/^[0-9]{1,13}$/', $no_hp)) {
        $errors['no_hp'] = 'Phone number should only contain digits and be up to 13 digits long.';
    }

    if (!$username) {
        $errors['username'] = 'Username is required.';
    }

    if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', $password)) {
        $errors['password'] = 'Password must be at least 8 characters long and contain both letters and numbers.';
    }

    if (!$terms_checked) {
        $error_checkbox = 'You must agree to the terms and conditions to continue.';
    }

    // Jika belum ada error validasi input, cek duplikat di database
    if (empty($errors) && !$error_checkbox) {
        // Cek email
        $stmt = $db->prepare("SELECT COUNT(*) FROM pelanggan WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetchColumn() > 0) {
            $errors['email'] = 'Email is already in use. Please use another email address.';
        }

        // Cek no_hp
        if (empty($errors)) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM pelanggan WHERE no_hp = :no_hp");
            $stmt->execute([':no_hp' => $no_hp]);
            if ($stmt->fetchColumn() > 0) {
                $errors['no_hp'] = 'Phone number is already in use. Please use another phone number.';
            }
        }

        // Cek username
        if (empty($errors)) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM pelanggan WHERE username = :username");
            $stmt->execute([':username' => $username]);
            if ($stmt->fetchColumn() > 0) {
                $errors['username'] = 'Username is already taken. Please choose another username.';
            }
        }
    }

    // Jika tidak ada error, simpan ke DB
    if (empty($errors) && !$error_checkbox) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO pelanggan (firstname, lastname, email, no_hp, username, password) 
                VALUES (:firstname, :lastname, :email, :no_hp, :username, :password)";
        $stmt = $db->prepare($sql);
        $params = [
            ":firstname" => $firstname,
            ":lastname" => $lastname,
            ":email" => $email,
            ":no_hp" => $no_hp,
            ":username" => $username,
            ":password" => $hashed_password
        ];

        try {
            $saved = $stmt->execute($params);
            if ($saved) {
                $_SESSION['username'] = $username;
                $_SESSION['firstname'] = $firstname;
                echo "<script>alert('Registration successful!'); window.location.href='timeline.php';</script>";
                exit;
            } else {
                $error_top = 'Failed to save data.';
            }
        } catch (PDOException $e) {
            $error_top = 'Error: ' . $e->getMessage();
        }
    }
}
?>


<!-- HTML -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Register Pages</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" href="../../img/hero.PNG" />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../../dash/assets/vendor/fonts/boxicons.css" />
    <link rel="stylesheet" href="../../dash/assets/vendor/css/core.css" />
    <link rel="stylesheet" href="../../dash/assets/vendor/css/theme-default.css" />
    <link rel="stylesheet" href="../../dash/assets/css/demo.css" />
    <link rel="stylesheet" href="../../dash/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="../../dash/assets/vendor/css/pages/page-auth.css" />
</head>
<body>
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner">
                <div class="card">
                    <div class="card-body">
                        <div class="app-brand justify-content-center">
                            <a href="#" class="app-brand-link gap-2">
                                <span class="app-brand-logo demo">
                                    <img src="../../img/hero.PNG" alt="logo" style="width: 60px;">
                                </span>
                                <span class="app-brand-text demo text-body fw-bolder">Ocean's Feast</span>
                            </a>
                        </div>
                        <h4 class="mb-2">Are you hungry? 🍔</h4>
                        <p class="mb-4">Let's register!</p>

                        <!-- Error top message -->
                        <?php if ($error_top): ?>
                            <div class="alert alert-danger" role="alert">
                                <?= $error_top; ?>
                            </div>
                        <?php endif; ?>

                       <form id="formAuthentication" class="mb-3" action="" method="POST">
                        <div class="mb-3">
                            <label for="firstname" class="form-label">First Name</label>
                            <input type="text" class="form-control <?= isset($errors['firstname']) ? 'is-invalid' : '' ?>" id="firstname" name="firstname" placeholder="Enter your first name" 
                                value="<?= htmlspecialchars($firstname) ?>" required />
                            <?php if (isset($errors['firstname'])): ?>
                                <div class="invalid-feedback"><?= $errors['firstname'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="lastname" class="form-label">Last Name</label>
                            <input type="text" class="form-control <?= isset($errors['lastname']) ? 'is-invalid' : '' ?>" id="lastname" name="lastname" placeholder="Enter your last name" 
                                value="<?= htmlspecialchars($lastname) ?>" required />
                            <?php if (isset($errors['lastname'])): ?>
                                <div class="invalid-feedback"><?= $errors['lastname'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" placeholder="Enter your email" 
                                value="<?= htmlspecialchars($email) ?>" required />
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?= $errors['email'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="no_hp" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control <?= isset($errors['no_hp']) ? 'is-invalid' : '' ?>" id="no_hp" name="no_hp" placeholder="Enter your phone number" 
                                pattern="[0-9]{1,13}" maxlength="13" oninput="this.value=this.value.replace(/[^0-9]/g,'')" 
                                value="<?= htmlspecialchars($no_hp) ?>" required />
                            <?php if (isset($errors['no_hp'])): ?>
                                <div class="invalid-feedback"><?= $errors['no_hp'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" id="username" name="username" placeholder="Enter your username" 
                                value="<?= htmlspecialchars($username) ?>" required />
                            <?php if (isset($errors['username'])): ?>
                                <div class="invalid-feedback"><?= $errors['username'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3 form-password-toggle">
                            <label class="form-label" for="password">Password</label>
                            <div class="input-group input-group-merge">
                                <input type="password" id="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" name="password" placeholder="Password" required />
                                <span class="input-group-text cursor-pointer" id="toggle-password"><i class="bx bx-hide"></i></span>
                            </div>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback d-block"><?= $errors['password'] ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3 form-password-toggle">
                            <label class="form-label" for="password">Confirmation Password</label>
                            <div class="input-group input-group-merge">
                                <input type="password" id="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" name="password" placeholder="Password" required />
                                <span class="input-group-text cursor-pointer" id="toggle-password"><i class="bx bx-hide"></i></span>
                            </div>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback d-block"><?= $errors['password'] ?></div>
                            <?php endif; ?>
                        </div>
                        

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input <?= $error_checkbox ? 'is-invalid' : '' ?>" type="checkbox" id="terms-conditions" name="terms" <?= $terms_checked ? 'checked' : '' ?> />
                                <label class="form-check-label" for="terms-conditions">
                                    I agree to <a href="javascript:void(0);">privacy policy & terms</a>
                                </label>
                            </div>
                            <?php if ($error_checkbox): ?>
                                <div class="alert alert-danger mt-2 mb-0" role="alert">
                                    <?= $error_checkbox; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" name="register_pelanggan" class="btn btn-primary d-grid w-100">Sign up</button>
                    </form>


                        <p class="text-center">
                            <span>Already have an account?</span>
                            <a href="login_pelanggan.php">
                                <span>Sign in instead</span>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../dash/assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../../dash/assets/vendor/libs/popper/popper.js"></script>
    <script src="../../dash/assets/vendor/js/bootstrap.js"></script>
    <script src="../../dash/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../../dash/assets/vendor/js/menu.js"></script>
    <script src="../../dash/assets/js/main.js"></script>
    <script>
        document.getElementById('toggle-password').addEventListener('click', function () {
            const passwordField = document.getElementById('password');
            const icon = this.querySelector('i');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('bx-hide');
                icon.classList.add('bx-show');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('bx-show');
                icon.classList.add('bx-hide');
            }
        });
    </script>
</body>
</html>
