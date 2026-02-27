<?php
session_start();

// --- Database config ---
$servername = "sql306.infinityfree.com";
$username   = "if0_40049528";
$password   = "saniya0905";
$database   = "if0_40049528_ccms9";

$con = new mysqli($servername, $username, $password, $database);
if ($con->connect_error) die("Connection failed: " . $con->connect_error);

$message = ''; // feedback message

// ------------------- HANDLE GET REQUESTS -------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['forgot_email'])) {
    $email = trim($_GET['forgot_email']);
    if (!$email) {
        echo "<script>alert('No email provided.'); window.location.href='login_register.php';</script>";
        exit();
    }
}

// ------------------- HANDLE POST REQUESTS -------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // -------- LOGIN --------
    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $pwd   = trim($_POST['pwd'] ?? '');

        if (!$email || !$pwd) {
            $message = '<div class="error-message">Please enter email and password</div>';
        } else {
            $stmt = $con->prepare("SELECT id, uname, email, pwd, role FROM student WHERE email=? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res && $res->num_rows > 0) {
                $user = $res->fetch_assoc();
                if ($pwd === $user['pwd']) {
                    $_SESSION['id']    = $user['id'];
                    $_SESSION['uname'] = $user['uname'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role']  = strtolower($user['role']);

                    setcookie("user_id", $user['id'], time()+86400, "/");
                    setcookie("user_name", $user['uname'], time()+86400, "/");
                    setcookie("user_email", $user['email'], time()+86400, "/");
                    setcookie("user_role", strtolower($user['role']), time()+86400, "/");

                    $role = strtolower($user['role']);
                    if ($role === "student") header("Location: student-dashboard.php");
                    elseif ($role === "faculty") header("Location: faculty-dashboard.php");
                    elseif ($role === "admin") header("Location: admin-dashboard.php");
                    exit();
                } else {
                    $message = '<div class="error-message">Invalid password</div>';
                }
            } else {
                $message = '<div class="error-message">Invalid email or password</div>';
            }
            $stmt->close();
        }

    // -------- REGISTER --------
    } elseif ($action === 'register') {
        $id    = trim($_POST['id'] ?? '');
        $uname = trim($_POST['uname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role  = $_POST['role'] ?? '';
        $pwd   = $_POST['pwd'] ?? '';
        $cpwd  = $_POST['cpwd'] ?? '';

        if (!$id || !$uname || !$email || !$role || !$pwd || !$cpwd) {
            $message = '<div class="error-message">Please fill in all fields.</div>';
        } elseif ($pwd !== $cpwd) {
            $message = '<div class="error-message">Passwords do not match.</div>';
        } else {
            $stmt = $con->prepare("SELECT id FROM student WHERE email=? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $message = '<div class="error-message">Email already registered.</div>';
            } else {
                $stmt->close();
                $stmt = $con->prepare("INSERT INTO student (id, uname, email, `role`, pwd) VALUES (?,?,?,?,?)");
                $stmt->bind_param("sssss", $id, $uname, $email, $role, $pwd);
                if ($stmt->execute()) {
                    $message = '<div class="success-message">Registration successful! Redirecting to login...</div>';
                    echo "<script>setTimeout(()=>{ window.location.href='login_register.php'; }, 2000);</script>";
                } else {
                    $message = '<div class="error-message">Database error: '.$con->error.'</div>';
                }
            }
            $stmt->close();
        }

    // -------- FORGOT PASSWORD RESET --------
    } elseif ($action === 'forgot') {
        $email = trim($_POST['email'] ?? '');
        $newpwd = $_POST['newpwd'] ?? '';
        $confpwd = $_POST['confpwd'] ?? '';

        if (!$email || !$newpwd || !$confpwd) {
            $message = '<div class="error-message">All fields are required.</div>';
        } elseif ($newpwd !== $confpwd) {
            $message = '<div class="error-message">Passwords do not match.</div>';
        } else {
            $stmt = $con->prepare("UPDATE student SET pwd=? WHERE email=?");
            $stmt->bind_param("ss", $newpwd, $email);
            if ($stmt->execute()) {
                $message = '<div class="success-message">Password updated successfully! Redirecting to login...</div>';
                echo "<script>setTimeout(()=>{ window.location.href='login_register.php'; }, 2000);</script>";
            } else {
                $message = '<div class="error-message">Database error: '.$con->error.'</div>';
            }
            $stmt->close();
        }
    }
}

$con->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>College Compliance System</title>
<style>
body{margin:0;padding:0;height:100vh;background:#181c22;font-family:'Segoe UI',Arial,sans-serif;}
.container{display:flex;align-items:center;justify-content:center;height:100vh;}
.card{background:#23272f;border-radius:18px;padding:35px 32px;box-shadow:0 2px 16px rgba(0,0,0,0.2);width:350px;}
.title{text-align:center;margin-bottom:18px;}
.icon{font-size:28px;display:block;margin-bottom:8px;color:#51a9ef;}
h2{color:#fff;font-weight:bold;margin:0 0 7px 0;font-size:21px;}
.subtitle{color:#74b0f7;font-size:13px;margin-bottom:0;}
.tabs{display:flex;margin-bottom:16px;justify-content:center;}
.tab{flex:1;background:none;border:none;color:#8fa3bf;font-size:15px;padding:5px 0 8px 0;cursor:pointer;transition:color 0.2s;border-bottom:2.5px solid transparent;}
.tab.active{color:#fff;border-bottom:2.5px solid #51a9ef;font-weight:bold;}
.form{display:flex;flex-direction:column;gap:9px;}
label{color:#c1cbdd;font-size:13px;margin-bottom:-4px;}
input,select,button{padding:8px;border-radius:6px;border:1px solid #3d4152;font-size:14px;background:#222533;color:#ececec;margin-bottom:4px;outline:none;}
input:focus,select:focus{border:1.5px solid #51a9ef;}
.submit-btn{background:#51a9ef;border:none;color:#fff;border-radius:6px;padding:9px;font-weight:600;margin-top:7px;cursor:pointer;font-size:15px;transition:background 0.2s;}
.submit-btn:hover{background:#318be9;}
.forgot-btn{text-align:right;color:#7aafea;font-size:13px;text-decoration:none;background:none;border:none;cursor:pointer;padding:0;margin-top:8px;}
.forgot-btn:hover{text-decoration:underline;}
.success-message{color:#1ee87f;background:#22332b;padding:10px;border-radius:6px;text-align:center;margin-top:10px;}
.error-message{color:#f07070;background:#332222;padding:10px;border-radius:6px;text-align:center;margin-top:10px;}
</style>
</head>
<body>
<div class="container">
<div class="card">
    <div class="title">
        <span class="icon">&#127891;</span>
        <h2>College Compliance<br>System</h2>
        <p class="subtitle">Secure Portal</p>
    </div>

    <?php if($message) echo $message; ?>

    <div class="tabs">
        <button id="loginTab" class="tab active">Login</button>
        <button id="registerTab" class="tab">Register</button>
        <button id="forgotTab" class="tab">Forgot Password</button>
    </div>

    <!-- LOGIN FORM -->
    <form method="POST" id="loginForm" class="form">
        <label>ID</label>
        <input type="text" name="id" placeholder="Enter your ID" required>
        <input type="hidden" name="action" value="login">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="Enter your email" required>
        <label>Password</label>
        <input type="password" name="pwd" placeholder="Enter your password" required>
        <button type="submit" class="submit-btn">Login</button>
    </form>

    <!-- REGISTER FORM -->
    <form method="POST" id="registerForm" class="form" style="display:none;">
        <input type="hidden" name="action" value="register">
        <label>ID</label>
        <input type="text" name="id" placeholder="Enter your ID" required>
        <label>Full Name</label>
        <input type="text" name="uname" placeholder="Enter your name" required>
        <label>Email Address</label>
        <input type="email" name="email" placeholder="Enter your email" required>
        <label>Role</label>
        <select name="role" required>
            <option value="">Select your role</option>
            <option value="student">Student</option>
            <option value="faculty">Faculty</option>
        </select>
        <label>Password</label>
        <input type="password" name="pwd" placeholder="Create a password" required>
        <label>Confirm Password</label>
        <input type="password" name="cpwd" placeholder="Confirm your password" required>
        <button type="submit" class="submit-btn">Register</button>
    </form>

    <!-- FORGOT PASSWORD FORM -->
    <form method="POST" id="forgotForm" class="form" style="display:none;">
        <input type="hidden" name="action" value="forgot">
        <label>ID</label>
        <input type="text" name="id" placeholder="Enter your ID" required>
        <label>Email</label>
        <input type="email" name="email" value="<?php echo $_GET['forgot_email'] ?? ''; ?>" required>
        <label>New Password</label>
        <input type="password" name="newpwd" required>
        <label>Confirm Password</label>
        <input type="password" name="confpwd" required>
        <button type="submit" class="submit-btn">Reset Password</button>
    </form>
</div>
</div>

<script>
const loginTab = document.getElementById('loginTab');
const registerTab = document.getElementById('registerTab');
const forgotTab = document.getElementById('forgotTab');
const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');
const forgotForm = document.getElementById('forgotForm');

loginTab.addEventListener('click', ()=>{
    loginTab.classList.add('active');
    registerTab.classList.remove('active');
    forgotTab.classList.remove('active');
    loginForm.style.display=''; registerForm.style.display='none'; forgotForm.style.display='none';
});
registerTab.addEventListener('click', ()=>{
    loginTab.classList.remove('active');
    registerTab.classList.add('active');
    forgotTab.classList.remove('active');
    loginForm.style.display='none'; registerForm.style.display=''; forgotForm.style.display='none';
});
forgotTab.addEventListener('click', ()=>{
    loginTab.classList.remove('active');
    registerTab.classList.remove('active');
    forgotTab.classList.add('active');
    loginForm.style.display='none'; registerForm.style.display='none'; forgotForm.style.display='';
});
</script>
</body>
</html>
