<?php
session_start();

$host = "sql103.infinityfree.com";
$user = "if0_38246856";
$password = "i5YTerobo6G2";
$database = "if0_38246856_tuiter";
$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Redirect to login page if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Post content function
function postContent($user_id, $content) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO actions (user_id, content, rating) VALUES (?, ?, 0)");
    $stmt->bind_param("is", $user_id, $content);
    return $stmt->execute();
}

// Like/Unlike function
function toggleLike($user_id, $post_id) {
    global $conn;
    
    // Check if the user already liked the post
    $stmt = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->bind_param("ii", $user_id, $post_id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        // Unlike (Remove like)
        $stmt = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->bind_param("ii", $user_id, $post_id);
        $stmt->execute();
        
        $stmt = $conn->prepare("UPDATE actions SET rating = rating - 1 WHERE id = ?");
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
    } else {
        // Like (Add like)
        $stmt = $conn->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $post_id);
        $stmt->execute();
        
        $stmt = $conn->prepare("UPDATE actions SET rating = rating + 1 WHERE id = ?");
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
    }
}

// Fetch content based on algorithm
function getFilteredContent() {
    global $conn;
    $sql = "SELECT actions.*, users.username FROM actions JOIN users ON actions.user_id = users.id ORDER BY rating DESC, user_id, content";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Handle posting content
if (isset($_POST['post']) && isset($_SESSION['user_id'])) {
    postContent($_SESSION['user_id'], $_POST['content']);
    header("Location: index.php");
    exit();
}

// Handle like/unlike
if (isset($_POST['rate']) && isset($_SESSION['user_id'])) {
    toggleLike($_SESSION['user_id'], $_POST['post_id']);
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <title>Tuiter Notes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
        }
    </script>
    <style>
        .dark-mode { 
            background-color: #121212; 
            color: white;
        }
        .posts {
            padding: 1rem;
            margin-top: 1rem;
            border: #121212 1px solid;
            border-radius: 10px;
        }
        .footer {
            margin-top: 5rem;
            display: flex;
            justify-content: center;
            align-items: center;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Tuiter Notes</h1>
        <button class="btn btn-outline-dark" onclick="toggleTheme()">Toggle Dark Mode</button>
        <a href="logout.php" class="btn btn-danger float-end">Logout</a>
        
        <h2 class="mt-4">Post Content</h2>
        <form method="POST">
            <textarea name="content" class="form-control" placeholder="Write something..." required></textarea>
            <button type="submit" name="post" class="btn btn-primary mt-2">Post</button>
        </form>

        <div class="posts">
            <h2 class="mt-4">Posts</h2>
            <ul class="list-group">
                <?php
                $posts = getFilteredContent();
                foreach ($posts as $post) {
                    echo "<li class='list-group-item'><strong>" . htmlspecialchars($post['username']) . "</strong>: " . htmlspecialchars($post['content']) .
                        " <form method='POST' style='display:inline;'>" .
                        "<input type='hidden' name='post_id' value='" . $post['id'] . "'>" .
                        "<button type='submit' name='rate' class='btn btn-sm btn-outline-warning'>👍</button>" .
                        " (" . $post['rating'] . ")</form></li>";
                }
                ?>
            </ul>
        </div>
        
    </div>

    <footer>
        <div class="footer card-footer text-body-secondary d-flex align-items-center">
            <p>© 2025 | SSIP - IT6</p>
        </div>
    </footer>
</body>
</html>
