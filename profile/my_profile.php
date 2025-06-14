<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Handle profile picture upload
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['profile_picture']['name'];
    $filetype = pathinfo($filename, PATHINFO_EXTENSION);
    
    if (in_array(strtolower($filetype), $allowed)) {
        $temp_name = $_FILES['profile_picture']['tmp_name'];
        $new_filename = 'profile_' . $_SESSION['user_id'] . '.' . $filetype;
        $upload_path = '../uploads/profiles/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_path)) {
            if (!mkdir($upload_path, 0777, true)) {
                $upload_error = "Failed to create upload directory";
            }
        }
        
        if (!isset($upload_error) && is_writable($upload_path)) {
            if (move_uploaded_file($temp_name, $upload_path . $new_filename)) {
                $profile_picture = 'uploads/profiles/' . $new_filename;
                $update_sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "si", $profile_picture, $_SESSION['user_id']);
                if (!mysqli_stmt_execute($update_stmt)) {
                    $upload_error = "Failed to update database";
                }
            } else {
                $upload_error = "Failed to upload file";
            }
        } else {
            $upload_error = "Upload directory is not writable";
        }
    } else {
        $upload_error = "Invalid file type. Allowed types: JPG, JPEG, PNG, GIF";
    }
}

// Fetch user information
$user_id = $_SESSION['user_id'];
$user_sql = "SELECT * FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

// Fetch user's recipes
$recipe_sql = "SELECT * FROM recipes WHERE user_id = ? ORDER BY created_at DESC";
$recipe_stmt = mysqli_prepare($conn, $recipe_sql);
mysqli_stmt_bind_param($recipe_stmt, "i", $user_id);
mysqli_stmt_execute($recipe_stmt);
$recipe_result = mysqli_stmt_get_result($recipe_stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Filipino Delicacy Recipes</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)),
                        url('../images/filipino-food-bg.jpg');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            font-family: 'Arial', sans-serif;
        }

        .navbar {
            background-color: rgba(0, 0, 0, 0.9);
            padding: 15px 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .brand {
            color: #ffd700;
            font-size: 1.5em;
            text-decoration: none;
            font-weight: bold;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 25px;
            transition: all 0.3s ease;
            font-weight: 500;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        .nav-links a:before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background-color: #ffd700;
            transition: width 0.3s ease;
        }

        .nav-links a:hover:before {
            width: 80%;
        }

        .nav-links a:hover {
            color: #ffd700;
            background-color: transparent;
            transform: translateY(-2px);
        }

        .nav-links a.logout {
            background-color: #dc3545;
            color: white;
            padding: 10px 24px;
            border-radius: 25px;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .nav-links a.logout:before {
            display: none;
        }

        .nav-links a.logout:hover {
            background-color: #c82333;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0,0,0,0.2);
        }

        .main-content {
            margin-top: 80px;
            padding: 20px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
            margin-bottom: 70px;
        }

        .profile-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .profile-header {
            display: flex;
            align-items: flex-start;
            gap: 40px;
            margin-bottom: 30px;
            padding: 20px;
        }

        .profile-picture-container {
            flex-shrink: 0;
            width: 180px;
            text-align: center;
        }

        .profile-picture {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 3px solid #ffd700;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .profile-username {
            color: #666;
            font-size: 1.1em;
            margin-top: 10px;
            text-align: center;
        }

        .welcome-container {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .welcome-container h1 {
            color: #333;
            font-size: 2.4em;
            margin: 0;
            text-align: center;
        }

        .recipes-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .recipe-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .recipe-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .recipe-card:hover {
            transform: translateY(-10px);
        }

        .recipe-content {
            padding: 20px;
        }

        .recipe-content h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.5em;
        }

        .recipe-content p {
            color: #666;
            margin-bottom: 15px;
        }

        .btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.3s;
            font-size: 14px;
            margin-right: 10px;
        }

        .view-btn {
            background: #ffd700;
            color: #333;
        }

        .edit-btn {
            background: #28a745;
            color: white;
        }

        .delete-btn {
            background: #dc3545;
            color: white;
        }

        .add-recipe-btn {
            background: #ffd700;
            color: #333;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s;
            display: inline-block;
            margin-bottom: 20px;
        }

        .btn:hover {
            opacity: 0.9;
            text-decoration: none;
        }

        .recipe-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .no-recipes {
            text-align: center;
            color: #666;
            margin: 30px 0;
        }

        .profile-picture-upload {
            display: none;
        }

        .upload-btn {
            background: #ffd700;
            color: #333;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            display: inline-block;
            font-size: 14px;
            transition: all 0.3s ease;
            border: none;
            font-weight: bold;
            text-decoration: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .upload-btn:hover {
            background: #ffed4a;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
        }

        .upload-btn i {
            margin-right: 8px;
        }

        /* Footer styles */
        .footer {
            background-color: rgba(0, 0, 0, 0.9);
            padding: 15px 0;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .footer-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .footer-links a {
            color: #fff;
            text-decoration: none;
            font-size: 0.9em;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: #ffd700;
        }

        .footer-text {
            color: #666;
            font-size: 0.9em;
        }

        /* Responsive design */
        @media screen and (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                padding: 10px;
            }

            .brand {
                margin-bottom: 10px;
            }

            .nav-links {
                flex-direction: column;
                width: 100%;
                gap: 10px;
                text-align: center;
            }

            .nav-links a {
                width: 100%;
                padding: 8px 0;
            }

            .main-content {
                margin-top: 140px;
                padding: 15px;
            }

            .profile-header {
                flex-direction: column;
                align-items: center;
                gap: 20px;
                padding: 15px;
            }

            .profile-picture-container {
                width: 100%;
                max-width: 250px;
            }

            .welcome-container h1 {
                font-size: 2em;
                text-align: center;
            }

            .recipe-grid {
                grid-template-columns: 1fr;
            }

            .recipe-card {
                margin-bottom: 20px;
            }

            .recipe-actions {
                flex-wrap: wrap;
                justify-content: center;
            }

            .btn {
                width: 100%;
                margin: 5px 0;
                text-align: center;
            }
        }

        /* Tablet-specific adjustments */
        @media screen and (min-width: 769px) and (max-width: 1024px) {
            .recipe-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .main-content {
                padding: 20px;
            }
        }

        /* Ensure images are responsive */
        img {
            max-width: 100%;
            height: auto;
        }

        /* Make forms responsive */
        form {
            max-width: 100%;
        }

        input, select, textarea {
            max-width: 100%;
            box-sizing: border-box;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="brand">Filipino Delicacies</a>
            <div class="nav-links">
                <a href="../index.php">Home</a>
                <a href="../recipes/view_recipes.php">All Recipes</a>
                <a href="../profile/my_profile.php">My Profile</a>
                <a href="../index.php#about-section">About Us</a>
                <a href="../logout.php" class="logout">Logout</a>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="profile-section">
            <div class="profile-header">
                <div class="profile-picture-container">
                    <img src="<?php echo !empty($user['profile_picture']) ? '../' . $user['profile_picture'] : '../images/default-profile.png'; ?>" 
                         alt="Profile Picture" 
                         class="profile-picture" 
                         id="profilePreview">
                    <form method="POST" enctype="multipart/form-data" id="profilePictureForm">
                        <input type="file" name="profile_picture" id="profilePictureInput" class="profile-picture-upload" accept="image/*">
                        <button type="button" class="upload-btn" onclick="document.getElementById('profilePictureInput').click()">
                            <i class="fas fa-camera"></i> Change Profile Picture
                        </button>
                    </form>
                    <p class="profile-username">Username: <?php echo htmlspecialchars($user['username']); ?></p>
                </div>
                <div class="welcome-container">
                    <h1>Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h1>
                </div>
            </div>
        </div>

        <div class="recipes-section">
            <h2>My Recipes</h2>
            <a href="../recipes/add_recipe.php" class="add-recipe-btn">Add New Recipe</a>

            <div class="recipe-grid">
                <?php while ($recipe = mysqli_fetch_assoc($recipe_result)): ?>
                    <div class="recipe-card">
                        <?php if (!empty($recipe['image_path'])): ?>
                            <img src="../<?php echo htmlspecialchars($recipe['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($recipe['title']); ?>"
                                 style="width: 100%; height: 200px; object-fit: cover;">
                        <?php endif; ?>
                        <div class="recipe-content">
                            <h3><?php echo htmlspecialchars($recipe['title']); ?></h3>
                            <p><?php echo substr(htmlspecialchars($recipe['description']), 0, 100) . '...'; ?></p>
                            <div>
                                <a href="../recipes/view_recipe.php?id=<?php echo $recipe['id']; ?>" class="btn view-btn">View</a>
                                <a href="../recipes/edit_recipe.php?id=<?php echo $recipe['id']; ?>" class="btn edit-btn">Edit</a>
                                <a href="../recipes/delete_recipe.php?id=<?php echo $recipe['id']; ?>" 
                                   class="btn delete-btn"
                                   onclick="return confirm('Are you sure you want to delete this recipe?');">Delete</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

        <div class="social-container">        <div class="social-icons">            <a href="https://facebook.com" target="_blank" title="Facebook">                <i class="fab fa-facebook-f"></i>            </a>            <a href="https://youtube.com" target="_blank" title="YouTube">                <i class="fab fa-youtube"></i>            </a>            <a href="https://twitter.com" target="_blank" title="Twitter">                <i class="fab fa-twitter"></i>            </a>            <a href="https://instagram.com" target="_blank" title="Instagram">                <i class="fab fa-instagram"></i>            </a>        </div>    </div>    <footer class="footer">        <div class="footer-text">            All rights reserved.        </div>    </footer>

    <script>
        document.getElementById('profilePictureInput').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // Check file size (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size too large. Maximum size is 5MB.');
                    this.value = '';
                    return;
                }

                // Check file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Invalid file type. Please upload a JPG, PNG, or GIF image.');
                    this.value = '';
                    return;
                }

                // Show preview and submit form
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                };
                reader.readAsDataURL(file);
                document.getElementById('profilePictureForm').submit();
            }
        });
    </script>

    <?php if (isset($upload_error)): ?>
    <script>
        alert('<?php echo addslashes($upload_error); ?>');
    </script>
    <?php endif; ?>
</body>
</html> 