<?php
require_once 'DBConnector.php';

session_start();

// Already logged in → go straight to dashboard
if (isset($_SESSION['staff_ID'])) {
    header('Location: index.php');
    exit;
}

function sanitize($conn, $val) {
    return $conn->real_escape_string(trim($val));
}

$error = '';

// LOGIN HANDLER 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $input_username = sanitize($conn, $_POST['username'] ?? '');
    $input_password = $_POST['password'] ?? '';

    if ($input_username === '' || $input_password === '') {
        $error = "Please enter both username and password.";
    } else {
        $sql    = "SELECT staff_ID, first_name, last_name, role, password
                   FROM staff
                   WHERE username = '$input_username'
                   LIMIT 1";
        $result = $conn->query($sql);

        if ($result && $result->num_rows === 1) {
            $staff = $result->fetch_assoc();

            if ($input_password === $staff['password']) {
                $_SESSION['staff_ID']        = $staff['staff_ID'];
                $_SESSION['staff_name']      = $staff['first_name'] . ' ' . $staff['last_name'];
                $_SESSION['staff_role']      = $staff['role'];
                $_SESSION['staff_firstname'] = $staff['first_name'];

                header('Location: index.php');
                exit;
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "Username not found.";
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — UPV HSU Patient Records</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --bg:        #f4f1eb;
    --surface:   #ffffff;
    --border:    #c8c0b0;
    --ink:       #1a1714;
    --ink-muted: #6b6357;
    --accent-fg: #f4f1eb;
    --danger:    #8b1a1a;
    --radius:    8px;
    --mono:      'IBM Plex Mono', monospace;
    --sans:      'IBM Plex Sans', sans-serif;
}

body {
    font-family: var(--sans);
    background: var(--bg);
    color: var(--ink);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

/* ── CARD ── */
.login-card {
    background: var(--surface);
    border: 2px solid var(--ink);
    border-radius: var(--radius);
    width: 100%;
    max-width: 400px;
    overflow: hidden;
    box-shadow: 6px 6px 0px var(--ink);
}

/* ── HEADER ── */
.login-header {
    background: var(--ink);
    color: var(--accent-fg);
    padding: 28px 32px 24px;
    text-align: center;
}
.login-logo {
    font-family: var(--mono);
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    opacity: 0.6;
    margin-bottom: 10px;
}
.login-title {
    font-family: var(--mono);
    font-size: 20px;
    font-weight: 700;
    letter-spacing: 0.04em;
    line-height: 1.2;
}
.login-subtitle {
    font-family: var(--sans);
    font-size: 13px;
    opacity: 0.65;
    margin-top: 6px;
    font-weight: 300;
}

/* ── BODY ── */
.login-body { padding: 28px 32px 32px; }

/* ── ERROR ── */
.error-box {
    background: #fbe8e8;
    border: 1.5px solid var(--danger);
    border-left: 4px solid var(--danger);
    border-radius: var(--radius);
    color: var(--danger);
    font-family: var(--mono);
    font-size: 12px;
    padding: 10px 14px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* ── FORM ── */
.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 18px;
}
.form-label {
    font-family: var(--mono);
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--ink-muted);
}
.input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.input-icon {
    position: absolute;
    left: 12px;
    font-size: 14px;
    opacity: 0.45;
    pointer-events: none;
}
.form-control {
    width: 100%;
    font-family: var(--sans);
    font-size: 14px;
    color: var(--ink);
    background: var(--bg);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    padding: 10px 12px 10px 36px;
    outline: none;
    transition: border-color .15s, box-shadow .15s;
}
.form-control:focus {
    border-color: var(--ink);
    box-shadow: 0 0 0 3px rgba(26,23,20,.08);
}
.form-control::placeholder { color: var(--ink-muted); opacity: .6; }

/* Password toggle */
.toggle-pw {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 14px;
    opacity: 0.4;
    transition: opacity .15s;
    padding: 0;
    line-height: 1;
}
.toggle-pw:hover { opacity: .8; }

/* ── SUBMIT ── */
.btn-login {
    width: 100%;
    font-family: var(--mono);
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    background: var(--ink);
    color: var(--accent-fg);
    border: 2px solid var(--ink);
    border-radius: 40px;
    padding: 12px;
    cursor: pointer;
    transition: background .15s, transform .1s;
    margin-top: 6px;
}
.btn-login:hover   { background: #3a3330; }
.btn-login:active  { transform: translateY(1px); }

/* ── FOOTER ── */
.login-footer {
    text-align: center;
    padding: 14px 32px 20px;
    border-top: 1px solid var(--border);
    font-family: var(--mono);
    font-size: 11px;
    color: var(--ink-muted);
    letter-spacing: 0.04em;
}

/* ── SEED HINT ── */
.dev-hint {
    margin-top: 20px;
    background: #fffbe6;
    border: 1px dashed #c8aa30;
    border-radius: var(--radius);
    padding: 12px 16px;
    font-family: var(--mono);
    font-size: 11px;
    color: #5a4a00;
    max-width: 400px;
    width: 100%;
}
.dev-hint strong { display: block; margin-bottom: 6px; }
.dev-hint table  { width: 100%; border-collapse: collapse; }
.dev-hint td     { padding: 2px 6px; }
.dev-hint td:first-child { opacity: .6; }
</style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <div class="login-logo">UPV · Health Services Unit</div>
        <div class="login-title">Patient Records</div>
        <div class="login-subtitle">Sign in to continue</div>
    </div>

    <div class="login-body">

        <?php if ($error): ?>
        <div class="error-box">
            <span>⚠</span>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <div class="input-wrap">
                    <span class="input-icon">👤</span>
                    <input
                        type="text"
                        name="username"
                        id="username"
                        class="form-control"
                        placeholder="Enter username"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        autocomplete="username"
                        autofocus
                        required
                    >
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="input-wrap">
                    <span class="input-icon">🔒</span>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control"
                        placeholder="Enter password"
                        autocomplete="current-password"
                        required
                    >
                    <button type="button" class="toggle-pw" onclick="togglePassword()" id="toggleBtn" title="Show/hide password">👁</button>
                </div>
            </div>

            <button type="submit" class="btn-login">Sign In →</button>
        </form>
    </div>

    <div class="login-footer">
        UPV HSU · Digitalized Patient Records
    </div>
</div>

<!-- Accounts -->
<div class="dev-hint">
    <strong>🛠 Dev — Seeded accounts:</strong>
    <table>
        <tr><td>Admin</td><td><strong>msantos</strong> / 123</td></tr>
        <tr><td>Nurse</td><td><strong>jreyes</strong> / 123</td></tr>
        <tr><td>Nurse</td><td><strong>adelacruz</strong> / 123</td></tr>
    </table>
</div>

<script>
function togglePassword() {
    const pw  = document.getElementById('password');
    const btn = document.getElementById('toggleBtn');
    if (pw.type === 'password') {
        pw.type = 'text';
        btn.textContent = '🙈';
    } else {
        pw.type = 'password';
        btn.textContent = '👁';
    }
}
</script>
</body>
</html>
