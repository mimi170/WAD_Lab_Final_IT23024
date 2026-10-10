
<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $receiver_id = (int) $_SESSION['user_id'];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT sender_id, status FROM requests
         WHERE id = ? AND receiver_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "ii", $id, $receiver_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $request = mysqli_fetch_assoc($result);

    if (!$request || $request['status'] != 'pending') {
        header("Location: my_requests.php");
        exit();
    }

    $sender_id = (int) $request['sender_id'];

    $update = mysqli_prepare(
        $conn,
        "UPDATE requests SET status = 'accepted'
         WHERE id = ? AND receiver_id = ?"
    );
    mysqli_stmt_bind_param($update, "ii", $id, $receiver_id);

    if (mysqli_stmt_execute($update)) {
        $name_stmt = mysqli_prepare(
            $conn,
            "SELECT name FROM users WHERE id = ?"
        );
        mysqli_stmt_bind_param($name_stmt, "i", $receiver_id);
        mysqli_stmt_execute($name_stmt);
        $name_result = mysqli_stmt_get_result($name_stmt);
        $user = mysqli_fetch_assoc($name_result);

        $name = $user ? $user['name'] : "A student";
        $message = $name . " accepted your skill exchange request.";

        $notify = mysqli_prepare(
            $conn,
            "INSERT INTO notifications
             (user_id, message, is_read, created_at, actor_id, request_id)
             VALUES (?, ?, 0, NOW(), ?, ?)"
        );
        mysqli_stmt_bind_param(
            $notify, "isii", $sender_id, $message, $receiver_id, $id
        );
        mysqli_stmt_execute($notify);
        mysqli_stmt_close($notify);

        header("Location: my_requests.php");
        exit();
    }

    echo "Error accepting request.";
}
?>
