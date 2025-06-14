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

// Fetch recipe data to check ownership and get image path
$sql = "SELECT * FROM recipes WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $recipe_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $recipe = mysqli_fetch_assoc($result);
    
    // Delete the recipe image if it exists
    if (!empty($recipe['image_path']) && file_exists('../' . $recipe['image_path'])) {
        unlink('../' . $recipe['image_path']);
    }
    
    // Delete the recipe from database
    $sql = "DELETE FROM recipes WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $recipe_id, $_SESSION['user_id']);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "Recipe deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Error deleting recipe.";
    }
} else {
    $_SESSION['error_message'] = "Recipe not found or you don't have permission to delete it.";
}

header("Location: view_recipes.php");
exit(); 