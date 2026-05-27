<?php
session_start();
if (!isset($_SESSION['staff_ID'])) {
    header('Location: login.php');
    exit;
}
$name = $_SESSION['staff_firstname'] ?? $_SESSION['staff_name'] ?? 'Staff';
$role = strtoupper($_SESSION['staff_role'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — UPV HSU</title>
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
    --radius:    8px;
    --mono:      'IBM Plex Mono', monospace;
    --sans:      'IBM Plex Sans', sans-serif;
}

body { font-family: var(--sans); background: var(--bg); color: var(--ink); min-height: 100vh; }

/* ── TOPBAR ── */
.topbar {
    background: var(--ink); color: var(--accent-fg);
    padding: 0 32px; height: 56px;
    display: flex; align-items: center; justify-content: space-between;
    border-bottom: 2px solid var(--border);
}
.topbar-brand { font-family: var(--mono); font-size: 13px; letter-spacing:.08em; opacity:.7; }
.topbar-title { font-family: var(--mono); font-size: 15px; font-weight:600; letter-spacing:.05em; }
.topbar-right { display:flex; align-items:center; gap:14px; }
.topbar-user  { font-family: var(--mono); font-size:11px; opacity:.6; letter-spacing:.06em; }
.btn-logout {
    font-family: var(--mono); font-size: 12px; font-weight:600;
    letter-spacing:.1em; text-transform:uppercase;
    background: transparent; color: var(--accent-fg);
    border: 1.5px solid rgba(244,241,235,.3);
    padding: 7px 18px; border-radius: 40px;
    cursor: pointer; text-decoration: none; transition: border-color .15s, opacity .15s;
}
.btn-logout:hover { border-color: var(--accent-fg); opacity:.85; }

/* ── HERO ── */
.hero {
    padding: 48px 32px 5px;
    max-width: 900px;
    margin: 0 auto;
}
.hero-greeting {
    font-family: var(--mono);
    font-size: 12px;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: var(--ink-muted);
    margin-bottom: 8px;
}
.hero-title {
    font-family: var(--mono);
    font-size: 28px;
    font-weight: 700;
    letter-spacing: .02em;
    line-height: 1.2;
    margin-bottom: 6px;
}
.hero-sub {
    font-size: 15px;
    color: var(--ink-muted);
    font-weight: 300;
}

.instruction {
    text-align: center;
}

/* ── DIVIDER ── */
.divider {
    max-width: 900px; margin: 24px auto 0;
    border: none; border-top: 1.5px solid var(--border);
}

/* ── MODULE GRID ── */
.modules {
    max-width: 900px;
    margin: 28px auto 0;
    padding: 0 32px 48px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 16px;
}

.module-card {
    background: var(--surface);
    border: 2px solid var(--border);
    border-radius: var(--radius);
    padding: 24px;
    text-decoration: none;
    color: var(--ink);
    transition: border-color .15s, box-shadow .15s, transform .1s;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.module-card:hover {
    border-color: var(--ink);
    box-shadow: 4px 4px 0 var(--ink);
    transform: translate(-2px, -2px);
}

.module-icon  { font-size: 28px; line-height: 1; }
.module-title {
    font-family: var(--mono);
    font-size: 14px;
    font-weight: 600;
    letter-spacing: .04em;
}
.module-desc  { font-size: 13px; color: var(--ink-muted); font-weight: 300; line-height: 1.5; }

/* ── FOOTER ── */
.footer {
    text-align: center;
    padding: 20px;
    font-family: var(--mono);
    font-size: 11px;
    color: var(--ink-muted);
    letter-spacing: .06em;
    border-top: 1px solid var(--border);
}
</style>
</head>
<body>

<div class="topbar">
    <span class="topbar-brand">UPV · HSU</span>
    <span class="topbar-title">Dashboard</span>
    <div class="topbar-right">
        <span class="topbar-user">
            <?= htmlspecialchars($_SESSION['staff_name'] ?? '') ?>
            &nbsp;·&nbsp;
            <?= htmlspecialchars($role) ?>
        </span>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</div>

<div class="hero">
    <div class="hero-greeting">Good day</div>
    <div class="hero-title">Welcome back, <?= htmlspecialchars($name) ?>.</div>
    <div class="hero-sub">UPV Health Services Unit — Digitalized Patient Records</div>
</div>

<hr class="divider">
<h1 class="instruction">Choose Action</h1>

<div class="modules">

    <!-- PROFILES — active -->
    <a href="patients.php" class="module-card">
        <div class="module-icon">🧑‍⚕️</div>
        <div class="module-title">Patients</div>
        <div class="module-desc">Manage patient or physician profiles, contact details, and affiliations.</div>
    </a>

     <!-- VISIT HISTORY — active -->
    <a href="patient_visits.php" class="module-card">
        <div class="module-icon">📋</div>
        <div class="module-title">Visit History</div>
        <div class="module-desc">View, add, edit, and delete visit records. Search by name, date, symptoms, and more.</div>
    </a>

    <!-- NEW VISIT — placeholder -->
    <a href="new_visit.php" class="module-card">
        <div class="module-icon">👨‍⚕️</div>
        <div class="module-title">New Visit</div>
        <div class="module-desc">Input a new Patient Visit for an existing or new patient.</div>
    </a>

    <!-- Staff — admin only -->
    <?php if (strtolower($_SESSION['staff_role'] ?? '') === 'admin'): ?>
    <a href="staff.php" class="module-card">
        <div class="module-icon">🔐</div>
        <div class="module-title">Staff Management</div>
        <div class="module-desc">Manage staff accounts, roles, and credentials. Admin only.</div>
    </a>
    <?php endif; ?>

</div>

<div class="footer">
    UPV HSU · Digitalized Patient Records &nbsp;·&nbsp; <?= date('Y') ?>
</div>

</body>
</html>
