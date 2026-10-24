<?php
/* Authentication + role-based access control (replaces the JS SESSION object). */

function current_user(): ?array
{
    static $cache = false;
    if ($cache !== false) return $cache;
    $cache = null;
    if (!empty($_SESSION['user_id'])) {
        $u = q('SELECT id, name, username, email, role, contact, verified FROM users WHERE id = ?', [$_SESSION['user_id']])->fetch();
        if ($u) {
            $cache = $u;
            $_SESSION['role'] = $u['role'];
            $_SESSION['name'] = $u['name'];
        } else {
            unset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['name']);
        }
    }
    return $cache;
}

function home_for(string $role): string
{
    return $role === 'Admin' ? 'admin/dashboard.php' : 'resident/dashboard.php';
}

function require_login(): array
{
    $u = current_user();
    if (!$u) redirect('login.php');
    return $u;
}

/** Blocks anyone whose role does not match (e.g. a Resident typing an /admin/ URL). */
function require_role(string $role): array
{
    $u = require_login();
    if ($u['role'] !== $role) {
        flash_set('toast', "You don't have access to that page.");
        redirect(home_for($u['role']));
    }
    return $u;
}
