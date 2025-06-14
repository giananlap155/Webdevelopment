<?php
session_start();
require_once 'config/database.php';

// Fetch recipes from database
$sql = "SELECT * FROM recipes ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);

// Fetch featured recipes (highest rated)
$featured_sql = "SELECT r.*, u.username as author, u.profile_picture,
                 COALESCE(AVG(rr.rating), 0) as avg_rating,
                 COUNT(rr.id) as rating_count
                 FROM recipes r 
                 JOIN users u ON r.user_id = u.id 
                 LEFT JOIN recipe_ratings rr ON r.id = rr.recipe_id
                 GROUP BY r.id
                 HAVING rating_count >= 1
                 ORDER BY avg_rating DESC, rating_count DESC
                 LIMIT 3";
$featured_result = mysqli_query($conn, $featured_sql);

// Fetch latest recipes
$latest_sql = "SELECT r.*, u.username as author, u.profile_picture 
               FROM recipes r 
               JOIN users u ON r.user_id = u.id 
               ORDER BY r.created_at DESC 
               LIMIT 6";
$latest_result = mysqli_query($conn, $latest_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filipino Delicacy Recipes</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)),
                        url('images/filipino-food-bg.jpg') center/cover fixed,
                        repeating-linear-gradient(45deg, #f8f9fa 0px, #f8f9fa 10px, #ffffff 10px, #ffffff 20px);
            font-family: 'Arial', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
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

        .nav-links a.register-btn {
            background-color: #28a745;
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .nav-links a.register-btn:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        .main-content {
            margin-top: 60px;
            padding: 20px;
            color: white;
            margin-bottom: 70px;
        }

        .welcome-section {
            text-align: center;
            padding: 100px 20px;
            margin-bottom: 60px;
            position: relative;
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)),
                        url('images/pattern-bg.png');
            background-attachment: fixed;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .welcome-section h1 {
            font-size: 4em;
            color: #ffd700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            animation: fadeInDown 1s ease-out;
            font-family: 'Playfair Display', serif;
        }

        .welcome-section p {
            color: #fff;
            font-size: 1.6em;
            margin-bottom: 40px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.8);
            animation: fadeInUp 1s ease-out;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
        }

        .featured-delicacies {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .featured-title {
            text-align: center;
            color: #ffd700;
            font-size: 2em;
            margin-bottom: 40px;
        }

        .delicacy-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .delicacy-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            overflow: hidden;
            transition: transform 0.3s ease;
            color: #333;
        }

        .delicacy-card:hover {
            transform: translateY(-10px);
        }

        .delicacy-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .delicacy-content {
            padding: 20px;
        }

        .delicacy-content h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.5em;
        }

        .delicacy-content p {
            color: #666;
            margin-bottom: 15px;
        }

        .view-recipe-btn {
            display: inline-block;
            background: #ffd700;
            color: #333;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .view-recipe-btn:hover {
            background: #ffed4a;
            text-decoration: none;
        }

        .add-recipe-btn {
            background: #ffd700;
            color: #333;
            padding: 12px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            display: inline-block;
            margin: 20px 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        .add-recipe-btn:hover {
            background: #ffed4a;
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.3);
            text-decoration: none;
            color: #333;
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

        .featured-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 20px;
            margin: 40px auto;
            max-width: 1000px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

        .section-title {
            color: #333;
            text-align: center;
            font-size: 2.2em;
            margin-bottom: 40px;
            position: relative;
            padding-bottom: 15px;
            font-weight: bold;
        }

        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: #ffd700;
        }

        .featured-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .featured-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            max-width: 300px;
            margin: 0 auto;
        }

        .featured-image {
            width: 100%;
            height: 130px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .featured-content {
            padding: 15px;
            background: #fff;
        }

        .featured-title {
            color: #333;
            font-size: 1.1em;
            margin-bottom: 10px;
            font-weight: bold;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .featured-description {
            color: #666;
            font-size: 0.95em;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .featured-rating {
            color: #ffd700;
            font-size: 1.3em;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .featured-rating small {
            color: #666;
            font-size: 0.7em;
        }

        .view-recipe-btn {
            display: inline-block;
            background: #ffd700;
            color: #333;
            padding: 12px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .view-recipe-btn:hover {
            background: #ffed4a;
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.2);
        }

        .recipe-profile {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            background: rgba(255, 255, 255, 0.95);
        }

        .profile-pic {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 2px solid #ffd700;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .profile-info {
            flex-grow: 1;
        }

        .profile-name {
            color: #333;
            font-weight: 600;
            font-size: 1em;
            margin: 0;
        }

        .profile-date {
            color: #666;
            font-size: 0.85em;
            margin: 5px 0 0 0;
        }

        .quick-filters {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .filter-tag {
            background: rgba(255, 215, 0, 0.1);
            color: #ffd700;
            padding: 8px 20px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 215, 0, 0.3);
        }

        .filter-tag:hover {
            background: rgba(255, 215, 0, 0.2);
            transform: translateY(-2px);
        }

        .filter-tag.active {
            background: #ffd700;
            color: #333;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media screen and (max-width: 768px) {
            .welcome-section {
                padding: 60px 20px;
            }

            .welcome-section h1 {
                font-size: 2.5em;
            }

            .welcome-section p {
                font-size: 1.2em;
            }

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

            .featured-grid {
                grid-template-columns: 1fr;
            }

            .categories-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .featured-image {
                height: 120px;
            }
        }

        @media screen and (max-width: 480px) {
            .categories-grid {
                grid-template-columns: 1fr;
            }

            .featured-image {
                height: 110px;
            }

            .search-select,
            .search-input,
            .search-btn {
                font-size: 0.85em;
                padding: 8px 15px;
            }
        }

        .categories-section {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)),
                        url('images/filipino-food-bg.jpg') center/cover;
            padding: 60px 20px;
            border-radius: 20px;
            margin: 40px auto;
            max-width: 1200px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .categories-section .section-title {
            color: #ffd700;
            font-size: 2.5em;
            margin-bottom: 40px;
            text-align: center;
            position: relative;
            padding-bottom: 15px;
            font-family: 'Playfair Display', serif;
        }

        .categories-section .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 150px;
            height: 3px;
            background: #ffd700;
            border-radius: 2px;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            padding: 20px;
        }

        .category-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            border: 2px solid transparent;
        }

        .category-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255, 215, 0, 0.1), rgba(255, 215, 0, 0.05));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .category-card:hover {
            transform: translateY(-10px);
            border-color: #ffd700;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .category-card:hover::before {
            opacity: 1;
        }

        .category-icon {
            font-size: 3em;
            color: #ffd700;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            position: relative;
        }

        .category-card:hover .category-icon {
            transform: scale(1.1);
        }

        .category-card h3 {
            color: #333;
            font-size: 1.6em;
            margin-bottom: 15px;
            font-family: 'Playfair Display', serif;
            position: relative;
        }

        .category-card p {
            color: #666;
            font-size: 1em;
            line-height: 1.6;
            margin-bottom: 20px;
            position: relative;
        }

        .category-card::after {
            content: '→';
            position: absolute;
            bottom: 20px;
            right: 30px;
            color: #ffd700;
            font-size: 1.5em;
            opacity: 0;
            transform: translateX(-20px);
            transition: all 0.3s ease;
        }

        .category-card:hover::after {
            opacity: 1;
            transform: translateX(0);
        }

        .category-recipe-count {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 215, 0, 0.2);
            color: #333;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: bold;
        }

        @media screen and (max-width: 768px) {
            .categories-section {
                padding: 40px 15px;
                margin: 20px auto;
            }

            .categories-section .section-title {
                font-size: 2em;
                margin-bottom: 30px;
            }

            .categories-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
                padding: 10px;
            }

            .category-card {
                padding: 20px;
            }

            .category-icon {
                font-size: 2.5em;
            }

            .category-card h3 {
                font-size: 1.3em;
            }

            .category-card p {
                font-size: 0.9em;
            }
        }

        @media screen and (max-width: 480px) {
            .categories-grid {
                grid-template-columns: 1fr;
            }

            .categories-section .section-title {
                font-size: 1.8em;
            }
        }

        /* Why Choose Section */
        .features-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 50px 20px;
            margin: 40px auto;
            max-width: 1200px;
            border-radius: 20px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

        .features-section .section-title {
            color: #333;
            text-align: center;
            font-size: 2em;
            margin-bottom: 40px;
            position: relative;
            padding-bottom: 15px;
        }

        .features-section .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: #ffd700;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            padding: 20px;
        }

        .feature-card {
            text-align: center;
            padding: 30px;
            background: #fff;
            border-radius: 15px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            border-color: #ffd700;
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }

        .feature-icon {
            font-size: 2.5em;
            color: #ffd700;
            margin-bottom: 20px;
        }

        .feature-title {
            color: #333;
            font-size: 1.4em;
            margin-bottom: 15px;
            font-family: 'Playfair Display', serif;
        }

        .feature-description {
            color: #666;
            line-height: 1.6;
        }

        /* Latest Recipes Section */
        .latest-recipes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 20px;
            max-width: 1000px;
            margin-left: auto;
            margin-right: auto;
        }

        .latest-recipe-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            max-width: 280px;
            margin: 0 auto;
        }

        .latest-recipe-image {
            width: 100%;
            height: 120px;
            object-fit: cover;
        }

        .latest-recipe-content {
            padding: 15px;
            background: #fff;
        }

        .latest-recipe-content h3 {
            color: #333;
            font-size: 1.1em;
            margin-bottom: 10px;
            font-weight: bold;
            line-height: 1.4;
        }

        /* Essential Cooking Tips Section */
        .cooking-tips {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)),
                        url('images/cooking-bg.jpg') center/cover;
            color: #fff;
        }

        .tips-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
            padding: 20px;
        }

        .tip-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            backdrop-filter: blur(5px);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .tip-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.15);
            border-color: #ffd700;
        }

        .tip-icon {
            font-size: 2em;
            margin-bottom: 15px;
        }

        .tip-card h3 {
            color: #ffd700;
            margin-bottom: 10px;
            font-size: 1.2em;
        }

        .tip-card p {
            color: #fff;
            font-size: 0.95em;
            line-height: 1.5;
        }

        /* Footer Sections */
        .footer {
            background: rgba(0, 0, 0, 0.9);
            padding: 60px 0 20px;
            margin-top: 60px;
            color: #fff;
        }

        .footer-container {
            max-width: 900px;  /* Reduced max-width to bring sections closer */
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);  /* Fixed 3 columns */
            gap: 30px;
            justify-content: center;
        }

        .footer-section {
            margin-bottom: 30px;
        }

        .footer-section h3 {
            color: #ffd700;
            font-size: 1.4em;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-section h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 2px;
            background: #ffd700;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-links a {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: #ffd700;
            color: #333;
            transform: translateY(-3px);
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            color: #fff;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer-links a:hover {
            color: #ffd700;
        }

        .newsletter-form {
            margin-top: 20px;
        }

        .newsletter-form input {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            margin-bottom: 15px;
        }

        .newsletter-form input:focus {
            outline: none;
            border-color: #ffd700;
        }

        .newsletter-form button {
            background: #ffd700;
            color: #333;
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            width: 100%;
        }

        .newsletter-form button:hover {
            background: #ffed4a;
            transform: translateY(-2px);
        }

        .copyright {
            text-align: center;
            padding-top: 30px;
            margin-top: 40px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            grid-column: 1 / -1;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Responsive Adjustments */
        @media screen and (max-width: 768px) {
            .features-grid,
            .latest-recipes-grid,
            .tips-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .footer-container {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .footer-section h3::after {
                left: 50%;
                transform: translateX(-50%);
            }

            .social-links {
                justify-content: center;
            }
        }

        @media screen and (max-width: 480px) {
            .features-grid,
            .latest-recipes-grid,
            .tips-grid {
                grid-template-columns: 1fr;
            }

            .featured-image {
                height: 110px;
            }

            .footer-section {
                text-align: center;
            }

            .footer-section h3::after {
                left: 50%;
                transform: translateX(-50%);
            }

            .social-links {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="brand">Filipino Delicacies</a>
            <div class="nav-links">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="index.php">Home</a>
                    <a href="recipes/view_recipes.php">All Recipes</a>
                    <a href="profile/my_profile.php">My Profile</a>
                    <a href="#about-section">About Us</a>
                    <a href="logout.php" class="logout">Logout</a>
                <?php else: ?>
                    <a href="index.php">Home</a>
                    <a href="recipes/view_recipes.php">All Recipes</a>
                    <a href="#about-section">About Us</a>
                    <a href="login.php" class="login-btn">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="search-navbar">
        <div class="search-container">
            <div class="search-group">
                <select class="search-select" id="category">
                    <option value="">All Categories</option>
                    <option value="Main Dish">Main Dish</option>
                    <option value="Appetizer">Appetizer</option>
                    <option value="Dessert">Dessert</option>
                    <option value="Snack">Snack</option>
                    <option value="Beverage">Beverage</option>
                </select>
                <select class="search-select" id="dish_type">
                    <option value="">All Dish Types</option>
                    <option value="Soup">Soup</option>
                    <option value="Grilled">Grilled</option>
                    <option value="Fried">Fried</option>
                    <option value="Stewed">Stewed</option>
                    <option value="Steamed">Steamed</option>
                    <option value="Baked">Baked</option>
                    <option value="Raw">Raw</option>
                </select>
                <input type="text" class="search-input" id="search" placeholder="Search recipes...">
                <button class="search-btn" onclick="filterRecipes()">Search</button>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="welcome-section">
            <h1>Welcome to Filipino Delicacies</h1>
            <p>Discover the rich and diverse flavors of Philippine cuisine</p>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="recipes/add_recipe.php" class="add-recipe-btn">Share Your Recipe</a>
            <?php else: ?>
                <a href="login.php" class="add-recipe-btn">Login to Share Your Recipe</a>
            <?php endif; ?>
            
            <div class="quick-filters">
                <div class="filter-tag" data-category="all">All Recipes</div>
                <div class="filter-tag" data-category="Main Dish">Main Dishes</div>
                <div class="filter-tag" data-category="Dessert">Desserts</div>
                <div class="filter-tag" data-category="Appetizer">Appetizers</div>
                <div class="filter-tag" data-category="Snack">Snacks</div>
                <div class="filter-tag" data-category="Popular">Most Popular</div>
            </div>
        </div>

        <div class="categories-section">
            <h2 class="section-title">Explore Categories</h2>
            <div class="categories-grid">
                <div class="category-card" onclick="filterRecipes('Main Dish')">
                    <div class="category-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h3>Main Dishes</h3>
                    <p>Discover authentic Filipino main courses from adobo to sinigang</p>
                </div>
                <div class="category-card" onclick="filterRecipes('Appetizer')">
                    <div class="category-icon">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <h3>Appetizers</h3>
                    <p>Start your meal with traditional Filipino appetizers</p>
                </div>
                <div class="category-card" onclick="filterRecipes('Dessert')">
                    <div class="category-icon">
                        <i class="fas fa-ice-cream"></i>
                    </div>
                    <h3>Desserts</h3>
                    <p>Indulge in sweet Filipino delicacies and treats</p>
                </div>
                <div class="category-card" onclick="filterRecipes('Snack')">
                    <div class="category-icon">
                        <i class="fas fa-cookie"></i>
                    </div>
                    <h3>Snacks</h3>
                    <p>Perfect merienda options and street food favorites</p>
                </div>
                <div class="category-card" onclick="filterRecipes('Beverage')">
                    <div class="category-icon">
                        <i class="fas fa-glass-cheers"></i>
                    </div>
                    <h3>Beverages</h3>
                    <p>Refreshing Filipino drinks and smoothies</p>
                </div>
                <div class="category-card" onclick="filterRecipes('Soup')">
                    <div class="category-icon">
                        <i class="fas fa-soup"></i>
                    </div>
                    <h3>Soups</h3>
                    <p>Comforting Filipino soups and broths</p>
                </div>
            </div>
        </div>

        <?php if (mysqli_num_rows($featured_result) > 0): ?>
        <div class="featured-section" id="featured-section">
            <h2 class="section-title">Featured Filipino Delicacies</h2>
            <div class="featured-grid">
                <?php while ($recipe = mysqli_fetch_assoc($featured_result)): ?>
                    <div class="featured-card">
                        <div class="featured-badge">★ <?php echo number_format($recipe['avg_rating'], 1); ?></div>
                        <?php if (!empty($recipe['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($recipe['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($recipe['title']); ?>"
                                 class="featured-image">
                        <?php else: ?>
                            <img src="images/default-recipe.jpg" 
                                 alt="Default recipe image"
                                 class="featured-image">
                        <?php endif; ?>
                        <div class="featured-content">
                            <h3 class="featured-title"><?php echo htmlspecialchars($recipe['title']); ?></h3>
                            <div class="featured-rating">
                                <?php
                                $rating = round($recipe['avg_rating']);
                                for ($i = 1; $i <= 5; $i++) {
                                    echo ($i <= $rating) ? '★' : '☆';
                                }
                                ?>
                                <small>(<?php echo $recipe['rating_count']; ?> ratings)</small>
                            </div>
                            <p class="featured-description">
                                <?php echo htmlspecialchars(substr($recipe['description'], 0, 100)) . '...'; ?>
                            </p>
                            <a href="recipes/view_recipe.php?id=<?php echo $recipe['id']; ?>" class="view-recipe-btn">View Recipe</a>
                        </div>
                        <div class="recipe-profile">
                            <img src="<?php echo !empty($recipe['profile_picture']) ? htmlspecialchars($recipe['profile_picture']) : 'images/default-profile.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($recipe['author']); ?>" 
                                 class="profile-pic">
                            <div class="profile-info">
                                <p class="profile-name"><?php echo htmlspecialchars($recipe['author']); ?></p>
                                <p class="profile-date"><?php echo date('M d, Y', strtotime($recipe['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="features-section">
            <h2 class="section-title">Why Choose Filipino Delicacies?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h3 class="feature-title">Authentic Recipes</h3>
                    <p class="feature-description">Discover traditional Filipino recipes passed down through generations, preserving the authentic taste of our culture.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="feature-title">Community Driven</h3>
                    <p class="feature-description">Join our growing community of food lovers sharing their favorite recipes and cooking tips.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3 class="feature-title">Quality Rated</h3>
                    <p class="feature-description">Find the best recipes with our community rating system ensuring quality and taste.</p>
                </div>
            </div>
        </div>

        <!-- Latest Recipes Section -->
        <div class="features-section">
            <h2 class="section-title">Latest Recipes</h2>
            <div class="latest-recipes-grid">
                <?php while ($latest_recipe = mysqli_fetch_assoc($latest_result)): ?>
                    <div class="latest-recipe-card">
                        <?php if (!empty($latest_recipe['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($latest_recipe['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($latest_recipe['title']); ?>"
                                 class="latest-recipe-image">
                        <?php endif; ?>
                        <div class="latest-recipe-content">
                            <h3><?php echo htmlspecialchars($latest_recipe['title']); ?></h3>
                            <a href="recipes/view_recipe.php?id=<?php echo $latest_recipe['id']; ?>" 
                               class="view-recipe-btn">View Recipe</a>
                        </div>
                        <div class="recipe-profile">
                            <img src="<?php echo !empty($latest_recipe['profile_picture']) ? htmlspecialchars($latest_recipe['profile_picture']) : 'images/default-profile.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($latest_recipe['author']); ?>" 
                                 class="profile-pic">
                            <div class="profile-info">
                                <p class="profile-name"><?php echo htmlspecialchars($latest_recipe['author']); ?></p>
                                <p class="profile-date"><?php echo date('M d, Y', strtotime($latest_recipe['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Cooking Tips Section -->
        <div class="features-section cooking-tips" id="cooking-tips">
            <h2 class="section-title">Essential Cooking Tips</h2>
            <div class="tips-grid">
                <div class="tip-card">
                    <div class="tip-icon">🔥</div>
                    <h3>Temperature Control</h3>
                    <p>Master the art of heat control. Low and slow for stews, high heat for stir-frying.</p>
                </div>
                <div class="tip-card">
                    <div class="tip-icon">🧂</div>
                    <h3>Seasoning Balance</h3>
                    <p>Season gradually and taste as you cook. It's easier to add than to remove.</p>
                </div>
                <div class="tip-card">
                    <div class="tip-icon">⏲️</div>
                    <h3>Timing is Key</h3>
                    <p>Prep ingredients before cooking and follow recipe timing carefully.</p>
                </div>
                <div class="tip-card">
                    <div class="tip-icon">🥄</div>
                    <h3>Mise en Place</h3>
                    <p>Organize and prepare all ingredients before starting to cook.</p>
                </div>
                </div>
            </div>
        </div>

    <footer class="footer" id="footer">
        <div class="footer-container">
            <div class="footer-section" id="about-section">
                <h3>About Us</h3>
                <p style="color: #fff; margin: 0 0 15px 0;">Reach us on:</p>
                <div class="social-links">
                    <a href="https://facebook.com" target="_blank"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://twitter.com" target="_blank"><i class="fab fa-twitter"></i></a>
                    <a href="https://instagram.com" target="_blank"><i class="fab fa-instagram"></i></a>
                    <a href="https://youtube.com" target="_blank"><i class="fab fa-youtube"></i></a>
                </div>
            </div>

            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="#about-section">About Us</a></li>
                    <li><a href="recipes/add_recipe.php">Share Recipe</a></li>
                    <li><a href="#featured-section">Featured Recipes</a></li>
                    <li><a href="#cooking-tips">Cooking Tips</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h3>Newsletter</h3>
                <p style="margin: 0 0 15px 0;">Subscribe for new recipes and tips!</p>
                <form id="newsletter-form" class="newsletter-form">
                    <input type="email" placeholder="Enter your email" required>
                    <button type="submit">Subscribe</button>
                </form>
            </div>

            <div class="copyright">
                © 2025 Filipino Delicacies. All rights reserved.
            </div>
        </div>
    </footer>

    <!-- Add Font Awesome for social media icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <script>
        // Quick filters functionality
        document.querySelectorAll('.filter-tag').forEach(tag => {
            tag.addEventListener('click', function() {
                // Remove active class from all tags
                document.querySelectorAll('.filter-tag').forEach(t => t.classList.remove('active'));
                // Add active class to clicked tag
                this.classList.add('active');
                
                const category = this.dataset.category;
                if (category === 'Popular') {
                    // Handle popular recipes filter
                    window.location.href = `recipes/view_recipes.php?sort=popular`;
                } else if (category === 'all') {
                    window.location.href = 'recipes/view_recipes.php';
                } else {
                    // Filter by category
                    window.location.href = `recipes/view_recipes.php?category=${encodeURIComponent(category)}`;
                }
            });
        });

        // Filter recipes based on search, category, and dish type
        function filterRecipes(category = '', dishType = '') {
            const search = document.getElementById('search').value;
            const selectedCategory = category || document.getElementById('category').value;
            const selectedDishType = dishType || document.getElementById('dish_type').value;

            // Redirect to recipes page with filters
            window.location.href = `recipes/view_recipes.php?search=${encodeURIComponent(search)}&category=${encodeURIComponent(selectedCategory)}&dish_type=${encodeURIComponent(selectedDishType)}`;
        }

        // Add enter key support for search input
        document.getElementById('search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                filterRecipes();
            }
        });

        // Smooth scroll for all anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    const headerOffset = 80;
                    const elementPosition = targetElement.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Newsletter form submission
        document.getElementById('newsletter-form').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Thank you for subscribing to our newsletter!');
            this.reset();
        });

        // Add animation on scroll for categories
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate');
                }
            });
        }, {
            threshold: 0.1
        });

        document.querySelectorAll('.category-card').forEach(card => {
            observer.observe(card);
        });
    </script>
</body>
</html>
