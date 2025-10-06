<?php
session_start();
require_once 'connection.php'; // must create $pdo (PDO)

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$success_message = $error_message = null;

// --- Fetch current user ---
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        throw new Exception("User not found.");
    }
} catch (Exception $e) {
    die("Error fetching user: " . htmlspecialchars($e->getMessage()));
}

// --- Handle form submit ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect + sanitize input
    $first_name      = trim($_POST['first_name'] ?? '');
    $last_name       = trim($_POST['last_name'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $phone_number    = trim($_POST['phone'] ?? '');
    $bio             = trim($_POST['bio'] ?? '');
    $street_address  = trim($_POST['street'] ?? '');
    $apartment_unit  = trim($_POST['apartment'] ?? '');
    $city            = trim($_POST['city'] ?? '');
    $state_province  = trim($_POST['state'] ?? '');
    $zip_postal_code = trim($_POST['zip'] ?? '');
    $date_of_birth   = trim($_POST['dob'] ?? '');
    $gender          = trim($_POST['gender'] ?? '');
    $profile_picture_url   = trim($_POST['profile_picture_url'] ?? '');

    // Convert DOB from MM/DD/YYYY to YYYY-MM-DD if needed
    if (!empty($date_of_birth) && strpos($date_of_birth, '/') !== false) {
        $parts = explode('/', $date_of_birth);
        if (count($parts) === 3) {
            // sanitize numbers
            $mm = str_pad((int)$parts[0], 2, '0', STR_PAD_LEFT);
            $dd = str_pad((int)$parts[1], 2, '0', STR_PAD_LEFT);
            $yy = (int)$parts[2];
            if ($yy < 100) { // two-digit year -> assume 19xx/20xx (optional)
                $yy += ($yy > 30) ? 1900 : 2000;
            }
            $date_of_birth = sprintf('%04d-%02d-%02d', $yy, $mm, $dd);
        }
    }

    // Validate email (basic)
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    }

$user_id = (int) $_SESSION['user_id'];

// Fetch user
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile picture upload
// Profile picture handling (skip GD)
$profile_picture_url = $user['profile_picture_url'] ?? null;
if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
    $fileTmp = $_FILES['profile_pic']['tmp_name'];
    $fileName = uniqid('user_'.$user_id.'_') . '_' . basename($_FILES['profile_pic']['name']);
    $uploadDir = __DIR__ . '/uploads/profile_pics/';

    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $targetPath = $uploadDir . $fileName;
    $dbPath = 'uploads/profile_pics/' . $fileName; // store relative path in DB

    if (move_uploaded_file($fileTmp, $targetPath)) {
        $profile_picture_url = $dbPath;

        // Optionally delete old profile pic
        if (!empty($user['profile_picture_url'])) {
            $old = __DIR__ . '/' . ltrim($user['profile_picture_url'], '/');
            if (is_file($old)) @unlink($old);
        }
    } else {
        $error_message = 'Failed to upload profile picture.';
    }
}


// Display avatar in HTML
$avatarHTML = '';
if (!empty($user['profile_picture_url']) && file_exists(__DIR__.'/'.$user['profile_picture_url'])) {
    $avatarHTML = '<img src="'.htmlspecialchars($user['profile_picture_url']).'" width="120" height="120" style="border-radius:50%;object-fit:cover;">';
} else {
    $avatarHTML = '<div class="avatar-large">'.strtoupper(substr($user['first_name']??'U',0,1)).strtoupper(substr($user['last_name']??'',0,1)).'</div>';
}



    // If no errors, update DB
    if (empty($error_message)) {
        try {
            $sql = "UPDATE users SET
                        first_name = :first_name,
                        last_name = :last_name,
                        email = :email,
                        phone_number = :phone_number,
                        bio = :bio,
                        street_address = :street_address,
                        apartment_unit = :apartment_unit,
                        city = :city,
                        state_province = :state_province,
                        zip_postal_code = :zip_postal_code,
                        date_of_birth = :date_of_birth,
                        gender = :gender,
                        profile_picture_url = :profile_picture_url
                    WHERE user_id = :user_id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':first_name' => $first_name,
                ':last_name'  => $last_name,
                ':email'      => $email,
                ':phone_number' => $phone_number,
                ':bio'        => $bio,
                ':street_address' => $street_address,
                ':apartment_unit' => $apartment_unit,
                ':city'       => $city,
                ':state_province' => $state_province,
                ':zip_postal_code' => $zip_postal_code,
                ':date_of_birth' => $date_of_birth ?: null,
                ':gender'     => $gender,
                ':profile_picture_url' => $profile_picture_url,
                ':user_id'    => $user_id
            ]);

            // re-fetch user to reflect changes
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $success_message = "Profile updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - NextRig</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://kit.fontawesome.com/4a24449835.js" crossorigin="anonymous"></script>
</head>
<body>
<?php include 'header.php'; ?>
<main class="container">
    <nav class="breadcrumbs">
        <a href="#">Home</a> &gt; <span>My Account</span>
    </nav>

    <div class="profile-layout">
        <aside class="profile-sidebar">
            <div class="user-info">
                <?php if (!empty($user['profile_picture_url'])): ?>
                    <img src="<?= htmlspecialchars($user['profile_picture_url']) ?>" class="avatar-large" alt="Profile Picture">
                <?php else: ?>
                    <div class="avatar-large">
                        <?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1)) . strtoupper(substr($user['last_name'] ?? '', 0, 1)) ?>
                    </div>
                <?php endif; ?>
                <h3><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h3>
                <p>Member since <?= date("Y", strtotime($user['member_since'])) ?></p>
            </div>
             <nav class="sidebar-nav">
            <ul>
                <li><a href="profile-settings.php" class="active"><i class="fas fa-user-cog"></i> Profile Settings</a></li>
                <li><a href="orders.php" ><i class="fas fa-box-open"></i> My Orders</a></li>
                <li><a href="my-listings.php"><i class="fas fa-list-ul"></i> My Listings</a></li>
                <li><a href="wishlist.php"><i class="fas fa-heart"></i> Wishlist</a></li>
                <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
                <li><a href="security.php"><i class="fas fa-shield-alt"></i> Security</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
        </aside>

        

        <section class="profile-content">
            <div class="content-header">
                <h2>Profile Settings</h2>
                <span class="secure-note"><i class="fas fa-lock"></i> Your information is secure</span>
            </div>

            <?php if (isset($_GET['updated'])): ?>
                <p style="color: green; font-weight: bold;">✅ Profile updated successfully!</p>
            <?php endif; ?>

            <form class="settings-form" method="post" enctype="multipart/form-data">


                <!-- Personal Information -->
                <div class="form-section">
                     <input type="file" name="images[]" id="imageUpload" accept="image/*" multiple>
                    <h4>Personal Information</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="text" name="dob" value="<?= htmlspecialchars($user['date_of_birth'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender">
                                <option value="Male" <?= ($user['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= ($user['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                <option value="Other" <?= ($user['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="form-section">
                    <h4>Contact Information</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone_number'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- Location -->
                <div class="form-section">
                    <h4>Location</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Street Address</label>
                            <input type="text" name="street" value="<?= htmlspecialchars($user['street_address'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Apartment/Unit</label>
                            <input type="text" name="apartment" value="<?= htmlspecialchars($user['apartment_unit'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>City</label>
                            <input type="text" name="city" value="<?= htmlspecialchars($user['city'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>State/Province</label>
                            <input type="text" name="state" value="<?= htmlspecialchars($user['state_province'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>ZIP/Postal Code</label>
                            <input type="text" name="zip" value="<?= htmlspecialchars($user['zip_postal_code'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- Bio -->
                <div class="form-section">
                    <h4>Additional Information</h4>
                    <div class="form-group">
                        <label>Bio</label>
                        <textarea name="bio"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                </div>
            </form>
        </section>
    </div>
</main>
</body>
</html>
