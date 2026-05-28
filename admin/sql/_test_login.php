<?php
require_once __DIR__ . '/../core/bootstrap.php';
$u = Database::fetch("SELECT id, email, role, status, is_active, password FROM users WHERE role='client' ORDER BY id DESC LIMIT 1");
echo "User: {$u['email']} role={$u['role']} status={$u['status']} is_active={$u['is_active']}\n";
echo "Hash prefix: " . substr($u['password'], 0, 7) . "\n";
$c = Database::fetch('SELECT id, user_id FROM clients WHERE user_id = ?', [$u['id']]);
print_r($c);
// test wrong password
$r = Auth::attempt($u['email'], 'wrongpass', 'client');
echo "Wrong pass: " . $r['message'] . "\n";
