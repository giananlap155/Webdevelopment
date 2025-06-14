<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Please login to add a recipe.";
    header("Location: ../login.php");
    exit();
}

// Handle recipe submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $dish_type = mysqli_real_escape_string($conn, $_POST['dish_type']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    // Clean and normalize line endings for ingredients
    $ingredients = trim($_POST['ingredients']);
    $ingredients = preg_replace('/\\\\n|\r\n|\r|\n/', "\n", $ingredients);
    $ingredients = implode("\n", array_filter(array_map('trim', explode("\n", $ingredients))));
    $ingredients = mysqli_real_escape_string($conn, $ingredients);
    
    // Clean and normalize line endings for instructions
    $instructions = trim($_POST['instructions']);
    $instructions = preg_replace('/\\\\n|\r\n|\r|\n/', "\n", $instructions);
    $instructions = implode("\n", array_filter(array_map('trim', explode("\n", $instructions))));
    $instructions = mysqli_real_escape_string($conn, $instructions);
    
    $cook_time = mysqli_real_escape_string($conn, $_POST['cook_time']);
    $servings = mysqli_real_escape_string($conn, $_POST['servings']);
    $user_id = $_SESSION['user_id'];

    // Handle image upload (optional)
    $image_path = '';
    if (isset($_FILES['recipe_image']) && $_FILES['recipe_image']['error'] == 0 && $_FILES['recipe_image']['size'] > 0) {
        $upload_dir = '../uploads/recipes/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['recipe_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
        
        if (in_array($file_extension, $allowed_extensions)) {
            $unique_filename = uniqid() . '.' . $file_extension;
            $target_file = $upload_dir . $unique_filename;
            
            if (move_uploaded_file($_FILES['recipe_image']['tmp_name'], $target_file)) {
                $image_path = 'uploads/recipes/' . $unique_filename;
            }
        }
    }

    $sql = "INSERT INTO recipes (user_id, title, category, dish_type, description, ingredients, instructions, image_path, cook_time, servings) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isssssssss", $user_id, $title, $category, $dish_type, $description, $ingredients, $instructions, $image_path, $cook_time, $servings);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "Recipe added successfully!";
        header("Location: view_recipes.php");
        exit();
    } else {
        $error_message = "Error adding recipe: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Recipe - Filipino Delicacy Recipes</title>
    <link rel="stylesheet" href="../css/style.css">
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
            min-height: 100vh;
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
            min-height: calc(100vh - 80px);
            display: flex;
            flex-direction: column;
        }

        .recipe-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            flex-grow: 1;
        }

        .section-title {
            color: #333;
            text-align: center;
            margin-bottom: 40px;
            font-size: 2.2em;
            font-weight: bold;
            position: relative;
            padding-bottom: 15px;
        }

        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: #ffd700;
            border-radius: 2px;
        }

        .recipe-form {
            max-width: 800px;
            margin: 0 auto;
            display: grid;
            gap: 25px;
        }

        .form-group {
            margin-bottom: 0;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 1.1em;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: #fff;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #ffd700;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.2);
            outline: none;
        }

        .form-group textarea {
            height: 180px;
            resize: vertical;
            line-height: 1.5;
            white-space: pre-wrap;
            font-family: inherit;
        }

        .form-group textarea#ingredients,
        .form-group textarea#instructions {
            font-size: 1em;
            padding: 15px;
            background-color: #fff;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .form-group textarea#ingredients:focus,
        .form-group textarea#instructions:focus {
            border-color: #ffd700;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.2);
            outline: none;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .image-preview {
            margin-top: 10px;
            text-align: center;
            display: none;
        }

        .image-preview img {
            max-width: 300px;
            max-height: 300px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-input-wrapper input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .file-input-button {
            display: block;
            padding: 12px 15px;
            background: #f8f9fa;
            border: 2px dashed #ddd;
            border-radius: 8px;
            text-align: center;
            color: #666;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-input-button:hover {
            border-color: #ffd700;
            color: #333;
        }

        .submit-btn {
            background: #ffd700;
            color: #333;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: bold;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .submit-btn:hover {
            background: #ffed4a;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .message {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 8px;
            text-align: center;
            font-weight: 500;
            animation: slideDown 0.4s ease;
        }

        .error {
            background: #fff3f3;
            color: #dc3545;
            border: 1px solid #ffd7d9;
        }

        .success {
            background: #f0fff4;
            color: #28a745;
            border: 1px solid #c3e6cb;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .recipe-section {
                padding: 25px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .section-title {
                font-size: 1.8em;
            }

            .form-group label {
                font-size: 1em;
            }

            .submit-btn {
                padding: 12px 25px;
                font-size: 1em;
            }
        }

        /* Loading indicator for image upload */
        .loading {
            display: none;
            text-align: center;
            margin-top: 10px;
        }

        .loading::after {
            content: '';
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #ffd700;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Image preview functionality
            const recipeImage = document.getElementById('recipe_image');
            const loading = document.getElementById('imageLoading');
            
            if (recipeImage) {
                recipeImage.onchange = function(evt) {
                    const preview = document.getElementById('preview');
                    const imagePreview = document.getElementById('imagePreview');
                    const file = evt.target.files[0];
                    
                    if (file) {
                        loading.style.display = 'block';
                        imagePreview.style.display = 'none';
                        
                        const reader = new FileReader();
                        reader.onload = function() {
                            preview.src = reader.result;
                            loading.style.display = 'none';
                        imagePreview.style.display = 'block';
                        }
                        reader.readAsDataURL(file);
                    }
                };
            }

            // Form validation with better user feedback
            const form = document.querySelector('.recipe-form');
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.style.borderColor = '#dc3545';
                        
                        // Add error message
                        let errorMsg = field.parentElement.querySelector('.error-message');
                        if (!errorMsg) {
                            errorMsg = document.createElement('div');
                            errorMsg.className = 'error-message';
                            errorMsg.style.color = '#dc3545';
                            errorMsg.style.fontSize = '0.85em';
                            errorMsg.style.marginTop = '5px';
                            field.parentElement.appendChild(errorMsg);
                        }
                        errorMsg.textContent = 'This field is required';
                    } else {
                        field.style.borderColor = '#e1e1e1';
                        const errorMsg = field.parentElement.querySelector('.error-message');
                        if (errorMsg) {
                            errorMsg.remove();
                        }
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    window.scrollTo({
                        top: form.offsetTop - 100,
                        behavior: 'smooth'
                    });
                }
            });

            // Clear error styling on input
            form.querySelectorAll('input, select, textarea').forEach(field => {
                field.addEventListener('input', function() {
                    this.style.borderColor = '#e1e1e1';
                    const errorMsg = this.parentElement.querySelector('.error-message');
                    if (errorMsg) {
                        errorMsg.remove();
                    }
                });
            });

            // Handle About Us link smooth scrolling
            const aboutUsLink = document.querySelector('a[href="../index.php#about-section"]');
            if (aboutUsLink) {
                aboutUsLink.addEventListener('click', function(e) {
                    window.location.href = '../index.php#about-section';
                });
            }
        });
    </script>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="brand">Filipino Delicacies</a>
            <div class="nav-links">
                <a href="../index.php">Home</a>
                <a href="view_recipes.php">All Recipes</a>
                <a href="../profile/my_profile.php">My Profile</a>
                <a href="../index.php#about-section">About Us</a>
                <a href="../logout.php" class="logout">Logout</a>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="recipe-section">
            <h1 class="section-title">Share Your Recipe</h1>
            <?php if (isset($error_message)): ?>
                <div class="message error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form class="recipe-form" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Recipe Title*</label>
                    <input type="text" id="title" name="title" required placeholder="Enter the name of your recipe">
                </div>

                <div class="form-row">
                <div class="form-group">
                        <label for="category">Category*</label>
                    <select id="category" name="category" required>
                            <option value="">Select a category</option>
                        <option value="Main Dish">Main Dish</option>
                        <option value="Appetizer">Appetizer</option>
                        <option value="Dessert">Dessert</option>
                        <option value="Snack">Snack</option>
                        <option value="Beverage">Beverage</option>
                    </select>
                </div>

                <div class="form-group">
                        <label for="dish_type">Dish Type*</label>
                    <select id="dish_type" name="dish_type" required>
                            <option value="">Select a dish type</option>
                        <option value="Soup">Soup</option>
                        <option value="Grilled">Grilled</option>
                        <option value="Fried">Fried</option>
                        <option value="Stewed">Stewed</option>
                        <option value="Steamed">Steamed</option>
                        <option value="Baked">Baked</option>
                        <option value="Raw">Raw</option>
                    </select>
                </div>
                </div>

                <div class="form-group">
                    <label for="description">Description*</label>
                    <textarea id="description" name="description" required placeholder="Tell us about your recipe..."></textarea>
                </div>

                <div class="form-group">
                    <label for="ingredients">Ingredients*</label>
                    <textarea id="ingredients" name="ingredients" required 
                        style="white-space: pre-wrap; font-family: inherit;"
                        placeholder="List your ingredients, one per line:&#10;Example:&#10;1 kg pork belly&#10;1/2 cup soy sauce&#10;1/3 cup vinegar"></textarea>
                </div>

                <div class="form-group">
                    <label for="instructions">Cooking Instructions*</label>
                    <textarea id="instructions" name="instructions" required 
                        style="white-space: pre-wrap; font-family: inherit;"
                        placeholder="Explain the cooking steps, one per line:&#10;Example:&#10;1. Marinate meat for 30 minutes&#10;2. Heat oil in a pot&#10;3. Sauté the marinated meat"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="cook_time">Cooking Time*</label>
                        <input type="text" id="cook_time" name="cook_time" required placeholder="input cooking time...">
                    </div>

                    <div class="form-group">
                        <label for="servings">Number of Servings*</label>
                        <input type="text" id="servings" name="servings" required placeholder="input number of serving...">
                    </div>
                </div>

                <div class="form-group">
                    <label for="recipe_image">Recipe Image</label>
                    <div class="file-input-wrapper">
                        <div class="file-input-button">
                            <i class="fas fa-cloud-upload-alt"></i> Choose an image
                        </div>
                    <input type="file" id="recipe_image" name="recipe_image" accept="image/*">
                    </div>
                    <div class="loading" id="imageLoading"></div>
                    <div class="image-preview" id="imagePreview">
                        <img id="preview" src="#" alt="Recipe preview">
                    </div>
                </div>

                <button type="submit" class="submit-btn">Share Recipe</button>
            </form>
        </div>
    </div>

    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</body>
</html> 