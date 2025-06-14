<?php
session_start();
require_once '../config/database.php';

// Get filter parameters
$category = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : '';
$dish_type = isset($_GET['dish_type']) ? mysqli_real_escape_string($conn, $_GET['dish_type']) : '';
$search_term = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Build the SQL query with filters
$sql = "SELECT r.*, r.user_id, u.username as author FROM recipes r JOIN users u ON r.user_id = u.id";
$where_conditions = [];
$params = [];
$param_types = "";

if (!empty($search_term)) {
    $where_conditions[] = "(r.title LIKE ? OR r.description LIKE ? OR r.ingredients LIKE ?)";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $param_types .= "sss";
}

if (!empty($category)) {
    $where_conditions[] = "r.category = ?";
    $params[] = $category;
    $param_types .= "s";
}

if (!empty($dish_type)) {
    $where_conditions[] = "r.dish_type = ?";
    $params[] = $dish_type;
    $param_types .= "s";
}

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$sql .= " ORDER BY r.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Recipes - Filipino Delicacy Recipes</title>
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

        .nav-links a.login-btn {
            background-color: #ffd700;
            color: #333;
            padding: 10px 24px;
            border-radius: 25px;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .nav-links a.login-btn:before {
            display: none;
        }

        .nav-links a.login-btn:hover {
            background-color: #ffed4a;
            color: #333;
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0,0,0,0.2);
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
            margin-top: 110px;
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

        .recipe-author {
            color: #888;
            font-size: 0.9em;
            margin-bottom: 15px;
        }

        .view-btn {
            display: inline-block;
            background: #ffd700;
            color: #333;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .view-btn:hover {
            background: #ffed4a;
            text-decoration: none;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .edit-btn {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .edit-btn:hover {
            background: #218838;
            text-decoration: none;
            color: white;
        }

        .delete-btn {
            display: inline-block;
            background: #dc3545;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .delete-btn:hover {
            background: #c82333;
            text-decoration: none;
            color: white;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .search-navbar {
            background: rgba(0, 0, 0, 0.8);
            padding: 15px 0;
            position: fixed;
            top: 65px;
            left: 0;
            right: 0;
            z-index: 999;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .search-container {
            max-width: 1000px;
            margin: 0 auto;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            padding: 0 20px;
        }

        .search-group {
            display: flex;
            gap: 15px;
            align-items: center;
            max-width: 800px;
            width: 100%;
        }

        .search-select {
            padding: 10px 15px;
            border: 2px solid rgba(255, 215, 0, 0.3);
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            font-size: 0.9em;
            min-width: 120px;
            cursor: pointer;
            transition: all 0.3s ease;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url('data:image/svg+xml;charset=US-ASCII,<svg width="14" height="8" viewBox="0 0 14 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 1L7 7L13 1" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>');
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 35px;
        }

        .search-select:focus {
            border-color: #ffd700;
            outline: none;
            box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.2);
        }

        .search-select option {
            background: #333;
            color: #fff;
            padding: 10px;
        }

        .search-input {
            padding: 10px 20px;
            border: 2px solid rgba(255, 215, 0, 0.3);
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            font-size: 0.9em;
            flex-grow: 1;
            transition: all 0.3s ease;
        }

        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .search-input:focus {
            border-color: #ffd700;
            outline: none;
            box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.2);
        }

        .search-btn {
            background: #ffd700;
            color: #333;
            padding: 10px 25px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9em;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            white-space: nowrap;
        }

        .search-btn:hover {
            background: #ffed4a;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }

        @media screen and (max-width: 768px) {
            .search-container {
                padding: 0 15px;
            }

            .search-group {
                flex-direction: column;
                gap: 10px;
            }

            .search-select,
            .search-input,
            .search-btn {
                width: 100%;
                min-width: unset;
            }

            .search-navbar {
                position: sticky;
                top: 60px;
                padding: 10px 0;
            }
        }

        @media screen and (max-width: 480px) {
            .search-select,
            .search-input,
            .search-btn {
                font-size: 0.85em;
                padding: 8px 15px;
            }
        }
    </style>
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
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="../logout.php" class="logout">Logout</a>
                <?php else: ?>
                    <a href="../login.php" class="login-btn">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="search-navbar">
        <div class="search-container">
            <div class="search-group">
                <select class="search-select" id="category">
                    <option value="">All Categories</option>
                    <option value="Main Dish" <?php echo $category === 'Main Dish' ? 'selected' : ''; ?>>Main Dish</option>
                    <option value="Appetizer" <?php echo $category === 'Appetizer' ? 'selected' : ''; ?>>Appetizer</option>
                    <option value="Dessert" <?php echo $category === 'Dessert' ? 'selected' : ''; ?>>Dessert</option>
                    <option value="Snack" <?php echo $category === 'Snack' ? 'selected' : ''; ?>>Snack</option>
                    <option value="Beverage" <?php echo $category === 'Beverage' ? 'selected' : ''; ?>>Beverage</option>
                </select>
                <select class="search-select" id="dish_type">
                    <option value="">All Dish Types</option>
                    <option value="Soup" <?php echo $dish_type === 'Soup' ? 'selected' : ''; ?>>Soup</option>
                    <option value="Grilled" <?php echo $dish_type === 'Grilled' ? 'selected' : ''; ?>>Grilled</option>
                    <option value="Fried" <?php echo $dish_type === 'Fried' ? 'selected' : ''; ?>>Fried</option>
                    <option value="Stewed" <?php echo $dish_type === 'Stewed' ? 'selected' : ''; ?>>Stewed</option>
                    <option value="Steamed" <?php echo $dish_type === 'Steamed' ? 'selected' : ''; ?>>Steamed</option>
                    <option value="Baked" <?php echo $dish_type === 'Baked' ? 'selected' : ''; ?>>Baked</option>
                    <option value="Raw" <?php echo $dish_type === 'Raw' ? 'selected' : ''; ?>>Raw</option>
                </select>
                <input type="text" class="search-input" id="search" placeholder="Search recipes..." value="<?php echo htmlspecialchars($search_term); ?>">
                <button class="search-btn" onclick="filterRecipes()">Search</button>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="recipe-section">
            <h1 class="section-title">All Recipes</h1>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <div style="text-align: center; margin-bottom: 30px;">
                    <a href="add_recipe.php" class="add-recipe-btn" style="display: inline-block; background: #ffd700; color: #333; padding: 12px 25px; border-radius: 25px; text-decoration: none; font-weight: bold; transition: all 0.3s ease;">Share Your Recipe</a>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="message success"><?php echo $_SESSION['success_message']; ?></div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <div class="recipe-grid">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($recipe = mysqli_fetch_assoc($result)): ?>
                        <div class="recipe-card">
                            <?php if (!empty($recipe['image_path'])): ?>
                                <div class="recipe-image" style="height: 200px; overflow: hidden;">
                                    <img src="../<?php echo htmlspecialchars($recipe['image_path']); ?>" alt="<?php echo htmlspecialchars($recipe['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                            <?php endif; ?>
                            <div class="recipe-content">
                                <h3><?php echo htmlspecialchars($recipe['title']); ?></h3>
                                <p class="recipe-author">By: <?php echo htmlspecialchars($recipe['author']); ?></p>
                                <p><?php echo htmlspecialchars(substr($recipe['description'], 0, 150)) . '...'; ?></p>
                                <a href="view_recipe.php?id=<?php echo $recipe['id']; ?>" class="view-btn">View Recipe</a>
                                <?php 
                                // Check if user is logged in and if the recipe belongs to them
                                if (isset($_SESSION['user_id']) && isset($recipe['user_id']) && $_SESSION['user_id'] == $recipe['user_id']): 
                                ?>
                                    <div class="action-buttons">
                                        <a href="edit_recipe.php?id=<?php echo $recipe['id']; ?>" class="edit-btn">Edit</a>
                                        <a href="delete_recipe.php?id=<?php echo $recipe['id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this recipe?');">Delete</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No recipes have been added yet. Be the first to share a recipe!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function filterRecipes() {
            const search = document.getElementById('search').value;
            const category = document.getElementById('category').value;
            const dish_type = document.getElementById('dish_type').value;
            
            // Build the URL with filters
            let url = 'view_recipes.php';
            const params = new URLSearchParams();
            
            if (search) {
                params.append('search', search);
            }
            if (category) {
                params.append('category', category);
            }
            if (dish_type) {
                params.append('dish_type', dish_type);
            }
            
            if (params.toString()) {
                url += '?' + params.toString();
            }
            
            window.location.href = url;
        }

        // Add enter key support for search input
        document.getElementById('search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                filterRecipes();
            }
        });
    </script>
</body>
</html> 