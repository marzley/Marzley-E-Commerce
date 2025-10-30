<?php
session_start();

$username_display = 'Guest';

// Temporary debug (uncomment while troubleshooting)
 error_log('SESSION CONTENTS: ' . print_r($_SESSION, true));

// DB connection (XAMPP default: root / no password). Change if different.
$mysqli = new mysqli('localhost', 'root', '', 'mtkenya');
$mysqli->set_charset('utf8mb4');

if ($mysqli->connect_errno) {
    error_log('DB connect error: ' . $mysqli->connect_error);
} else {
    // helper to fetch username by prepared statement (id or email)
    $getById = function($id) use ($mysqli) {
        $username = null;
        $stmt = $mysqli->prepare('SELECT username FROM users WHERE id = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $stmt->bind_result($fetched);
                if ($stmt->fetch()) $username = $fetched;
            } else {
                error_log('Stmt execute error (id): ' . $stmt->error);
            }
            $stmt->close();
        } else {
            error_log('Prepare error (id): ' . $mysqli->error);
        }
        return $username;
    };

    $getByEmail = function($email) use ($mysqli) {
        $username = null;
        $stmt = $mysqli->prepare('SELECT username FROM users WHERE email = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $email);
            if ($stmt->execute()) {
                $stmt->bind_result($fetched);
                if ($stmt->fetch()) $username = $fetched;
            } else {
                error_log('Stmt execute error (email): ' . $stmt->error);
            }
            $stmt->close();
        } else {
            error_log('Prepare error (email): ' . $mysqli->error);
        }
        return $username;
    };

    // Try common session keys
    if (!empty($_SESSION['user_id']) || !empty($_SESSION['id'])) {
        $id = !empty($_SESSION['user_id']) ? intval($_SESSION['user_id']) : intval($_SESSION['id']);
        $fetched = $getById($id);
        if ($fetched) $username_display = htmlspecialchars($fetched, ENT_QUOTES, 'UTF-8');
    }

    // fallback: session may store username directly
    if ($username_display === 'Guest' && !empty($_SESSION['username'])) {
        $username_display = htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');
    }

    // fallback: session may store email, try lookup by email
    if ($username_display === 'Guest' && !empty($_SESSION['email'])) {
        $fetched = $getByEmail($_SESSION['email']);
        if ($fetched) $username_display = htmlspecialchars($fetched, ENT_QUOTES, 'UTF-8');
    }

    $mysqli->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Back, <?php echo $username; ?> - Marzley Dashboard</title>
    <link rel="stylesheet" href="style.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* --- Base Styles --- */
body {
    font-family: 'Roboto', sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f5f7fa; /* Light, soft background */
    color: #333;
}

.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* --- Header & User Info --- */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 0;
    border-bottom: 1px solid #e0e0e0;
    margin-bottom: 30px;
}

.logo {
    font-size: 1.8em;
    font-weight: 700;
    color: #007bff; /* Marzley Brand Color */
}

.user-info {
    font-size: 1em;
    color: #555;
}

.user-info i {
    margin-right: 8px;
    color: #28a94c; /* Success/Profile Color */
}

.user-info span {
    font-weight: 500;
}

/* --- Welcome Banner --- */
.welcome-banner {
    background-color: #ffffff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    margin-bottom: 40px;
    text-align: center;
}

.welcome-banner h2 {
    font-size: 2.5em;
    color: #333;
    margin-top: 0;
    margin-bottom: 10px;
    font-weight: 700;
}

.welcome-banner p {
    font-size: 1.1em;
    color: #777;
}

/* --- Action Grid --- */
.action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); /* Responsive grid */
    gap: 25px;
    margin-bottom: 40px;
}

.action-card {
    display: block;
    background-color: #ffffff;
    padding: 25px;
    border-radius: 12px;
    text-align: center;
    text-decoration: none;
    color: #333;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid #e0e0e0;
}

.action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.action-card i {
    font-size: 3em;
    color: #007bff; /* Default action color */
    margin-bottom: 10px;
}

.action-card h3 {
    font-size: 1.4em;
    margin: 10px 0 5px;
    font-weight: 600;
}

.action-card p {
    font-size: 0.9em;
    color: #999;
}

/* Highlight for Primary CTA */
.primary-action {
    background-color: #007bff;
    color: #ffffff;
}

.primary-action:hover {
    background-color: #0056b3;
}

.primary-action i, .primary-action p, .primary-action h3 {
    color: #ffffff; /* Ensure all text/icons are white */
}


/* --- Recent Activity --- */
.recent-activity {
    padding: 20px 0 40px;
}

.recent-activity h2 {
    font-size: 1.8em;
    color: #333;
    border-bottom: 2px solid #e0e0e0;
    padding-bottom: 10px;
    margin-bottom: 20px;
    font-weight: 500;
}

.recent-activity ul {
    list-style: none;
    padding: 0;
}

.recent-activity li {
    background-color: #ffffff;
    padding: 15px;
    margin-bottom: 10px;
    border-radius: 8px;
    box-shadow: 0 1px 5px rgba(0, 0, 0, 0.03);
    color: #555;
    font-size: 1em;
}

.recent-activity li i {
    margin-right: 10px;
    color: #6c757d;
}


/* --- Responsive Adjustments --- */
@media (max-width: 768px) {
    .header {
        flex-direction: column;
        text-align: center;
    }
    .user-info {
        margin-top: 10px;
    }
    .action-grid {
        grid-template-columns: 1fr; /* Stack cards vertically */
    }
    .welcome-banner h2 {
        font-size: 1.8em;
    }
}
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header class="header">
            <div class="logo">Marzley</div>
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span>Hello, <?php echo $username_display; ?></span>
            </div>
        </header>

        <main class="main-content">
            
            <section class="welcome-banner">
                <h2 style="font-family: 'Georgia', serif;">Welcome Back, <u style="color: #007bff; font-family: 'Georgia', serif;"><?php echo mb_convert_case($username_display, MB_CASE_TITLE, 'UTF-8'); ?></u> to Marzley E-Commerce</h2>
                <p>Your portal for managing orders, tracking shipments, and discovering the latest deals.</p>
            </section>

            <section class="action-grid">
                
                <a href="product.html" class="action-card primary-action">
                    <i class="fas fa-shopping-bag"></i>
                    <h3>Start Shopping</h3>
                    <p>Discover new arrivals and exclusive products.</p>
                </a>
                
                <a href="add to cart.html" class="action-card">
                    <i class="fas fa-box-open"></i>
                    <h3>My Orders</h3>
                    <p>Track shipments and view your purchase history.</p>
                </a>
                
                <a href="index.php" class="action-card">
                    <i class="fas fa-id-card"></i>
                    <h3>Manage Profile</h3>
                    <p>Update your address, payments, and preferences.</p>
                </a>

                <a href="#wishlist" class="action-card">
                    <i class="fas fa-heart"></i>
                    <h3>My Wishlist</h3>
                    <p>See items you've saved for later.</p>
                </a>
            </section>

            <section class="recent-activity">
                <h2>Your Recent Activity</h2>
                <ul>
                    <li><i class="fas fa-history"></i> Last login: Oct 30, 2025 at 16:09 EAT</li>
                    <li><i class="fas fa-bell"></i> New alert: Your recent order #4567 is being packed.</li>
                </ul>
            </section>
        </main>
    </div>
</body>
</html>