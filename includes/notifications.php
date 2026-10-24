<?php
/* Notification helpers (PHP versions of addNotification / unreadCount / myNotifications). */

function add_notification(int $userId, string $message): void
{
    q('INSERT INTO notifications (user_id, message, `date`, is_read) VALUES (?, ?, ?, 0)', [$userId, $message, today_iso()]);
}

/** Notify every Admin account. */
function notify_admins(string $message): void
{
    q("INSERT INTO notifications (user_id, message, `date`, is_read) SELECT id, ?, ?, 0 FROM users WHERE role = 'Admin'", [$message, today_iso()]);
}

function unread_count(int $userId): int
{
    return (int)q('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [$userId])->fetchColumn();
}

function my_notifications(int $userId): array
{
    return q('SELECT * FROM notifications WHERE user_id = ? ORDER BY `date` DESC, id DESC', [$userId])->fetchAll();
}
