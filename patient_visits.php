<?php
require_once 'DBConnector.php';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();
// if (!isset($_SESSION['staff_ID'])) { header('Location: login.php'); exit; }
$logged_in_staff = $_SESSION['staff_ID'] ?? 2;

function sanitize($conn, $val) {
    return $conn->real_escape_string(trim($val));
}

if (isset($_GET['get_visit'])) {
    $vid = (int)$_GET['get_visit'];
    $r   = $conn->query("SELECT * FROM patient_visits WHERE visit_ID = $vid LIMIT 1");
    header('Content-Type: application/json');
    if ($r && $r->num_rows) {
        echo json_encode($r->fetch_assoc());
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
    }
    exit;
}

// ACTIONS
$action  = $_POST['action'] ?? $_GET['action'] ?? '';
$message = '';
$msgType = 'success';

// ADD
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id   = (int)$_POST['patient_id'];
    $physician_id = (int)$_POST['physician_id'];
    $visit_date   = sanitize($conn, $_POST['visit_date']);
    $symptoms     = sanitize($conn, $_POST['symptoms']);
    $prescription = sanitize($conn, $_POST['prescription']);
    $today        = date('Y-m-d');

    $sql = "INSERT INTO patient_visits
              (symptoms_description, prescription_details, visit_date, created_at, updated_at, patient_ID, physician_ID, created_by)
            VALUES
              ('$symptoms','$prescription','$visit_date','$today','$today',$patient_id,$physician_id,$logged_in_staff)";
    if ($conn->query($sql)) {
        $message = "Visit record added successfully.";
    } else {
        $message = "Error adding record: " . $conn->error;
        $msgType = 'error';
    }
}

// EDIT
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $visit_id     = (int)$_POST['visit_id'];
    $patient_id   = (int)$_POST['patient_id'];
    $physician_id = (int)$_POST['physician_id'];
    $visit_date   = sanitize($conn, $_POST['visit_date']);
    $symptoms     = sanitize($conn, $_POST['symptoms']);
    $prescription = sanitize($conn, $_POST['prescription']);
    $today        = date('Y-m-d');

    // Verify the record exists in the DB before updating
    $check = $conn->query("SELECT visit_ID FROM patient_visits WHERE visit_ID = $visit_id LIMIT 1");
    if ($check && $check->num_rows > 0) {
        $sql = "UPDATE patient_visits SET
                  symptoms_description = '$symptoms',
                  prescription_details = '$prescription',
                  visit_date           = '$visit_date',
                  patient_ID           = $patient_id,
                  physician_ID         = $physician_id,
                  updated_at           = '$today'
                WHERE visit_ID = $visit_id";
        if ($conn->query($sql)) {
            $message = "Visit record updated successfully.";
        } else {
            $message = "Error updating record: " . $conn->error;
            $msgType = 'error';
        }
    } else {
        $message = "Record not found in database.";
        $msgType = 'error';
    }
}

// DELETE
if ($action === 'delete' && isset($_GET['id'])) {
    $visit_id = (int)$_GET['id'];
    $check    = $conn->query("SELECT visit_ID FROM patient_visits WHERE visit_ID = $visit_id LIMIT 1");
    if ($check && $check->num_rows > 0) {
        if ($conn->query("DELETE FROM patient_visits WHERE visit_ID = $visit_id")) {
            $message = "Visit record deleted.";
        } else {
            $message = "Error deleting record: " . $conn->error;
            $msgType = 'error';
        }
    } else {
        $message = "Record not found in database.";
        $msgType = 'error';
    }
}

// FETCH VISITS 
$search = sanitize($conn, $_GET['search'] ?? '');
$where  = '';
if ($search !== '') {
    $where = "WHERE
        pv.visit_ID LIKE '%$search%'
        OR p.first_name  LIKE '%$search%'
        OR p.last_name   LIKE '%$search%'
        OR CONCAT(p.first_name,' ',p.last_name) LIKE '%$search%'
        OR ph.first_name LIKE '%$search%'
        OR ph.last_name  LIKE '%$search%'
        OR CONCAT(ph.first_name,' ',ph.last_name) LIKE '%$search%'
        OR pv.visit_date LIKE '%$search%'
        OR pv.symptoms_description LIKE '%$search%'
        OR pv.prescription_details LIKE '%$search%'
        OR p.affiliation LIKE '%$search%'";
}

$visits_sql = "
    SELECT
        pv.visit_ID,
        CONCAT(p.first_name,' ',p.last_name)   AS patient_name,
        p.patient_ID,
        CONCAT(ph.first_name,' ',ph.last_name) AS physician_name,
        ph.physician_ID,
        pv.visit_date,
        pv.symptoms_description,
        pv.prescription_details,
        pv.created_at,
        pv.updated_at
    FROM patient_visits pv
    JOIN patient   p  ON pv.patient_ID   = p.patient_ID
    JOIN physician ph ON pv.physician_ID = ph.physician_ID
    $where
    ORDER BY pv.visit_date DESC, pv.visit_ID DESC
";
$visits_result = $conn->query($visits_sql);

// ─── FETCH PATIENTS & PHYSICIANS for dropdowns
$patients_result   = $conn->query("SELECT patient_ID,   CONCAT(first_name,' ',last_name) AS full_name FROM patient   ORDER BY last_name");
$physicians_result = $conn->query("SELECT physician_ID, CONCAT(first_name,' ',last_name) AS full_name FROM physician ORDER BY last_name");

$patients   = $patients_result->fetch_all(MYSQLI_ASSOC);
$physicians = $physicians_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Patient Visits — UPV HSU</title>
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
.topbar-brand  { font-family: var(--mono); font-size: 13px; letter-spacing:.08em; opacity:.7; }
.topbar-title  { font-family: var(--mono); font-size: 15px; font-weight:600; letter-spacing:.05em; }
.btn-home {
    font-family: var(--mono); font-size: 12px; font-weight:600;
    letter-spacing:.1em; text-transform:uppercase;
    background: var(--accent-fg); color: var(--ink);
    border: none; padding: 8px 20px; border-radius: 40px;
    cursor: pointer; text-decoration: none; transition: opacity .15s;
}
.btn-home:hover { opacity:.75; }

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
.btn-primary  { background: var(--ink); color: var(--accent-fg); }
.btn-primary:hover  { background: #3a3330; }
.btn-outline  { background: transparent; color: var(--ink); }
.btn-outline:hover  { background: var(--ink); color: var(--accent-fg); }
.btn-danger   { background: var(--danger); color:#fff; border-color: var(--danger); }
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
.td-long { max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.badge {
    display:inline-block; font-family: var(--mono);
    font-size:10px; font-weight:600; letter-spacing:.06em;
    padding:2px 8px; border-radius:40px;
    background: var(--ink); color: var(--accent-fg);
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
    border-radius: var(--radius); width:100%; max-width:560px;
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
textarea.form-control { resize:vertical; min-height:80px; }

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
</style>
</head>
<body>

<div class="topbar">
    <span class="topbar-brand">UPV · HSU</span>
    <span class="topbar-title">Patient Visits</span>
    <a href="index.php" class="btn-home">Home</a>
</div>

<div class="main">

    <?php if ($message): ?>
    <div class="toast toast-<?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="GET" action="">
        <div class="toolbar">
            <div class="search-wrap">
                <span class="search-label">Search</span>
                <input
                    type="text" name="search" id="searchInput"
                    class="search-input"
                    placeholder="Name, ID, date, symptoms…"
                    value="<?= htmlspecialchars($search) ?>"
                    autocomplete="off"
                >
                <button type="submit" class="btn-search" title="Search">&#9906;</button>
            </div>
            <?php if ($search): ?>
            <a href="patient_visits.php" class="btn btn-outline" style="padding:8px 14px;font-size:11px;">✕ Clear</a>
            <?php endif; ?>
            <div class="spacer"></div>
            <button type="button" class="btn btn-primary" onclick="openAdd()">+ Add Visit</button>
        </div>
    </form>

    <div class="table-card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Visit ID</th>
                        <th>Patient</th>
                        <th>Physician</th>
                        <th>Visit Date</th>
                        <th>Symptoms</th>
                        <th>Prescription</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($visits_result && $visits_result->num_rows > 0):
                    $count = 0;
                    while ($row = $visits_result->fetch_assoc()):
                        $count++;
                ?>
                <tr>
                    <td class="mono"><span class="badge"><?= $row['visit_ID'] ?></span></td>
                    <td><?= htmlspecialchars($row['patient_name']) ?></td>
                    <td><?= htmlspecialchars($row['physician_name']) ?></td>
                    <td class="mono"><?= $row['visit_date'] ?></td>
                    <td class="td-long" title="<?= htmlspecialchars($row['symptoms_description']) ?>">
                        <?= htmlspecialchars($row['symptoms_description']) ?>
                    </td>
                    <td class="td-long" title="<?= htmlspecialchars($row['prescription_details']) ?>">
                        <?= htmlspecialchars($row['prescription_details']) ?>
                    </td>
                    <td>
                        <div class="row-actions">
                            <button
                                class="btn-row btn-row-edit"
                                onclick="openEdit(<?= $row['visit_ID'] ?>)"
                            >Edit/View</button>
                            <button
                                class="btn-row btn-row-delete"
                                onclick="openConfirm(<?= $row['visit_ID'] ?>)"
                            >Delete</button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; else: $count = 0; ?>
                <tr><td colspan="7">
                    <div class="empty">
                        <div class="empty-icon">📋</div>
                        <?= $search
                            ? "No visits matching &ldquo;" . htmlspecialchars($search) . "&rdquo;"
                            : "No visit records yet." ?>
                    </div>
                </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <span>
                <?php if ($search): ?>
                    Results for: <strong><?= htmlspecialchars($search) ?></strong> &nbsp;·&nbsp;
                <?php endif; ?>
                <?= $count ?> record<?= $count !== 1 ? 's' : '' ?>
            </span>
            <span>Patient Visits · UPV HSU</span>
        </div>
    </div>
</div>


<div class="modal-overlay" id="modalAdd">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Add Visit Record</span>
            <button class="modal-close" onclick="closeModal('modalAdd')">✕</button>
        </div>
        <form method="POST" action="patient_visits.php<?= $search ? '?search='.urlencode($search) : '' ?>">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Patient *</label>
                        <select name="patient_id" class="form-control" required>
                            <option value="">— Select patient —</option>
                            <?php foreach ($patients as $p): ?>
                            <option value="<?= $p['patient_ID'] ?>"><?= htmlspecialchars($p['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Physician *</label>
                        <select name="physician_id" class="form-control" required>
                            <option value="">— Select physician —</option>
                            <?php foreach ($physicians as $ph): ?>
                            <option value="<?= $ph['physician_ID'] ?>"><?= htmlspecialchars($ph['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Visit Date *</label>
                        <input type="date" name="visit_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-full form-group">
                        <label class="form-label">Symptoms / Chief Complaint *</label>
                        <textarea name="symptoms" class="form-control" required placeholder="Describe symptoms…"></textarea>
                    </div>
                    <div class="form-full form-group">
                        <label class="form-label">Prescription / Treatment *</label>
                        <textarea name="prescription" class="form-control" required placeholder="Medications, instructions…"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalAdd')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Visit</button>
            </div>
        </form>
    </div>
</div>


<div class="modal-overlay" id="modalEdit">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Edit Visit Record</span>
            <button class="modal-close" onclick="closeModal('modalEdit')">✕</button>
        </div>
        <form method="POST" action="patient_visits.php<?= $search ? '?search='.urlencode($search) : '' ?>">
            <input type="hidden" name="action"   value="edit">
            <input type="hidden" name="visit_id" id="editVisitId">
            <div class="modal-body" id="editModalBody">
                <!-- Filled dynamically via AJAX fetch from DB -->
                <div class="spinner" id="editSpinner" style="display:block;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalEdit')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="editSubmitBtn">Update Visit</button>
            </div>
        </form>
    </div>
</div>


<div class="modal-overlay" id="modalConfirm">
    <div class="modal confirm-modal">
        <div class="modal-header">
            <span class="modal-title">Confirm Delete</span>
            <button class="modal-close" onclick="closeModal('modalConfirm')">✕</button>
        </div>
        <div class="confirm-body">
            <div class="confirm-icon">🗑️</div>
            <strong>Delete this visit record?</strong>
            <p>This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('modalConfirm')">Cancel</button>
            <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Yes, Delete</a>
        </div>
    </div>
</div>


<script>
// MODALS
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
});

// ADD 
function openAdd() { openModal('modalAdd'); }

// EDIT 
function openEdit(visitId) {
    const body    = document.getElementById('editModalBody');
    const spinner = document.getElementById('editSpinner');
    const submitBtn = document.getElementById('editSubmitBtn');

    // Show spinner while loading
    body.innerHTML = '<div class="spinner" style="display:block;margin:40px auto;"></div>';
    submitBtn.disabled = true;
    openModal('modalEdit');

    // Fetch the record directly from the database
    fetch('patient_visits.php?get_visit=' + visitId)
        .then(res => {
            if (!res.ok) throw new Error('Record not found');
            return res.json();
        })
        .then(data => {
            document.getElementById('editVisitId').value = data.visit_ID;

            // Build patients dropdown with current value selected
            const patients   = <?= json_encode($patients) ?>;
            const physicians = <?= json_encode($physicians) ?>;

            let patientOptions   = patients.map(p =>
                `<option value="${p.patient_ID}" ${p.patient_ID == data.patient_ID ? 'selected' : ''}>${escHtml(p.full_name)}</option>`
            ).join('');
            let physicianOptions = physicians.map(ph =>
                `<option value="${ph.physician_ID}" ${ph.physician_ID == data.physician_ID ? 'selected' : ''}>${escHtml(ph.full_name)}</option>`
            ).join('');

            body.innerHTML = `
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Patient *</label>
                        <select name="patient_id" id="editPatientId" class="form-control" required>${patientOptions}</select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Physician *</label>
                        <select name="physician_id" id="editPhysicianId" class="form-control" required>${physicianOptions}</select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Visit Date *</label>
                        <input type="date" name="visit_date" id="editVisitDate" class="form-control" required value="${data.visit_date}">
                    </div>
                    <div class="form-full form-group">
                        <label class="form-label">Symptoms / Chief Complaint *</label>
                        <textarea name="symptoms" id="editSymptoms" class="form-control" required>${escHtml(data.symptoms_description)}</textarea>
                    </div>
                    <div class="form-full form-group">
                        <label class="form-label">Prescription / Treatment *</label>
                        <textarea name="prescription" id="editPrescription" class="form-control" required>${escHtml(data.prescription_details)}</textarea>
                    </div>
                </div>`;
            submitBtn.disabled = false;
        })
        .catch(err => {
            body.innerHTML = `<p style="color:var(--danger);font-family:var(--mono);font-size:13px;padding:20px;text-align:center;">
                ⚠ Could not load record from database.</p>`;
        });
}

// DELETE CONFIRM
function openConfirm(visitId) {
    const search = <?= json_encode($search) ?>;
    const qs     = search ? '&search=' + encodeURIComponent(search) : '';
    document.getElementById('confirmDeleteBtn').href =
        'patient_visits.php?action=delete&id=' + visitId + qs;
    openModal('modalConfirm');
}

// HELPERS 
function escHtml(str) {
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// TOAST AUTO-DISMISS 
const toast = document.querySelector('.toast');
if (toast) setTimeout(() => toast.style.display = 'none', 4000);

// SEARCH ON ENTER
document.getElementById('searchInput')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') e.target.closest('form').submit();
});
</script>
</body>
</html>