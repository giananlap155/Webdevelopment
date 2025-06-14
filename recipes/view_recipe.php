<?php
session_start();
require_once '../config/database.php';

// Check if recipe ID is provided
if (!isset($_GET['id'])) {
    header("Location: ../index.php");
    exit();
}

$recipe_id = $_GET['id'];

// Handle rating submission
if (isset($_POST['rating']) && isset($_SESSION['user_id'])) {
    $rating = (int)$_POST['rating'];
    if ($rating >= 1 && $rating <= 5) {
        $stmt = mysqli_prepare($conn, "INSERT INTO recipe_ratings (recipe_id, user_id, rating) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE rating = ?");
        mysqli_stmt_bind_param($stmt, "iiii", $recipe_id, $_SESSION['user_id'], $rating, $rating);
        mysqli_stmt_execute($stmt);
    }
}

// Fetch recipe with author information and average rating
$sql = "SELECT r.*, 
        REPLACE(REPLACE(r.ingredients, '\\n', CHAR(10)), '\n', CHAR(10)) as clean_ingredients,
        REPLACE(REPLACE(r.instructions, '\\n', CHAR(10)), '\n', CHAR(10)) as clean_instructions,
        u.username as author, 
        COALESCE(AVG(rr.rating), 0) as avg_rating,
        COUNT(rr.id) as rating_count,
        (SELECT rating FROM recipe_ratings WHERE recipe_id = r.id AND user_id = ?) as user_rating
        FROM recipes r 
        JOIN users u ON r.user_id = u.id 
        LEFT JOIN recipe_ratings rr ON r.id = rr.recipe_id
        WHERE r.id = ?
        GROUP BY r.id";
$stmt = mysqli_prepare($conn, $sql);
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
mysqli_stmt_bind_param($stmt, "ii", $user_id, $recipe_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: ../index.php");
    exit();
}

$recipe = mysqli_fetch_assoc($result);
// Clean up any remaining line breaks
$recipe['ingredients'] = str_replace(['\n', "\r\n", "\r"], "\n", $recipe['clean_ingredients']);
$recipe['instructions'] = str_replace(['\n', "\r\n", "\r"], "\n", $recipe['clean_instructions']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($recipe['title']); ?> - Filipino Delicacy Recipes</title>
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
            padding: 5px 10px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .nav-links a:hover {
            background-color: #ffd700;
            color: #333;
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

        .recipe-container {            background: rgba(255, 255, 255, 0.95);            padding: 25px;            border-radius: 15px;            margin-bottom: 30px;            max-width: 800px;            margin-left: auto;            margin-right: auto;        }        .recipe-header {            text-align: center;            margin-bottom: 30px;            position: relative;        }        .recipe-title {            color: #333;            font-size: 2em;            margin-bottom: 8px;        }        .recipe-meta {            color: #666;            font-size: 1em;            margin-bottom: 15px;            display: flex;            align-items: center;            justify-content: center;            gap: 15px;        }

        .recipe-meta p {
            margin: 0;
        }

        .recipe-date {
            font-size: 0.9em;
            color: #888;
        }

        .recipe-stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 20px 0;
            padding: 15px;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 10px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-label {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 1.2em;
            color: #333;
            font-weight: bold;
        }

        .recipe-image-container {            position: relative;            margin-bottom: 25px;            max-width: 500px;            margin-left: auto;            margin-right: auto;        }        .recipe-image {            width: 100%;            height: 250px;            object-fit: cover;            border-radius: 8px;            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);        }

        .recipe-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .recipe-section {
            margin-bottom: 30px;
        }

        .recipe-section h2 {
            color: #333;
            font-size: 1.8em;
            margin-bottom: 15px;
            border-bottom: 2px solid #ffd700;
            padding-bottom: 10px;
        }

        .recipe-section p {
            color: #444;
            line-height: 1.6;
            font-size: 1.1em;
        }

        .ingredients-list {
            list-style-type: none;
            padding: 0;
        }

        .ingredients-list li {
            color: #444;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
            font-size: 1.1em;
            display: flex;
            align-items: center;
        }

        .ingredients-list li:before {
            content: "•";
            color: #ffd700;
            font-size: 1.5em;
            margin-right: 10px;
        }

        .instructions-list {
            list-style-type: decimal;
            padding-left: 20px;
        }

        .instructions-list li {
            color: #444;
            padding: 15px 0;
            line-height: 1.6;
            font-size: 1.1em;
            margin-bottom: 10px;
            background: rgba(255, 215, 0, 0.05);
            padding: 20px;
            border-radius: 8px;
        }

        .recipe-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
        }

        .action-btn {
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: opacity 0.3s;
        }

        .action-btn:hover {
            opacity: 0.9;
            text-decoration: none;
        }

        .edit-btn {
            background: #28a745;
            color: white;
        }

        .delete-btn {
            background: #dc3545;
            color: white;
        }

        .back-btn {
            background: #ffd700;
            color: #333;
        }

        .print-btn {
            background: #6c757d;
            color: white;
        }

        .recipe-category {
            display: inline-block;
            background: #ffd700;
            color: #333;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            margin: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .recipe-info {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        @media print {
            .navbar, .recipe-actions {
                display: none;
            }
            body {
                background: none;
            }
            .recipe-container {
                box-shadow: none;
                padding: 0;
            }
        }

        .rating-container {
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 10px;
        }

        .stars {
            display: inline-flex;
            flex-direction: row-reverse;
            gap: 5px;
        }

        .stars input {
            display: none;
        }

        .stars label {
            cursor: pointer;
            font-size: 30px;
            color: #ddd;
            transition: color 0.2s;
        }

        .stars label:hover,
        .stars label:hover ~ label,
        .stars input:checked ~ label {
            color: #ffd700;
        }

        .rating-stats {
            margin-top: 10px;
            color: #666;
        }

        .current-rating {
            font-size: 1.2em;
            color: #333;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="brand">Filipino Delicacies</a>
            <div class="nav-links">
                <a href="../index.php">Home</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="../profile/my_profile.php">My Profile</a>
                    <a href="../logout.php" class="logout">Logout</a>
                <?php else: ?>
                    <a href="../login.php">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="recipe-container">
            <div class="recipe-header">
                <h1 class="recipe-title"><?php echo htmlspecialchars($recipe['title']); ?></h1>
                <div class="recipe-meta">
                    <p>By <?php echo htmlspecialchars($recipe['author']); ?></p>
                    <p class="recipe-date">Posted on <?php echo date('F j, Y', strtotime($recipe['created_at'])); ?></p>
                </div>

                <?php if (isset($_SESSION['user_id'])): ?>
                <div class="rating-container">
                    <form method="POST" id="ratingForm">
                        <div class="stars">
                            <?php for($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" <?php echo ($recipe['user_rating'] == $i) ? 'checked' : ''; ?>>
                            <label for="star<?php echo $i; ?>">★</label>
                            <?php endfor; ?>
                        </div>
                    </form>
                    <div class="rating-stats">
                        <span class="current-rating"><?php echo number_format($recipe['avg_rating'], 1); ?></span> / 5
                        <br>
                        <small>(<?php echo $recipe['rating_count']; ?> ratings)</small>
                    </div>
                </div>
                <?php endif; ?>

                <div class="recipe-info">
                    <span class="recipe-category"><?php echo htmlspecialchars($recipe['category']); ?></span>
                    <span class="recipe-category"><?php echo htmlspecialchars($recipe['dish_type']); ?></span>
                </div>
                <div class="recipe-stats">
                    <div class="stat-item">
                        <div class="stat-label">Cook Time</div>
                        <div class="stat-value"><?php echo htmlspecialchars($recipe['cook_time'] ?? '45 mins'); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Servings</div>
                        <div class="stat-value"><?php echo htmlspecialchars($recipe['servings'] ?? '4-6'); ?></div>
                    </div>
                </div>
            </div>

            <?php if (!empty($recipe['image_path'])): ?>
                <div class="recipe-image-container">
                    <img src="../<?php echo htmlspecialchars($recipe['image_path']); ?>" 
                         alt="<?php echo htmlspecialchars($recipe['title']); ?>"
                         class="recipe-image">
                </div>
            <?php endif; ?>

            <div class="recipe-details">
                <div class="recipe-section">
                    <h2>Description</h2>
                    <p><?php echo nl2br(htmlspecialchars($recipe['description'])); ?></p>
                </div>

                <div class="recipe-section">
                    <h2>Ingredients</h2>
                    <ul class="ingredients-list">
                        <?php 
                        $ingredients = array_filter(array_map('trim', explode("\n", $recipe['ingredients'])));
                        foreach ($ingredients as $ingredient):
                        ?>
                            <li><?php echo htmlspecialchars($ingredient); ?></li>
                        <?php 
                        endforeach; 
                        ?>
                    </ul>
                </div>
            </div>

            <div class="recipe-section">
                <h2>Instructions</h2>
                <ol class="instructions-list">
                    <?php 
                    $instructions = array_filter(array_map('trim', explode("\n", $recipe['instructions'])));
                    foreach ($instructions as $instruction):
                    ?>
                        <li><?php echo htmlspecialchars($instruction); ?></li>
                    <?php 
                    endforeach; 
                    ?>
                </ol>
            </div>

            <div class="recipe-actions">
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $recipe['user_id']): ?>
                    <a href="edit_recipe.php?id=<?php echo $recipe['id']; ?>" class="action-btn edit-btn">Edit Recipe</a>
                    <a href="delete_recipe.php?id=<?php echo $recipe['id']; ?>" 
                       class="action-btn delete-btn" 
                       onclick="return confirm('Are you sure you want to delete this recipe?');">Delete Recipe</a>
                <?php endif; ?>
                <a href="javascript:history.back()" class="action-btn back-btn">Back</a>
                <a href="#" class="action-btn print-btn" onclick="downloadRecipe()">Download Recipe</a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ratingForm = document.getElementById('ratingForm');
            if (ratingForm) {
                const ratingInputs = ratingForm.querySelectorAll('input[name="rating"]');
                ratingInputs.forEach(input => {
                    input.addEventListener('change', function() {
                        ratingForm.submit();
                    });
                });
            }
        });
        
        function downloadRecipe() {
            const recipeTitle = <?php echo json_encode(htmlspecialchars($recipe['title'])); ?>;
            const content = document.querySelector('.recipe-container').innerHTML;
            const blob = new Blob([`
                <html>
                <head>
                    <title>${recipeTitle}</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        h1 { color: #333; }
                        .recipe-meta { color: #666; }
                        .recipe-stats { margin: 20px 0; }
                        .ingredients-list, .instructions-list { margin: 20px 0; }
                        .ingredients-list li { margin: 5px 0; }
                        .instructions-list li { margin: 10px 0; }
                    </style>
                </head>
                <body>
                    ${content}
                </body>
                </html>
            `], { type: 'text/html' });
            
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = recipeTitle + '.html';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
    </script>
</body>
</html> 