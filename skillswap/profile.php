
<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$logged_in_user = (int) $_SESSION['user_id'];

$view_id = isset($_GET['id'])
    ? (int) $_GET['id']
    : $logged_in_user;

$is_own_profile = ($view_id === $logged_in_user);
$upload_message = "";

/* PROFILE PICTURE UPLOAD - OWN PROFILE ONLY */

if (isset($_POST['upload_pic']) && $is_own_profile) {

    if (
        isset($_FILES['profile_pic']) &&
        $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK
    ) {

        $tmp_name = $_FILES['profile_pic']['tmp_name'];
        $extension = strtolower(
            pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION)
        );

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $image_info = getimagesize($tmp_name);

        if (
            in_array($extension, $allowed, true) &&
            $image_info !== false &&
            $_FILES['profile_pic']['size'] <= 5 * 1024 * 1024
        ) {

            $upload_folder = "uploads/";

            if (!is_dir($upload_folder)) {
                mkdir($upload_folder, 0755, true);
            }

            $new_name = "profile_" . $logged_in_user . "." . $extension;
            $destination = $upload_folder . $new_name;

            if (move_uploaded_file($tmp_name, $destination)) {

                $pic_stmt = mysqli_prepare(
                    $conn,
                    "UPDATE users SET profile_pic = ? WHERE id = ?"
                );

                mysqli_stmt_bind_param(
                    $pic_stmt,
                    "si",
                    $new_name,
                    $logged_in_user
                );

                mysqli_stmt_execute($pic_stmt);

                header("Location: profile.php");
                exit();

            } else {
                $upload_message = "Could not upload the picture. Please try again.";
            }

        } else {
            $upload_message = "Choose a valid JPG, JPEG, PNG or WEBP image up to 5 MB.";
        }

    } else {
        $upload_message = "Please select an image to upload.";
    }
}

/* GET THE PROFILE TO DISPLAY */

$profile_stmt = mysqli_prepare(
    $conn,
    "SELECT id, name, email, teach, learn, profile_pic
     FROM users
     WHERE id = ?"
);

mysqli_stmt_bind_param($profile_stmt, "i", $view_id);
mysqli_stmt_execute($profile_stmt);

$profile_result = mysqli_stmt_get_result($profile_stmt);
$user = mysqli_fetch_assoc($profile_result);

if (!$user) {
    http_response_code(404);
    exit("Student profile not found.");
}

$profile_name = htmlspecialchars($user['name'] ?? '');
$profile_email = htmlspecialchars($user['email'] ?? '');
$profile_teach = htmlspecialchars($user['teach'] ?? '');
$profile_learn = htmlspecialchars($user['learn'] ?? '');

$picture_file = basename($user['profile_pic'] ?? '');
$picture_path = "uploads/" . $picture_file;

$has_picture = (
    $picture_file !== "" &&
    is_file($picture_path)
);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo $profile_name; ?> - SkillSwap
    </title>

    <link rel="stylesheet" href="style.css">

    <style>
        .profile-cover {
            background: linear-gradient(
                135deg,
                #151832,
                #29235c,
                #5146a8
            );
            border-radius: 28px;
            padding: 45px 45px 35px;
            position: relative;
            overflow: hidden;
            color: white;
            box-shadow: 0 20px 50px rgba(31, 29, 75, 0.22);
        }

        .profile-cover:before {
            content: "";
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255,255,255,0.07);
            right: -100px;
            top: -140px;
        }

        .profile-cover:after {
            content: "";
            position: absolute;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: rgba(123,110,255,0.16);
            right: 180px;
            bottom: -120px;
        }

        .profile-main {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 28px;
        }

        .profile-photo-wrap {
            position: relative;
            flex-shrink: 0;
        }

        .profile-photo {
            width: 145px;
            height: 145px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid rgba(255,255,255,0.95);
            box-shadow: 0 10px 30px rgba(0,0,0,0.22);
            background: #eeeeff;
        }

        .profile-info h1 {
            color: white;
            font-size: 36px;
            margin: 0 0 8px;
            overflow-wrap: anywhere;
        }

        .profile-email {
            color: #dcdcff;
            margin: 0 0 14px;
            font-size: 15px;
            overflow-wrap: anywhere;
        }

        .student-badge {
            display: inline-block;
            padding: 7px 14px;
            background: rgba(255,255,255,0.13);
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 20px;
            font-size: 13px;
            color: #f0efff;
        }

        .upload-area {
            position: relative;
            z-index: 3;
            margin-top: 30px;
        }

        .upload-area input {
            background: white;
            color: #555;
            max-width: 330px;
        }

        .upload-area button {
            margin-top: 10px;
        }

        .upload-message {
            position: relative;
            z-index: 3;
            margin-top: 15px;
            padding: 12px;
            border-radius: 10px;
            background: rgba(255,255,255,0.13);
            color: white;
            overflow-wrap: anywhere;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 22px;
            margin-top: 25px;
        }

        .profile-card {
            background: white;
            padding: 28px;
            border-radius: 20px;
            border: 1px solid #e7e9f2;
            box-shadow: 0 8px 25px rgba(30,35,70,0.07);
        }

        .profile-card h2 {
            margin-top: 0;
            color: #303650;
            font-size: 21px;
        }

        .profile-card p {
            color: #73788d;
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .skill-box {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 20px;
            background: #f8f8fc;
            border-radius: 15px;
            margin-top: 15px;
        }

        .skill-box-icon {
            width: 50px;
            height: 50px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: #eeecff;
            font-size: 23px;
        }

        .skill-box h3 {
            margin: 0 0 5px;
            color: #393d58;
            font-size: 16px;
        }

        .skill-box p {
            margin: 0;
            color: #666b80;
            font-size: 15px;
        }

        .details-list {
            margin-top: 15px;
        }

        .detail {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #edf0f5;
        }

        .detail:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #85899b;
            font-size: 14px;
        }

        .detail-value {
            color: #363a54;
            font-weight: 600;
            text-align: right;
            overflow-wrap: anywhere;
        }

        .about-card {
            margin-top: 22px;
            background: linear-gradient(135deg, #f7f6ff, #f5f8ff);
            padding: 28px;
            border-radius: 20px;
            border: 1px solid #e5e4f5;
        }

        .about-card h2 {
            margin-top: 0;
            color: #4c47a0;
        }

        .about-card p {
            color: #73788d;
            line-height: 1.7;
        }

        .profile-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 25px;
        }

        .action-link {
            display: inline-block;
            padding: 12px 20px;
            border-radius: 10px;
            text-decoration: none;
            background: #5146a8;
            color: white;
            transition: 0.2s;
        }

        .action-link:hover {
            background: #39317f;
            transform: translateY(-2px);
        }

        .secondary-link {
            background: #ecebfa;
            color: #39317f;
        }

        .secondary-link:hover {
            background: #dedbfa;
        }

        @media (max-width: 800px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }

            .profile-main {
                flex-direction: column;
                text-align: center;
            }

            .profile-cover {
                padding: 35px 25px;
            }
        }

        @media (max-width: 500px) {
            .profile-info h1 {
                font-size: 28px;
            }

            .profile-photo {
                width: 120px;
                height: 120px;
            }

            .detail {
                flex-direction: column;
            }

            .detail-value {
                text-align: left;
            }

            .upload-area input {
                max-width: 100%;
                width: 100%;
            }
        }
    </style>
</head>

<body>

<nav>
    <a href="dashboard.php">SkillSwap</a>
    <a href="dashboard.php">Home</a>
    <a href="skills.php">Explore Skills</a>
    <a href="my_requests.php">Requests</a>
    <a href="logout.php">Logout</a>
</nav>

<div class="container">

    <!-- PROFILE HEADER -->

    <div class="profile-cover">

        <div class="profile-main">

            <div class="profile-photo-wrap">

                <?php if ($has_picture) { ?>

                    <img
                        src="<?php echo htmlspecialchars($picture_path); ?>"
                        alt="Profile picture"
                        class="profile-photo"
                    >

                <?php } else { ?>

                    <img
                        src="https://placehold.co/145x145/eeeeff/5146a8?text=Student"
                        alt="Default profile picture"
                        class="profile-photo"
                    >

                <?php } ?>

            </div>

            <div class="profile-info">

                <h1><?php echo $profile_name; ?></h1>

                <p class="profile-email">
                    <?php echo $profile_email; ?>
                </p>

                <span class="student-badge">
                    🎓 SkillSwap Student Member
                </span>

                <?php if (!$is_own_profile) { ?>
                    <p style="color:#dcdcff; margin-top:12px;">
                        You are viewing this student's profile.
                    </p>
                <?php } ?>

            </div>

        </div>

        <?php if ($is_own_profile) { ?>

            <div class="upload-area">

                <form method="POST" enctype="multipart/form-data">

                    <input
                        type="file"
                        name="profile_pic"
                        accept=".jpg,.jpeg,.png,.webp"
                        required
                    >

                    <br>

                    <button type="submit" name="upload_pic">
                        📷 Change Profile Picture
                    </button>

                </form>

                <?php if ($upload_message !== "") { ?>
                    <div class="upload-message">
                        <?php echo htmlspecialchars($upload_message); ?>
                    </div>
                <?php } ?>

                <p style="color:#dcdcff; font-size:13px;">
                    JPG, JPEG, PNG or WEBP · Maximum 5 MB
                </p>

            </div>

        <?php } ?>

    </div>

    <!-- MAIN CONTENT -->

    <div class="profile-grid">

        <div class="profile-card">

            <h2>✨ Skill Profile</h2>

            <p>
                Discover opportunities to share knowledge
                and learn new skills from other students.
            </p>

            <div class="skill-box">

                <div class="skill-box-icon">📚</div>

                <div>
                    <h3>Skills I Can Teach</h3>
                    <p>
                        <?php echo $profile_teach !== '' ? $profile_teach : 'Not specified'; ?>
                    </p>
                </div>

            </div>

            <div class="skill-box">

                <div class="skill-box-icon">🎯</div>

                <div>
                    <h3>Skills I Want to Learn</h3>
                    <p>
                        <?php echo $profile_learn !== '' ? $profile_learn : 'Not specified'; ?>
                    </p>
                </div>

            </div>

        </div>

        <div class="profile-card">

            <h2>👤 Account Details</h2>

            <div class="details-list">

                <div class="detail">
                    <span class="detail-label">Student ID</span>
                    <span class="detail-value">
                        #<?php echo (int)$user['id']; ?>
                    </span>
                </div>

                <div class="detail">
                    <span class="detail-label">Account</span>
                    <span class="detail-value">Active</span>
                </div>

                <div class="detail">
                    <span class="detail-label">Platform</span>
                    <span class="detail-value">SkillSwap</span>
                </div>

                <div class="detail">
                    <span class="detail-label">Role</span>
                    <span class="detail-value">Student</span>
                </div>

            </div>

        </div>

    </div>

    <!-- ABOUT -->

    <div class="about-card">

        <h2>🌟 About SkillSwap</h2>

        <p>
            SkillSwap connects students who want to teach and learn
            from each other. Profiles help students discover skills
            and connect with people who share their learning goals.
        </p>

    </div>

    <!-- ACTIONS -->

    <div class="profile-actions">

        <?php if (!$is_own_profile) { ?>

            <a
                class="action-link"
                href="message.php?user_id=<?php echo (int)$user['id']; ?>"
            >
                💬 Send Message
            </a>

        <?php } ?>

        <a class="action-link secondary-link" href="dashboard.php">
            ← Back to Dashboard
        </a>

        <?php if ($is_own_profile) { ?>

            <a class="action-link secondary-link" href="my_requests.php">
                💬 My Requests
            </a>

        <?php } ?>

    </div>

</div>

</body>
</html>
```