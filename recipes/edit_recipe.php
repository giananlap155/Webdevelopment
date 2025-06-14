<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if recipe ID is provided
if (!isset($_GET['id'])) {
    header("Location: view_recipes.php");
    exit();
}

$recipe_id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch recipe data
$sql = "SELECT *, 
        REPLACE(REPLACE(ingredients, '\\n', CHAR(10)), '\n', CHAR(10)) as clean_ingredients,
        REPLACE(REPLACE(instructions, '\\n', CHAR(10)), '\n', CHAR(10)) as clean_instructions 
        FROM recipes WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $recipe_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: view_recipes.php");
    exit();
}

$recipe = mysqli_fetch_assoc($result);
// Clean up any remaining line breaks
$recipe['ingredients'] = str_replace(['\n', "\r\n", "\r"], "\n", $recipe['clean_ingredients']);
$recipe['instructions'] = str_replace(['\n', "\r\n", "\r"], "\n", $recipe['clean_instructions']);

// Handle form submission
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
    
    // Handle image upload
    $image_path = $recipe['image_path']; // Keep existing image by default
    if (isset($_FILES['recipe_image']) && $_FILES['recipe_image']['error'] == 0) {
        $upload_dir = '../uploads/recipes/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['recipe_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
        
        if (in_array($file_extension, $allowed_extensions)) {
            // Delete old image if it exists
            if (!empty($recipe['image_path']) && file_exists('../' . $recipe['image_path'])) {
                unlink('../' . $recipe['image_path']);
            }
            
            $unique_filename = uniqid() . '.' . $file_extension;
            $target_file = $upload_dir . $unique_filename;
            
            if (move_uploaded_file($_FILES['recipe_image']['tmp_name'], $target_file)) {
                $image_path = 'uploads/recipes/' . $unique_filename;
            }
        }
    }

    $sql = "UPDATE recipes SET title = ?, category = ?, dish_type = ?, description = ?, ingredients = ?, instructions = ?, image_path = ?, cook_time = ?, servings = ? WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssssssssii", $title, $category, $dish_type, $description, $ingredients, $instructions, $image_path, $cook_time, $servings, $recipe_id, $_SESSION['user_id']);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "Recipe updated successfully!";
        header("Location: view_recipes.php");
        exit();
    } else {
        $error_message = "Error updating recipe. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Recipe - Filipino Delicacy Recipes</title>
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
        }

        .navbar {
            background-color: rgba(0, 0, 0, 0.8);
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
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .nav-links a:hover {
            background-color: #ffd700;
            color: #333;
            text-decoration: none;
        }

        .nav-links a.logout {
            background-color: #dc3545;
        }

        .nav-links a.logout:hover {
            background-color: #c82333;
            color: white;
        }

        .main-content {
            margin-top: 80px;
            padding: 20px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .recipe-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .section-title {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2em;
        }

        .recipe-form {
            max-width: 800px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: bold;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        .form-group textarea {
            height: 150px;
            resize: vertical;
        }

        .current-image {
            margin: 10px 0;
            max-width: 300px;
            border-radius: 8px;
        }

        .submit-btn {
            background: #ffd700;
            color: #333;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            width: 100%;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background: #ffed4a;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="brand">Filipino Delicacies</a>
            <div class="nav-links">
                <a href="../index.php">Home</a>
                <a href="view_recipes.php">Recipes</a>
                <a href="../logout.php" class="logout">Logout</a>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="recipe-section">
            <h2 class="section-title">Edit Recipe</h2>
            <?php if (isset($error_message)): ?>
                <div class="message error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form class="recipe-form" method="POST" action="edit_recipe.php?id=<?php echo $recipe_id; ?>" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Recipe Title</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($recipe['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" required class="form-control">
                        <option value="">Select Category</option>
                        <option value="Main Dish" <?php echo $recipe['category'] === 'Main Dish' ? 'selected' : ''; ?>>Main Dish</option>
                        <option value="Appetizer" <?php echo $recipe['category'] === 'Appetizer' ? 'selected' : ''; ?>>Appetizer</option>
                        <option value="Dessert" <?php echo $recipe['category'] === 'Dessert' ? 'selected' : ''; ?>>Dessert</option>
                        <option value="Snack" <?php echo $recipe['category'] === 'Snack' ? 'selected' : ''; ?>>Snack</option>
                        <option value="Beverage" <?php echo $recipe['category'] === 'Beverage' ? 'selected' : ''; ?>>Beverage</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="dish_type">Dish Type</label>
                    <select id="dish_type" name="dish_type" required class="form-control">
                        <option value="">Select Dish Type</option>
                        <option value="Soup" <?php echo $recipe['dish_type'] === 'Soup' ? 'selected' : ''; ?>>Soup</option>
                        <option value="Grilled" <?php echo $recipe['dish_type'] === 'Grilled' ? 'selected' : ''; ?>>Grilled</option>
                        <option value="Fried" <?php echo $recipe['dish_type'] === 'Fried' ? 'selected' : ''; ?>>Fried</option>
                        <option value="Stewed" <?php echo $recipe['dish_type'] === 'Stewed' ? 'selected' : ''; ?>>Stewed</option>
                        <option value="Steamed" <?php echo $recipe['dish_type'] === 'Steamed' ? 'selected' : ''; ?>>Steamed</option>
                        <option value="Baked" <?php echo $recipe['dish_type'] === 'Baked' ? 'selected' : ''; ?>>Baked</option>
                        <option value="Raw" <?php echo $recipe['dish_type'] === 'Raw' ? 'selected' : ''; ?>>Raw</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="cook_time">Cook Time (e.g., 45 mins, 1 hour)</label>
                    <input type="text" id="cook_time" name="cook_time" value="<?php echo htmlspecialchars($recipe['cook_time'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="servings">Servings (e.g., 4-6, 8)</label>
                    <input type="text" id="servings" name="servings" value="<?php echo htmlspecialchars($recipe['servings'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Short Description</label>
                    <textarea id="description" name="description" required><?php echo htmlspecialchars($recipe['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="ingredients">Ingredients</label>
                    <textarea id="ingredients" name="ingredients" required 
                        style="white-space: pre-wrap; font-family: inherit;"
                        placeholder="Enter ingredients, one per line"><?php 
                        $clean_ingredients = preg_replace('/\\\\n|\r\n|\r|\n/', "\n", $recipe['ingredients']);
                        echo htmlspecialchars($clean_ingredients); 
                    ?></textarea>
                </div>

                <div class="form-group">
                    <label for="instructions">Cooking Instructions</label>
                    <textarea id="instructions" name="instructions" required 
                        style="white-space: pre-wrap; font-family: inherit;"
                        placeholder="Enter step-by-step instructions"><?php 
                        $clean_instructions = preg_replace('/\\\\n|\r\n|\r|\n/', "\n", $recipe['instructions']);
                        echo htmlspecialchars($clean_instructions); 
                    ?></textarea>
                </div>

                <div class="form-group">
                    <label for="recipe_image">Recipe Image</label>
                    <?php if (!empty($recipe['image_path'])): ?>
                        <p>Current image:</p>
                        <img src="../<?php echo htmlspecialchars($recipe['image_path']); ?>" alt="Current recipe image" class="current-image">
                    <?php endif; ?>
                    <input type="file" id="recipe_image" name="recipe_image" accept="image/*">
                    <div class="image-preview" id="imagePreview" style="margin-top: 10px; display: none;">
                        <img id="preview" style="max-width: 300px; max-height: 300px; border-radius: 8px;">
                    </div>
                </div>

                <button type="submit" class="submit-btn">Update Recipe</button>
            </form>
        </div>
    </div>

    <script>
        // Image preview functionality
        document.getElementById('recipe_image').onchange = function(evt) {
            const preview = document.getElementById('preview');
            const imagePreview = document.getElementById('imagePreview');
            const file = evt.target.files[0];
            
            if (file) {
                preview.src = URL.createObjectURL(file);
                imagePreview.style.display = 'block';
            }
        };
    </script>
</body>
</html> 