<?php
require_once 'DBConnector.php';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();
if (!isset($_SESSION['staff_ID'])) {
    header('Location: login.php');
    exit;
}
$logged_in_staff = $_SESSION['staff_ID'];

function sanitize($conn, $val) {
    return $conn->real_escape_string(trim($val));
}

// FETCH SINGLE STAFF FOR EDIT MODAL (AJAX)
if (isset($_GET['get_staff'])) {
    $sid = (int)$_GET['get_staff'];
    $r   = $conn->query("SELECT staff_ID, first_name, last_name, role, username FROM staff WHERE staff_ID = $sid LIMIT 1");
    header('Content-Type: application/json');
    if ($r && $r->num_rows) {
        echo json_encode($r->fetch_assoc());
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
    }
    exit;
}

$action  = $_POST['action'] ?? $_GET['action'] ?? '';
$message = '';
$msgType = 'success';

// ACTION: ADD NEW STAFF
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize($conn, $_POST['first_name'] ?? '');
    $last_name  = sanitize($conn, $_POST['last_name']  ?? '');
    $role       = sanitize($conn, $_POST['role']       ?? '');
    $username   = sanitize($conn, $_POST['username']   ?? '');
    $password   = sanitize($conn, $_POST['password']   ?? '');

    $sql = "INSERT INTO staff (first_name, last_name, role, username, password)
            VALUES ('$first_name','$last_name','$role','$username','$password')";
    if ($conn->query($sql)) {
        $message = "Staff record added successfully.";
    } else {
        $message = "Error adding record: " . $conn->error;
        $msgType = 'error';
    }
}

// ACTION: EDIT STAFF
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_id   = (int)($_POST['staff_id'] ?? 0);
    $first_name = sanitize($conn, $_POST['first_name'] ?? '');
    $last_name  = sanitize($conn, $_POST['last_name']  ?? '');
    $role       = sanitize($conn, $_POST['role']       ?? '');
    $username   = sanitize($conn, $_POST['username']   ?? '');
    $new_pass   = sanitize($conn, $_POST['password']   ?? '');

    $check = $conn->query("SELECT staff_ID FROM staff WHERE staff_ID = $staff_id LIMIT 1");
    if ($check && $check->num_rows > 0) {
        // Only update password if a new one was provided
        $pass_sql = $new_pass !== '' ? ", password = '$new_pass'" : '';
        $sql = "UPDATE staff SET
                    first_name = '$first_name',
                    last_name  = '$last_name',
                    role       = '$role',
                    username   = '$username'
                    $pass_sql
                WHERE staff_ID = $staff_id";
        if ($conn->query($sql)) {
            $message = "Staff record updated successfully.";
        } else {
            $message = "Error updating record: " . $conn->error;
            $msgType = 'error';
        }
    } else {
        $message = "Record not found in database.";
        $msgType = 'error';
    }
}

// ACTION: DELETE STAFF
if ($action === 'delete' && isset($_GET['id'])) {
    $staff_id = (int)$_GET['id'];
    $check    = $conn->query("SELECT staff_ID FROM staff WHERE staff_ID = $staff_id LIMIT 1");
    if ($check && $check->num_rows > 0) {
        if ($conn->query("DELETE FROM staff WHERE staff_ID = $staff_id")) {
            $message = "Staff record deleted.";
        } else {
            $message = "Error deleting record: " . $conn->error;
            $msgType = 'error';
        }
    } else {
        $message = "Record not found in database.";
        $msgType = 'error';
    }
}

// DATA QUERY: FETCH STAFF LIST
$search      = sanitize($conn, $_GET['search'] ?? '');
$filter_sort = sanitize($conn, $_GET['sort']   ?? 'name_az');
$filter_role = sanitize($conn, $_GET['role']   ?? '');

$conditions = [];

if ($search !== '') {
    $conditions[] = "(
        staff_ID   LIKE '%$search%'
        OR first_name LIKE '%$search%'
        OR last_name  LIKE '%$search%'
        OR CONCAT(first_name,' ',last_name) LIKE '%$search%'
        OR role       LIKE '%$search%'
        OR username   LIKE '%$search%'
    )";
}

if ($filter_role !== '') {
    $conditions[] = "role = '$filter_role'";
}

$where = count($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

$order = match($filter_sort) {
    'name_za' => 'last_name DESC, first_name DESC',
    default   => 'last_name ASC,  first_name ASC',   // name_az
};

$staff_sql    = "SELECT staff_ID, first_name, last_name, role, username FROM staff $where ORDER BY $order";
$staff_result = $conn->query($staff_sql);

// Active filter count
$active_filters = 0;
if ($filter_sort !== 'name_az') $active_filters++;
if ($filter_role !== '')        $active_filters++;

// Fetch distinct roles for dropdowns
$roles_result = $conn->query("SELECT DISTINCT role FROM staff WHERE role IS NOT NULL AND role != '' ORDER BY role ASC");
$roles = [];
while ($r = $roles_result->fetch_assoc()) {
    $roles[] = $r['role'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Record — UPV HSU</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
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
    --success:   #1a4a1a;
    --radius:    6px;
    --mono:      'IBM Plex Mono', monospace;
    --sans:      'IBM Plex Sans', sans-serif;
}

body { font-family: var(--sans); background: var(--bg); color: var(--ink); min-height: 100vh; }

/* ── TOP BAR ── */
.topbar {
    background: var(--ink); color: var(--accent-fg);
    padding: 0 32px; height: 56px;
    display: flex; align-items: center; justify-content: space-between;
    border-bottom: 2px solid var(--border);
}
.topbar-left { display: flex; align-items: center; gap: 32px; }
.topbar-brand { font-family: var(--mono); font-size: 13px; letter-spacing:.08em; opacity:.7; }

/* ── TOP NAV TABS ── */
.top-nav-tabs { display: flex; gap: 12px; align-items: center; }
.tab {
    font-family: var(--mono); font-size: 13px; font-weight: 600;
    letter-spacing: .06em; text-transform: uppercase;
    padding: 8px 20px; border-radius: 40px;
    text-decoration: none; transition: all .2s ease;
}
.tab.active  { background: var(--accent-fg); color: var(--ink); }
.tab.inactive { background: transparent; color: var(--accent-fg); border: 1.5px solid rgba(244,241,235,.3); }
.tab.inactive:hover { background: rgba(244,241,235,.1); border-color: rgba(244,241,235,.6); }

.btn-home {
    font-family: var(--mono); font-size: 12px; font-weight: 600;
    letter-spacing:.1em; text-transform: uppercase;
    background: var(--accent-fg); color: var(--ink);
    border: none; padding: 8px 20px; border-radius: 40px;
    cursor: pointer; text-decoration: none; transition: opacity .15s;
}
.btn-home:hover { opacity:.85; }

/* ── MAIN ── */
.main { padding: 28px 32px; }

/* ── TOOLBAR ── */
.toolbar { display:flex; align-items:center; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
.search-wrap {
    display:flex; align-items:center;
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: var(--radius); overflow:hidden; flex:1; max-width:420px;
}
.search-label {
    font-family: var(--mono); font-size:12px; font-weight:600;
    letter-spacing:.06em; padding: 0 12px; color: var(--ink-muted);
    white-space:nowrap; border-right: 1.5px solid var(--border);
    height:38px; display:flex; align-items:center;
}
.search-input {
    flex:1; border:none; background:transparent;
    font-family: var(--mono); font-size:13px; color: var(--ink);
    padding: 0 12px; height:38px; outline:none;
}
.search-input::placeholder { color: var(--ink-muted); }
.btn-search {
    background: var(--ink); border:none; color: var(--accent-fg);
    padding: 0 14px; height:38px; cursor:pointer; font-size:14px; transition:opacity .15s;
}
.btn-search:hover { opacity:.75; }
.spacer { flex:1; }

/* ── BUTTONS ── */
.btn {
    font-family: var(--mono); font-size:12px; font-weight:600;
    letter-spacing:.08em; text-transform:uppercase;
    padding: 8px 20px; border-radius:40px; border: 2px solid var(--ink);
    cursor:pointer; transition: background .15s, color .15s; white-space:nowrap;
    text-decoration:none; display:inline-flex; align-items:center; gap:5px;
}
.btn-primary { background: var(--ink); color: var(--accent-fg); }
.btn-primary:hover  { background: #3a3330; }
.btn-outline { background: transparent; color: var(--ink); }
.btn-outline:hover  { background: var(--ink); color: var(--accent-fg); }
.btn-danger  { background: var(--danger); color:#fff; border-color: var(--danger); }
.btn-danger:hover   { opacity:.85; }

/* ── ROW ACTION BUTTONS ── */
.row-actions { display:flex; gap:6px; justify-content:center; }
.btn-row {
    font-family: var(--mono); font-size:10px; font-weight:600;
    letter-spacing:.06em; text-transform:uppercase;
    padding: 5px 12px; border-radius:40px; border: 1.5px solid;
    cursor:pointer; transition: background .15s, color .15s;
    white-space:nowrap; background:transparent;
}
.btn-row-edit   { color: var(--ink); border-color: var(--ink); }
.btn-row-edit:hover   { background: var(--ink); color: var(--accent-fg); }
.btn-row-delete { color: var(--danger); border-color: var(--danger); }
.btn-row-delete:hover { background: var(--danger); color:#fff; }

/* ── TOAST ── */
.toast {
    padding:12px 20px; border-radius: var(--radius);
    font-family: var(--mono); font-size:13px;
    margin-bottom:16px; border-left:4px solid;
    animation: slideIn .2s ease;
}
.toast-success { background:#e8f5e8; border-color: var(--success); color: var(--success); }
.toast-error   { background:#fbe8e8; border-color: var(--danger);  color: var(--danger);  }
@keyframes slideIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:none; } }

/* ── TABLE ── */
.table-card {
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: var(--radius); overflow:hidden;
}
.table-wrap { overflow-x:auto; }
table { width:100%; border-collapse:collapse; font-size:13.5px; }
thead tr { background: var(--ink); color: var(--accent-fg); }
thead th {
    font-family: var(--mono); font-size:11px; font-weight:600;
    letter-spacing:.1em; text-transform:uppercase;
    padding:13px 16px; text-align:left; white-space:nowrap;
}
thead th.th-actions { text-align:center; }
tbody tr { border-bottom:1px solid var(--border); }
tbody tr:last-child { border-bottom:none; }
tbody tr:hover { background:#f0ece4; }
td { padding:12px 16px; vertical-align:middle; color: var(--ink); }
td.mono { font-family: var(--mono); font-size:12.5px; }
.badge {
    display:inline-block; font-family: var(--mono);
    font-size:10px; font-weight:600; letter-spacing:.06em;
    padding:2px 8px; border-radius:40px;
    background: var(--ink); color: var(--accent-fg);
}
.role-badge {
    display:inline-block; font-family: var(--mono);
    font-size:10px; font-weight:600; letter-spacing:.06em;
    padding:3px 10px; border-radius:40px;
    border: 1.5px solid var(--ink); color: var(--ink);
}
.empty { text-align:center; padding:60px 20px; color: var(--ink-muted); font-family: var(--mono); font-size:13px; }
.empty-icon { font-size:32px; margin-bottom:12px; }
.table-footer {
    padding:10px 16px; border-top:1.5px solid var(--border);
    font-family: var(--mono); font-size:11px; color: var(--ink-muted);
    display:flex; justify-content:space-between; align-items:center;
}

/* ── MODAL ── */
.modal-overlay {
    display:none; position:fixed; inset:0;
    background:rgba(26,23,20,.55); z-index:100;
    align-items:center; justify-content:center; padding:20px;
}
.modal-overlay.open { display:flex; }
.modal {
    background: var(--surface); border: 2px solid var(--ink);
    border-radius: var(--radius); width:100%; max-width:520px;
    max-height:90vh; overflow-y:auto; animation: modalIn .2s ease;
}
@keyframes modalIn { from { opacity:0; transform:scale(.97) translateY(8px); } to { opacity:1; transform:none; } }
.modal-header {
    background: var(--ink); color: var(--accent-fg);
    padding:16px 20px; display:flex; justify-content:space-between; align-items:center;
}
.modal-title { font-family: var(--mono); font-size:13px; font-weight:600; letter-spacing:.08em; text-transform:uppercase; }
.modal-close { background:none; border:none; color: var(--accent-fg); font-size:18px; cursor:pointer; opacity:.7; line-height:1; }
.modal-close:hover { opacity:1; }
.modal-body   { padding:24px 20px; }
.modal-footer { padding:16px 20px; display:flex; gap:10px; justify-content:center; border-top:1px solid var(--border); }

/* ── FORM ── */
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.form-full  { grid-column:1 / -1; }
.form-group { display:flex; flex-direction:column; gap:6px; }
.form-label { font-family: var(--mono); font-size:11px; font-weight:600; letter-spacing:.08em; text-transform:uppercase; color: var(--ink-muted); }
.form-control {
    font-family: var(--sans); font-size:14px; color: var(--ink);
    background: var(--bg); border: 1.5px solid var(--border);
    border-radius: var(--radius); padding: 9px 12px; outline:none;
    transition: border-color .15s; width:100%;
}
.form-control:focus { border-color: var(--ink); }
.form-hint { font-family: var(--mono); font-size:10px; color: var(--ink-muted); margin-top:2px; }

/* ── CONFIRM MODAL ── */
.confirm-modal { max-width:360px; }
.confirm-body  { padding:32px 24px 24px; text-align:center; }
.confirm-icon  { font-size:40px; margin-bottom:12px; }
.confirm-body strong { font-size:16px; display:block; margin-bottom:8px; }
.confirm-body p { font-size:14px; color: var(--ink-muted); }

/* ── LOADING SPINNER ── */
.spinner {
    display:none; width:18px; height:18px;
    border:2px solid var(--border); border-top-color: var(--ink);
    border-radius:50%; animation:spin .6s linear infinite; margin:0 auto;
}
@keyframes spin { to { transform:rotate(360deg); } }

/* ── FILTER PANEL ── */
.filter-panel {
    display: none;
    background: var(--surface);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    padding: 20px 24px;
    margin-bottom: 16px;
    animation: slideIn .15s ease;
}
.filter-panel.open { display: block; }
.filter-panel-title {
    font-family: var(--mono); font-size: 11px; font-weight: 600;
    letter-spacing: .1em; text-transform: uppercase;
    color: var(--ink-muted); margin-bottom: 16px;
}
.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px; align-items: end;
}
.filter-group { display: flex; flex-direction: column; gap: 6px; }
.filter-label {
    font-family: var(--mono); font-size: 10px; font-weight: 600;
    letter-spacing: .08em; text-transform: uppercase; color: var(--ink-muted);
}
.filter-select {
    font-family: var(--sans); font-size: 13px; color: var(--ink);
    background: var(--bg); border: 1.5px solid var(--border);
    border-radius: var(--radius); padding: 8px 10px;
    outline: none; transition: border-color .15s; cursor: pointer;
}
.filter-select:focus { border-color: var(--ink); }
.filter-actions { display: flex; gap: 8px; align-items: flex-end; padding-top: 2px; }
.filter-badge {
    display: inline-flex; align-items: center; justify-content: center;
    width: 16px; height: 16px;
    background: var(--danger); color: #fff; border-radius: 50%;
    font-family: var(--mono); font-size: 9px; font-weight: 700;
    margin-left: 4px; vertical-align: middle;
}
.btn-filter { position: relative; display: inline-flex; align-items: center; gap: 4px; }
</style>
</head>
<body>

<div class="topbar">
    <div class="topbar-left">
        <span class="topbar-brand">UPV · HSU</span>
        <div class="top-nav-tabs">
            <a href="patients.php"   class="tab inactive">Patients</a>
            <a href="physicians.php" class="tab inactive">Physicians</a>
            <a href="staff.php"      class="tab active">Staff</a>
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:14px;">
        <span style="font-family:var(--mono);font-size:11px;opacity:.6;letter-spacing:.06em;">
            <?= htmlspecialchars($_SESSION['staff_name'] ?? '') ?>
            &nbsp;·&nbsp;
            <?= htmlspecialchars(strtoupper($_SESSION['staff_role'] ?? '')) ?>
        </span>
        <a href="index.php" class="btn-home">Home</a>
        <a href="logout.php" class="btn-home" style="background:transparent;color:var(--accent-fg);border:1.5px solid rgba(244,241,235,.3);">Logout</a>
    </div>
</div>

<div class="main">
    <?php if ($message): ?>
    <div class="toast toast-<?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="GET" action="staff.php" id="filterForm">
        <div class="toolbar">
            <div class="search-wrap">
                <span class="search-label">Search</span>
                <input
                    type="text" name="search" id="searchInput"
                    class="search-input"
                    placeholder="Name, ID, Username..."
                    value="<?= htmlspecialchars($search) ?>"
                    autocomplete="off"
                >
                <button type="submit" class="btn-search" title="Search">&#9906;</button>
            </div>

            <button type="button" class="btn btn-outline btn-filter" onclick="toggleFilters()" id="btnFilter">
                ⊟ Filters
                <?php if ($active_filters > 0): ?>
                <span class="filter-badge"><?= $active_filters ?></span>
                <?php endif; ?>
            </button>

            <?php if ($search || $active_filters > 0): ?>
            <a href="staff.php" class="btn btn-outline" style="padding:8px 14px;font-size:11px;">✕ Clear All</a>
            <?php endif; ?>

            <div class="spacer"></div>
            <button type="button" class="btn btn-primary" onclick="openAdd()">+ Add Staff</button>
        </div>

        <!-- FILTER PANEL -->
        <div class="filter-panel <?= $active_filters > 0 ? 'open' : '' ?>" id="filterPanel">
            <div class="filter-panel-title">Filter &amp; Sort</div>
            <div class="filter-grid">

                <!-- Sort order -->
                <div class="filter-group">
                    <label class="filter-label">Sort By</label>
                    <select name="sort" class="filter-select" onchange="this.form.submit()">
                        <option value="name_az" <?= $filter_sort === 'name_az' ? 'selected' : '' ?>>Name: A → Z</option>
                        <option value="name_za" <?= $filter_sort === 'name_za' ? 'selected' : '' ?>>Name: Z → A</option>
                    </select>
                </div>

                <!-- Filter by role -->
                <div class="filter-group">
                    <label class="filter-label">Role</label>
                    <select name="role" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Roles</option>
                        <?php foreach ($roles as $r): ?>
                        <option value="<?= htmlspecialchars($r) ?>" <?= $filter_role === $r ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst($r)) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Apply / Reset -->
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary" style="padding:8px 16px;font-size:11px;">Apply</button>
                    <a href="staff.php<?= $search ? '?search='.urlencode($search) : '' ?>" class="btn btn-outline" style="padding:8px 16px;font-size:11px;">Reset Filters</a>
                </div>

            </div>
        </div>
    </form>

    <div class="table-card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Username</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($staff_result && $staff_result->num_rows > 0):
                    $count = 0;
                    while ($row = $staff_result->fetch_assoc()):
                        $count++;
                        $fullName = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
                ?>
                <tr>
                    <td class="mono"><span class="badge"><?= htmlspecialchars($row['staff_ID']) ?></span></td>
                    <td><?= $fullName ?></td>
                    <td><span class="role-badge"><?= htmlspecialchars(ucfirst($row['role'])) ?></span></td>
                    <td class="mono"><?= htmlspecialchars($row['username']) ?></td>
                    <td>
                        <div class="row-actions">
                            <button class="btn-row btn-row-edit"   onclick="openEdit(<?= $row['staff_ID'] ?>)">Edit</button>
                            <button class="btn-row btn-row-delete" onclick="openConfirm(<?= $row['staff_ID'] ?>)">Delete</button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; else: $count = 0; ?>
                <tr><td colspan="5">
                    <div class="empty">
                        <div class="empty-icon">🪪</div>
                        <?= $search
                            ? "No staff matching &ldquo;" . htmlspecialchars($search) . "&rdquo;"
                            : "No staff records yet." ?>
                    </div>
                </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <span>
                <?php if ($search): ?>
                    Results for: <strong><?= htmlspecialchars($search) ?></strong>
                    <?php if ($active_filters > 0): ?> &nbsp;·&nbsp; <?php endif; ?>
                <?php endif; ?>
                <?php if ($filter_sort !== 'name_az'): ?>
                    Sort: <strong>Name Z→A</strong>
                    <?php if ($filter_role): ?> &nbsp;·&nbsp; <?php endif; ?>
                <?php endif; ?>
                <?php if ($filter_role): ?>
                    Role: <strong><?= htmlspecialchars(ucfirst($filter_role)) ?></strong> &nbsp;·&nbsp;
                <?php endif; ?>
                <?php if (!$search && !$active_filters): ?>
                    All records &nbsp;·&nbsp;
                <?php endif; ?>
                <?= $count ?> record<?= $count !== 1 ? 's' : '' ?>
            </span>
            <span>Staff Record · UPV HSU</span>
        </div>
    </div>
</div>


<!-- ADD MODAL -->
<div class="modal-overlay" id="modalAdd">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Add Staff Record</span>
            <button class="modal-close" onclick="closeModal('modalAdd')">✕</button>
        </div>
        <form method="POST" action="staff.php<?= $search ? '?search='.urlencode($search) : '' ?>">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">First Name *</label>
                        <input type="text" name="first_name" class="form-control" required placeholder="First Name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name *</label>
                        <input type="text" name="last_name" class="form-control" required placeholder="Last Name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role *</label>
                        <select name="role" class="form-control" required>
                            <option value="" disabled selected>Select a role</option>
                            <?php foreach ($roles as $r): ?>
                            <option value="<?= htmlspecialchars($r) ?>"><?= htmlspecialchars(ucfirst($r)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" class="form-control" required placeholder="Username" autocomplete="off">
                    </div>
                    <div class="form-full form-group">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-control" required placeholder="Password" autocomplete="new-password">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalAdd')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Staff</button>
            </div>
        </form>
    </div>
</div>


<!-- EDIT MODAL -->
<div class="modal-overlay" id="modalEdit">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Edit Staff Record</span>
            <button class="modal-close" onclick="closeModal('modalEdit')">✕</button>
        </div>
        <form method="POST" action="staff.php<?= $search ? '?search='.urlencode($search) : '' ?>">
            <input type="hidden" name="action"   value="edit">
            <input type="hidden" name="staff_id" id="editStaffId">
            <div class="modal-body" id="editModalBody">
                <div class="spinner" style="display:block;margin:40px auto;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalEdit')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="editSubmitBtn">Update Staff</button>
            </div>
        </form>
    </div>
</div>


<!-- DELETE CONFIRM -->
<div class="modal-overlay" id="modalConfirm">
    <div class="modal confirm-modal">
        <div class="modal-header">
            <span class="modal-title">Confirm Delete</span>
            <button class="modal-close" onclick="closeModal('modalConfirm')">✕</button>
        </div>
        <div class="confirm-body">
            <div class="confirm-icon">🗑️</div>
            <strong>Delete this staff record?</strong>
            <p>This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('modalConfirm')">Cancel</button>
            <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Yes, Delete</a>
        </div>
    </div>
</div>


<script>
// FILTER PANEL TOGGLE
function toggleFilters() {
    document.getElementById('filterPanel').classList.toggle('open');
}

function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
});

function openAdd() { openModal('modalAdd'); }

// EDIT — fetches fresh data from DB
function openEdit(staffId) {
    const body      = document.getElementById('editModalBody');
    const submitBtn = document.getElementById('editSubmitBtn');

    body.innerHTML = '<div class="spinner" style="display:block;margin:40px auto;"></div>';
    submitBtn.disabled = true;
    openModal('modalEdit');

    fetch('staff.php?get_staff=' + staffId)
        .then(res => {
            if (!res.ok) throw new Error('Record not found');
            return res.json();
        })
        .then(data => {
            document.getElementById('editStaffId').value = data.staff_ID;
            const roles = <?= json_encode($roles) ?>;
            const roleOptions = roles.map(r =>
                `<option value="${escHtml(r)}" ${r === data.role ? 'selected' : ''}>${escHtml(r.charAt(0).toUpperCase() + r.slice(1))}</option>`
            ).join('');
            body.innerHTML = `
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">First Name *</label>
                        <input type="text" name="first_name" class="form-control" required value="${escHtml(data.first_name)}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name *</label>
                        <input type="text" name="last_name" class="form-control" required value="${escHtml(data.last_name)}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role *</label>
                        <select name="role" class="form-control" required>${roleOptions}</select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" class="form-control" required value="${escHtml(data.username)}" autocomplete="off">
                    </div>
                    <div class="form-full form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current" autocomplete="new-password">
                        <span class="form-hint">Leave blank to keep the existing password.</span>
                    </div>
                </div>`;
            submitBtn.disabled = false;
        })
        .catch(() => {
            body.innerHTML = `<p style="color:var(--danger);font-family:var(--mono);font-size:13px;padding:20px;text-align:center;">
                ⚠ Could not load record from database.</p>`;
        });
}

// DELETE CONFIRM
function openConfirm(staffId) {
    const search = <?= json_encode($search) ?>;
    const qs     = search ? '&search=' + encodeURIComponent(search) : '';
    document.getElementById('confirmDeleteBtn').href = 'staff.php?action=delete&id=' + staffId + qs;
    openModal('modalConfirm');
}

function escHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

const toast = document.querySelector('.toast');
if (toast) setTimeout(() => toast.style.display = 'none', 4000);

document.getElementById('searchInput')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') e.target.closest('form').submit();
});
</script>
</body>
</html>
