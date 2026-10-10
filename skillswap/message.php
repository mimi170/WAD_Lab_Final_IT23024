```php
<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$my_id = (int) $_SESSION['user_id'];
$other_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
$error = "";

if ($other_id <= 0 || $other_id === $my_id) {
    exit("Invalid user.");
}

/* GET OTHER USER */

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, name FROM users WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $other_id);
mysqli_stmt_execute($stmt);

$other_result = mysqli_stmt_get_result($stmt);
$other = mysqli_fetch_assoc($other_result);

if (!$other) {
    exit("User not found.");
}

/* SEND MESSAGE */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $text = trim($_POST['message'] ?? '');

    if ($text === '') {
        $error = "Please write a message first.";

    } elseif (strlen($text) > 5000) {
        $error = "Message is too long.";

    } else {

        $send = mysqli_prepare(
            $conn,
            "INSERT INTO messages (sender_id, receiver_id, message)
             VALUES (?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $send,
            "iis",
            $my_id,
            $other_id,
            $text
        );

        if (mysqli_stmt_execute($send)) {

            /* CREATE MESSAGE NOTIFICATION */

            $notice_text = "You received a new message.";

            $notify = mysqli_prepare(
                $conn,
                "INSERT INTO notifications
                 (user_id, message, is_read, created_at, actor_id, request_id)
                 VALUES (?, ?, 0, NOW(), ?, NULL)"
            );

            if ($notify) {
                mysqli_stmt_bind_param(
                    $notify,
                    "isi",
                    $other_id,
                    $notice_text,
                    $my_id
                );

                mysqli_stmt_execute($notify);
                mysqli_stmt_close($notify);
            }

            mysqli_stmt_close($send);

            header("Location: message.php?user_id=" . $other_id);
            exit();

        } else {
            $error = "Message could not be sent. Please try again.";
            mysqli_stmt_close($send);
        }
    }
}

/* LOAD CHAT MESSAGES */

$chat = mysqli_prepare(
    $conn,
    "SELECT m.*, u.name AS sender_name
     FROM messages m
     JOIN users u ON u.id = m.sender_id
     WHERE (m.sender_id = ? AND m.receiver_id = ?)
        OR (m.sender_id = ? AND m.receiver_id = ?)
     ORDER BY m.created_at ASC"
);

mysqli_stmt_bind_param(
    $chat,
    "iiii",
    $my_id,
    $other_id,
    $other_id,
    $my_id
);

mysqli_stmt_execute($chat);
$messages = mysqli_stmt_get_result($chat);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Chat with <?php echo htmlspecialchars($other['name']); ?> - SkillSwap</title>

    <link rel="stylesheet" href="style.css">

    <style>
        .chat-card {
            max-width: 750px;
            margin: 35px auto;
            padding: 25px;
            background: white;
            border-radius: 18px;
            box-shadow: 0 8px 28px rgba(30,35,70,.08);
        }

        .chat-header {
            padding-bottom: 15px;
            border-bottom: 1px solid #ececf4;
        }

        .chat-header h2 {
            color: #34385a;
            overflow-wrap: anywhere;
        }

        .chat-list {
            max-height: 420px;
            overflow-y: auto;
            padding: 15px 0;
        }

        .chat-message {
            padding: 12px 16px;
            margin: 10px 0;
            border-radius: 14px;
            background: #f0efff;
            overflow-wrap: anywhere;
        }

        .mine {
            background: #e7f8ef;
            margin-left: 12%;
        }

        .theirs {
            margin-right: 12%;
        }

        .chat-message small {
            display: block;
            margin-top: 7px;
            color: #777;
            font-size: 12px;
        }

        .chat-form textarea {
            width: 100%;
            min-height: 90px;
            box-sizing: border-box;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-family: inherit;
            resize: vertical;
        }

        .chat-form button {
            margin-top: 10px;
        }

        .error {
            color: #b42318;
            background: #fff0ef;
            padding: 12px;
            border-radius: 10px;
        }

        .back-link {
            display: inline-block;
            margin-top: 15px;
            color: #5146a8;
            text-decoration: none;
        }

        .empty-chat {
            color: #777;
            text-align: center;
            padding: 25px 10px;
        }
    </style>
</head>

<body>

<nav>
    <a href="dashboard.php">SkillSwap</a>
    <a href="dashboard.php">Home</a>
    <a href="profile.php">My Profile</a>
    <a href="my_requests.php">Requests</a>
    <a href="logout.php">Logout</a>
</nav>

<div class="container">

    <div class="chat-card">

        <div class="chat-header">
            <h2>
                💬 Chat with <?php echo htmlspecialchars($other['name']); ?>
            </h2>
            <p>Send a message and start your conversation.</p>
        </div>

        <?php if ($error !== '') { ?>
            <p class="error">
                <?php echo htmlspecialchars($error); ?>
            </p>
        <?php } ?>

        <div class="chat-list">

            <?php if (mysqli_num_rows($messages) > 0) { ?>

                <?php while ($m = mysqli_fetch_assoc($messages)) { ?>

                    <div class="chat-message <?php echo ((int)$m['sender_id'] === $my_id) ? 'mine' : 'theirs'; ?>">

                        <b>
                            <?php echo htmlspecialchars($m['sender_name']); ?>
                        </b>

                        <div>
                            <?php echo nl2br(htmlspecialchars($m['message'])); ?>
                        </div>

                        <small>
                            <?php echo htmlspecialchars($m['created_at']); ?>
                        </small>

                    </div>

                <?php } ?>

            <?php } else { ?>

                <p class="empty-chat">
                    No messages yet. Start the conversation!
                </p>

            <?php } ?>

        </div>

        <form method="POST" class="chat-form">

            <textarea
                name="message"
                maxlength="5000"
                placeholder="Write your message..."
                required
            ></textarea>

            <button type="submit">
                Send Message
            </button>

        </form>

        <a class="back-link" href="dashboard.php">
            ← Back to Dashboard
        </a>

    </div>

</div>

</body>
</html>
```