<?php

include 'connection.php';

// ifnu buat
function registrasi($conn, $username, $fullname, $email, $password)
{
    $sql = "SELECT id FROM users WHERE username=? OR email=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        return [
            "status" => false,
            "message" => "Username already exists!"
        ];
    }
    $stmt->close();

    if (strlen($password) < 8) {
        return [
            "status" => false,
            "message" => "Password must be at least 8 characters!"
        ];
    }
    $password = password_hash($password, PASSWORD_DEFAULT);

    $username = htmlspecialchars($username);
    $fullname = htmlspecialchars($fullname);
    $email = htmlspecialchars($email);

    $sql = "INSERT INTO users (username, fullname, email, password) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $username, $fullname, $email, $password);

    if ($stmt->execute()) {
        return [
            "status" => true,
            "message" => "Successfully registered!"
        ];
    } else {
        return [
            "status" => false,
            "message" => "Failed to register!"
        ];
    }
}

// Cara penggunaan registrasi
// $result = registrasi($conn, $username, $fullname, $email, $password);
// if($result['status'])
// {
//     echo "<script>alert('".$result['message']."')</script>";
//     header('Location: login.php');
// }
// else
// {
//     echo "<script>alert('".$result['message']."')</script>";
// }

// ifnu buat
function login($conn, $usernameOrEmail, $password)
{
    $sql = "SELECT id, username, fullname, email, password, role FROM users WHERE username=? OR email=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return [
            "status" => false,
            "message" => "Username or email not found!"
        ];
    }

    $user = $result->fetch_assoc();

    if (!password_verify($password, $user['password'])) {
        return [
            "status" => false,
            "message" => "Password is incorrect!"
        ];
    }

    return [
        "status" => true,
        "message" => "Successfully logged in!",
        "data" => [
            "id" => $user['id'],
            "username" => $user['username'],
            "fullname" => $user['fullname'],
            "email" => $user['email'],
            "role" => $user['role'],
        ]
    ];
}

// Cara penggunaan login
// $result = login($conn, $usernameOrEmail, $password);
// if($result['status'])
// {
//     session_start();
//     $_SESSION['user] = $result['data'];
//     if($result['data']['role'] == 'admin')
//     {
//         header('Location: admin.php');
//     }
//     else
//     {
//         header('Location: index.php');
//     }
// }
// else
// {
//     echo "<script>alert('".$result['message']."')</script>";
// }

// ifnu buat
function getAllPosts($conn)
{
    $sql = "
        SELECT 
            p.id as post_id,
            p.title,
            p.content,
            p.created_at,
            p.photo,
            p.watching_counter,
            p.share_counter,
            p.isSolve,
            u.id,
            u.username AS author,
            u.fullname AS author_fullname,
            u.photo AS author_photo,
            c.name AS category,
            (SELECT COUNT(*) FROM comments WHERE comments.post_id = p.id) AS comment_count
        FROM 
            posts p
        JOIN 
            users u ON p.user_id = u.id
        JOIN 
            categories c ON p.category_id = c.id
        ORDER BY 
            p.created_at DESC
    ";

    $result = $conn->query($sql);

    $posts = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $posts[] = [
                "user_id" => $row['id'],
                "id" => $row['post_id'],
                "title" => $row['title'],
                "content" => $row['content'],
                "created_at" => $row['created_at'],
                "photo" => $row['photo'],
                "watching_counter" => $row['watching_counter'],
                "share_counter" => $row['share_counter'],
                "isSolve" => $row['isSolve'],
                "author" => [
                    "username" => $row['author'],
                    "fullname" => $row['author_fullname'],
                    "photo" => $row['author_photo']
                ],
                "category" => $row['category'],
                "comment_count" => $row['comment_count']
            ];
        }
    }

    return $posts;
}

// cara penggunaan getAllPosts

// $allPosts = getAllPosts($conn);

// if (empty($allPosts)) 
// {
//     echo "Tidak ada postingan yang tersedia.";
// } else 
// {
//     foreach ($allPosts as $post) {
//         echo "isSolve: " . $post['isSolve'] . "<br>";
//         echo "Title: " . $post['title'] . "<br>";
//         echo "Created At: " . $post['created_at'] . "<br>";
//         echo "User ID: " . $post['user_id'] . "<br>";
//         echo "Author: " . $post['author']['fullname'] . " (" . $post['author']['username'] . ")<br>";
//         echo "Category: " . $post['category'] . "<br>";
//         echo "Comments: " . $post['comment_count'] . "<br>";
//         echo "Content: " . $post['content'] . "<br>";
//         echo "watching_counter: " . $post['watching_counter'] . "<br>";
//         echo "share_counter: " . $post['share_counter'] . "<br><br>";
//     }
// }

// ifnu buat
function getSinglePost($conn, $postId)
{
    $sql = "
        SELECT 
            p.id,
            p.title,
            p.content,
            p.created_at,
            p.photo,
            p.watching_counter,
            p.share_counter,
            p.isSolve,
            u.id,
            u.username AS author,
            u.fullname AS author_fullname,
            u.photo AS author_photo,
            c.name AS category
        FROM 
            posts p
        JOIN 
            users u ON p.user_id = u.id
        JOIN 
            categories c ON p.category_id = c.id
        WHERE 
            p.id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return [
            "status" => false,
            "message" => "Post not found!"
        ];
    }

    $post = $result->fetch_assoc();

    $sqlComments = "
        SELECT 
            c.id,
            c.content,
            c.created_at,
            u.id,
            u.username AS commenter,
            u.fullname AS commenter_fullname,
            u.photo AS commenter_photo
        FROM 
            comments c
        JOIN 
            users u ON c.user_id = u.id
        WHERE 
            c.post_id = ?
        ORDER BY 
            c.created_at ASC
    ";

    $stmtComments = $conn->prepare($sqlComments);
    $stmtComments->bind_param("i", $postId);
    $stmtComments->execute();
    $commentsResult = $stmtComments->get_result();

    $comments = [];
    while ($comment = $commentsResult->fetch_assoc()) {
        $comments[] = [
            "id" => $comment['id'],
            "content" => $comment['content'],
            "created_at" => $comment['created_at'],
            "commenter" => [
                "id" => $comment['id'],
                "username" => $comment['commenter'],
                "fullname" => $comment['commenter_fullname'],
                "photo" => $comment['commenter_photo']
            ]
        ];
    }

    return [
        "status" => true,
        "post" => [
            "user_id" => $post['id'],
            "id" => $post['id'],
            "title" => $post['title'],
            "content" => $post['content'],
            "created_at" => $post['created_at'],
            "photo" => $post['photo'],
            "watching_counter" => $post['watching_counter'],
            "share_counter" => $post['share_counter'],
            "isSolve" => $post['isSolve'],
            "author" => [
                "username" => $post['author'],
                "fullname" => $post['author_fullname'],
                "photo" => $post['author_photo']
            ],
            "category" => $post['category']
        ],
        "comments" => $comments
    ];
}


// cara penggunaan getSinglePost
// $postId = 1; 
// $singlePost = getSinglePost($conn, $postId);

// if ($singlePost['status']) {
//     $post = $singlePost['post'];
//     echo "Created At: " . $post['created_at'] . "<br>";
//     echo "Title: " . $post['title'] . "<br>";
//     echo "Author: " . $post['author']['fullname'] . " (" . $post['author']['username'] . ")<br>";
//     echo "Category: " . $post['category'] . "<br>";
//     echo "Content: " . $post['content'] . "<br><br>";

//     echo "Comments:<br>";
//     foreach ($singlePost['comments'] as $comment) {
//         echo $comment['created_at'] . "<br>";
//         echo "- " . $comment['commenter']['fullname'] . " (" . $comment['commenter']['username'] . "): " . $comment['content'] . "<br><br>";
//     }
// } else {
//     echo $singlePost['message'];
// }

// ifnu buat
function getAllUsers($conn)
{
    $sql = "SELECT id, username, fullname, email, created_at, photo FROM users WHERE role = 'user'";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];

    while ($user = $result->fetch_assoc()) {
        $users[] = [
            "id" => $user['id'],
            "username" => $user['username'],
            "fullname" => $user['fullname'],
            "email" => $user['email'],
            "created_at" => $user['created_at'],
            "photo" => $user['photo']
        ];
    }

    if (empty($users)) {
        return [
            "status" => false,
            "message" => "Ops!. User not found!"
        ];
    }
    return [
        "status" => true,
        "users" => $users
    ];
}

// cara penggunaan getAllUsers

// $allUsers = getAllUsers($conn);

// if ($allUsers['status']) {
//     echo "Daftar Pengguna:<br>";
//     foreach ($allUsers['users'] as $user) {
//         echo "ID: " . $user['id'] . "<br>";
//         echo "Username: " . $user['username'] . "<br>";
//         echo "Fullname: " . $user['fullname'] . "<br>";
//         echo "Email: " . $user['email'] . "<br>";
//         echo "Created At: " . $user['created_at'] . "<br>";
//         echo "Photo: <img src='" . $user['photo'] . "' alt='Photo' width='50'><br><br>";
//     }
// } else {
//     // Menampilkan pesan jika tidak ada pengguna ditemukan
//     echo $allUsers['message'];
// }

// ifnu buat
function getCategories($conn)
{
    $sql = "SELECT id, name FROM categories";
    $result = $conn->query($sql);

    $categories = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = [
                "id" => $row['id'],
                "name" => $row['name']
            ];
        }
    }

    return $categories;
}

// cara penggunaan getCategories

// $categories = getCategories($conn);

// if (empty($categories)) {
//     echo "Tidak ada kategori yang tersedia.";
// } else {
//     echo "Daftar Kategori:<br>";
//     foreach ($categories as $category) {
//         echo "ID: " . $category['id'] . "<br>";
//         echo "Name: " . $category['name'] . "<br><br>";
//     }
// }


// ifnu buat
function getProfileUserId($conn, $userId)
{

    $sqlUser = "SELECT id, username, fullname, bio, email, photo, created_at, role FROM users WHERE id = ?";
    $stmtUser = $conn->prepare($sqlUser);
    $stmtUser->bind_param("i", $userId);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();

    if ($resultUser->num_rows == 0) {
        return [
            "status" => false,
            "message" => "Pengguna tidak ditemukan."
        ];
    }

    $user = $resultUser->fetch_assoc();

    $sqlPosts = "
        SELECT 
            p.id, 
            p.title, 
            p.content, 
            p.created_at, 
            p.photo, 
            p.watching_counter, 
            p.share_counter, 
            p.isSolve, 
            c.name AS category,
            (SELECT COUNT(*) FROM comments WHERE comments.post_id = p.id) AS comment_count
        FROM 
            posts p
        LEFT JOIN 
            categories c ON p.category_id = c.id
        WHERE 
            p.user_id = ?
        ORDER BY 
            p.created_at DESC";

    $stmtPosts = $conn->prepare($sqlPosts);
    $stmtPosts->bind_param("i", $userId);
    $stmtPosts->execute();
    $resultPosts = $stmtPosts->get_result();

    $posts = [];
    while ($post = $resultPosts->fetch_assoc()) {
        $posts[] = [
            "user_id" => $userId,
            "id" => $post['id'],
            "title" => $post['title'],
            "content" => $post['content'],
            "created_at" => $post['created_at'],
            "photo" => $post['photo'],
            "watching_counter" => $post['watching_counter'],
            "share_counter" => $post['share_counter'],
            "isSolve" => $post['isSolve'],
            "category" => $post['category'],
            "comment_count" => $post['comment_count']
        ];
    }

    return [
        "status" => true,
        "user" => $user,
        "posts" => $posts
    ];
}

// cara penggunaan getProfileUserId

// $userId = 1; // Ganti dengan ID pengguna yang ingin diambil profilnya
// $profile = getProfileUserId($conn, $userId);

// if ($profile['status']) {
//     $user = $profile['user'];
//     echo "Profil Pengguna:<br>";
//     echo "Username: " . $user['username'] . "<br>";
//     echo "Fullname: " . $user['fullname'] . "<br>";
//     echo "Bio: " . $user['bio'] . "<br>";
//     echo "Email: " . $user['email'] . "<br>";
//     echo "Role: " . $user['role'] . "<br>";
//     echo "Joined at: " . $user['created_at'] . "<br>";
//     echo "Photo: <img src='" . $user['photo'] . "' alt='Photo' width='50'><br><br>";

//     echo "Postingan:<br>";
//     if (empty($profile['posts'])) {
//         echo "Tidak ada postingan yang ditemukan.<br>";
//     } else {
//         foreach ($profile['posts'] as $post) {
//             echo "Title: " . $post['title'] . "<br>";
//             echo "Created At: " . $post['created_at'] . "<br>";
//             echo "Category: " . $post['category'] . "<br>";
//             echo "Content: " . $post['content'] . "<br>";
//             echo "watching_counter: " . $post['watching_counter'] . "<br>";
//             echo "share_counter: " . $post['share_counter'] . "<br>";
//             echo "Comments: " . $post['comment_count'] . "<br>";
//             echo "<hr>";
//         }
//     }
// } else {
//     echo $profile['message'];
// }

// ifnu buat
function addPost($conn, $userId, $categoryId, $title, $content, $imgFile = null)
{
    if (empty($title) || empty($categoryId)) {
        return [
            "status" => false,
            "message" => "Title and category are required."
        ];
    }

    $imagePath = null;

    if ($imgFile && $imgFile['error'] == 0) {
        if ($imgFile['size'] > 2 * 1024 * 1024) {
            return [
                "status" => false,
                "message" => "Image size exceeds the maximum limit of 2 MB."
            ];
        }

        $imgInfo = getimagesize($imgFile['tmp_name']);
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];

        if ($imgInfo === false || !in_array($imgInfo['mime'], $allowedMimeTypes)) {
            return [
                "status" => false,
                "message" => "Image formats are not supported. Only JPG, PNG, and GIF are allowed."
            ];
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $fileExtension = strtolower(pathinfo($imgFile['name'], PATHINFO_EXTENSION));

        if (!in_array($fileExtension, $allowedExtensions)) {
            return [
                "status" => false,
                "message" => "File extensions are not supported. Only JPG, PNG, and GIF are allowed."
            ];
        }


        $uniqueName = uniqid("post_", true) . "." . $fileExtension;
        $uploadDir = 'img/posts/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true); // Membuat folder jika belum ada
        }
        $imagePath = $uploadDir . $uniqueName;

        if (!move_uploaded_file($imgFile['tmp_name'], $imagePath)) {
            return [
                "status" => false,
                "message" => "Failed to upload image."
            ];
        }
    }

    $sql = "INSERT INTO posts (user_id, category_id, title, content, photo) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisss", $userId, $categoryId, $title, $content, $imagePath);

    if ($stmt->execute()) {
        return [
            "status" => true,
            "message" => "Post added successfully."
        ];
    } else {
        return [
            "status" => false,
            "message" => "Failed to add post."
        ];
    }
}
// demonya ada difile demoAddPost.php

// ifnu buat
function addComment($conn, $postId, $userId, $content)
{
    if (empty($content)) {
        return [
            "status" => false,
            "message" => "Comments cannot be empty."
        ];
    }

    $sql = "INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $postId, $userId, $content);

    if ($stmt->execute()) {
        return [
            "status" => true,
            "message" => "Comment added successfully."
        ];
    } else {
        return [
            "status" => false,
            "message" => "Failed to add comment."
        ];
    }
}

// demonya ada difile demoAddComment.php

// ifnu buat
function search($conn, $keyword)
{
    $keyword = "%" . $conn->real_escape_string($keyword) . "%";

    $userQuery = "SELECT * FROM users WHERE username LIKE ? AND role = 'user'";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->bind_param("s", $keyword);
    $userStmt->execute();
    $userResult = $userStmt->get_result();

    $postQuery = "
        SELECT p.*, 
               u.id,
               u.username AS author_username, 
               u.fullname AS author_fullname,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count 
        FROM posts p
        JOIN users u ON p.user_id = u.id 
        WHERE p.title LIKE ?";
    $postStmt = $conn->prepare($postQuery);
    $postStmt->bind_param("s", $keyword);
    $postStmt->execute();
    $postResult = $postStmt->get_result();

    return [
        "users" => $userResult->fetch_all(MYSQLI_ASSOC),
        "posts" => $postResult->fetch_all(MYSQLI_ASSOC)
    ];
}

// demonya ada difile demoSearch.php

// ifnu buat
function incrementWatchingCounter($conn, $postId)
{
    $sql = "UPDATE posts SET watching_counter = watching_counter + 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $stmt->close();
}

// cara penggunaan 
// incrementWatchingCounter($conn, $postId);

// ifnu buat
function incrementShareCounter($conn, $postId)
{
    $sql = "UPDATE posts SET share_counter = share_counter + 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $stmt->close();
}

// cara penggunaan
// incrementShareCounter($conn, $postId);

// ifnu buat
function top5WatchingCounter($conn)
{
    $sql = "
        SELECT 
            p.id AS post_id,
            p.title,
            p.watching_counter,
            u.id,
            u.username AS author,
            u.fullname AS author_fullname,
            c.name AS category
        FROM 
            posts p
        JOIN 
            users u ON p.user_id = u.id
        JOIN 
            categories c ON p.category_id = c.id
        ORDER BY 
            p.watching_counter DESC
        LIMIT 5
    ";

    $result = $conn->query($sql);

    $posts = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $posts[] = [
                "id" => $row['post_id'],
                "title" => $row['title'],
                "watching_counter" => $row['watching_counter'],
                "author" => [
                    "username" => $row['author'],
                    "fullname" => $row['author_fullname']
                ],
                "category" => $row['category']
            ];
        }
    }

    return $posts;
}


// cara penggunaan top5WatchingCounter
// $top5Watching = top5WatchingCounter($conn);

// if (empty($top5Watching)) {
//     echo "Tidak ada postingan yang tersedia.";
// } else {
//     echo "Top 5 Watching Counter:<br>";
//     $index = 0;
//     foreach ($top5Watching as $post) {
//         echo "Top: " . ++$index . "<br>";
//         echo "Title: " . $post['title'] . "<br>";
//         echo "Watching Counter: " . $post['watching_counter'] . "<br>";
//         echo "Author: " . $post['author']['fullname'] . " (" . $post['author']['username'] . ")<br>";
//         echo "Category: " . $post['category'] . "<br><br>";
//     }
// }