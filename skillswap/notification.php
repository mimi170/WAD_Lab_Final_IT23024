
<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM notifications WHERE id = ? AND user_id = ?"
);

mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$notification = mysqli_fetch_assoc($result);

if (!$notification) {
    header("Location: dashboard.php");
    exit();
}

/* Get the person who accepted the request */
$actor = null;

if (!empty($notification['actor_id'])) {
    $actor_id = (int) $notification['actor_id'];

    $person_stmt = mysqli_prepare(
        $conn,
        "SELECT id, name, email, teach, learn
         FROM users WHERE id = ?"
    );

    mysqli_stmt_bind_param($person_stmt, "i", $actor_id);
    mysqli_stmt_execute($person_stmt);

    $person_result = mysqli_stmt_get_result($person_stmt);
    $actor = mysqli_fetch_assoc($person_result);
}

/* Mark notification as read */
$update = mysqli_prepare(
    $conn,
    "UPDATE notifications
     SET is_read = 1
     WHERE id = ? AND user_id = ?"
);

mysqli_stmt_bind_param($update, "ii", $id, $user_id);
mysqli_stmt_execute($update);

$message = $notification['message'];

if ($actor && stripos($message, 'accept') !== false) {
    $message = $actor['name'] . " accepted your request.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification - SkillSwap</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .notification-card {
            max-width: 650px;
            margin: 45px auto;
            padding: 30px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 28px rgba(30,35,70,.09);
        }

        .notification-message {
            padding: 18px;
            margin: 20px 0;
            background: #f5f4ff;
            border: 1px solid #e8e5ff;
            border-radius: 14px;
            color: #34385a;
            font-size: 17px;
            line-height: 1.7;
        }

        .person-card {
            margin-top: 20px;
            padding: 20px;
            background: #fafaff;
            border-radius: 15px;
        }

        .person-card p {
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .action-link {
            display: inline-block;
            margin: 8px 8px 0 0;
            padding: 11px 17px;
            background: #5146a8;
            color: white;
            border-radius: 9px;
            text-decoration: none;
        }

        .back-link {
            display: inline-block;
            margin-top: 22px;
            color: #5146a8;
            text-decoration: none;
        }
    </style>
</head>

<body>
<nav>
    <a href="dashboard.php">SkillSwap</a>
    <a href="dashboard.php">Home</a>
    <a href="profile.php">My Profile</a>
    <a href="logout.php">Logout</a>
</nav>

<div class="container">
    <div class="notification-card">

        <h2>🔔 Notification</h2>

        <div class="notification-message">
            <?php echo htmlspecialchars($message); ?>
        </div>

        <?php if ($actor) { ?>
            <div class="person-card">
                <h3>
                    <?php echo htmlspecialchars($actor['name']); ?>
                </h3>

                <p>
                    <b>Email:</b>
                    <?php echo htmlspecialchars($actor['email']); ?>
                </p>

                <p>
                    <b>Can teach:</b>
                    <?php echo htmlspecialchars($actor['teach']); ?>
                </p>

                <p>
                    <b>Wants to learn:</b>
                    <?php echo htmlspecialchars($actor['learn']); ?>
                </p>

                <a class="action-link"
                   href="profile.php?id=<?php echo (int)$actor['id']; ?>">
                    View Profile
                </a>

                <a class="action-link"
                   href="message.php?user_id=<?php echo (int)$actor['id']; ?>">
                    💬 Send Message
                </a>
            </div>
        <?php } ?>

        <a class="back-link" href="dashboard.php">
            ← Back to Dashboard
        </a>

    </div>
</div>
</body>
</html>
```