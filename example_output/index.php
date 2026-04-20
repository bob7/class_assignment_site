<?php
session_start();
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
if (isset($_SESSION['userID'])) {
    $isAdmin = $_SESSION['userID'] === 'admin';
    if (!$isAdmin) {
        $uploadDir = $_SESSION['userID'] . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdfFile'])) {
            $selectedFile = $_POST['fileType'];
            $subDir = $uploadDir . $selectedFile . '/';
            if (!is_dir($subDir)) {
                mkdir($subDir, 0777, true);
            }
            $fileCount = count(glob($subDir . '*.pdf'));
            $date = date('ymdH');
            $newFileName = $_SESSION['userID'] . '-' . $selectedFile . '-' . $date . '-' . ($fileCount + 1) . '.pdf';
            $targetPath = $subDir . $newFileName;
            if (move_uploaded_file($_FILES['pdfFile']['tmp_name'], $targetPath)) {
                $uploadSuccess = true;
            } else {
                $uploadError = "File upload failed.";
            }
        }
        $subDirs = ['file1', 'file2', 'file3', 'file4', 'file5'];
        $uploadInfo = [];
        foreach ($subDirs as $dir) {
            $path = $uploadDir . $dir . '/';
            $files = glob($path . '*.pdf');
            if (empty($files)) {
                $uploadInfo[$dir] = ['time' => '-', 'size' => '-'];
            } else {
                $latestFile = array_reduce($files, function ($a, $b) {
                    return filemtime($a) > filemtime($b) ? $a : $b;
                });
                $time = date('y/m/d/H:i', filemtime($latestFile));
                $size = filesize($latestFile);
                $size = $size < 1024 * 1024 ? round($size / 1024, 1) . 'KB' : round($size / (1024 * 1024), 1) . 'MB';
                $uploadInfo[$dir] = ['time' => $time, 'size' => $size];
            }
        }
    } else {
        $lines = file('/home/cb5kqn0l/secure/pwd.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $users = [];
        foreach ($lines as $line) {
            list($id, $pwd) = explode(' ', trim($line));
            if ($id !== 'admin') {
                $users[] = $id;
            }
        }
        $subDirs = ['file1', 'file2', 'file3', 'file4', 'file5'];
        $adminUploadInfo = [];
        foreach ($users as $user) {
            $adminUploadInfo[$user] = [];
            foreach ($subDirs as $dir) {
                $path = $user . '/' . $dir . '/';
                $files = glob($path . '*.pdf');
                if (empty($files)) {
                    $adminUploadInfo[$user][$dir] = ['date' => '-', 'file' => ''];
                } else {
                    $latestFile = array_reduce($files, function ($a, $b) {
                        return filemtime($a) > filemtime($b) ? $a : $b;
                    });
                    $date = date('m/d', filemtime($latestFile));
                    $adminUploadInfo[$user][$dir] = ['date' => $date, 'file' => $latestFile];
                }
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isAdmin ? 'Admin Dashboard' : 'File Upload'; ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(to bottom, #e3f2fd, #bbdefb);
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: <?php echo $isAdmin ? '800px' : '500px'; ?>;
            text-align: center;
        }
        h2 {
            color: #1e88e5;
            margin-bottom: 20px;
        }
        h3 {
            color: #333;
            margin: 10px 0;
        }
        select, button {
            padding: 12px;
            margin: 10px 0;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            box-sizing: border-box;
        }
        select {
            background: #f5f5f5;
            color: #333;
        }
        button {
            background: #1e88e5;
            color: white;
            transition: background 0.3s;
        }
        button:hover {
            background: #1565c0;
        }
        .logout-btn {
            background: #e53935;
        }
        .logout-btn:hover {
            background: #b71c1c;
        }
        .drop-zone {
            width: 100%;
            height: 150px;
            border: 2px dashed #1e88e5;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            margin: 20px 0;
            background: #f5f5f5;
            color: #666;
            font-size: 16px;
            transition: all 0.3s;
        }
        .drop-zone.dragover {
            background: #bbdefb;
            border-color: #1565c0;
            color: #1565c0;
        }
        .drop-zone.ready {
            background: #c8e6c9;
            border-color: #4caf50;
            color: #4caf50;
        }
        .success {
            color: #4caf50;
            font-weight: bold;
            margin-top: 20px;
        }
        .error {
            color: #e53935;
            font-weight: bold;
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background: #1e88e5;
            color: white;
        }
        tr:nth-child(even) {
            background: #f5f5f5;
        }
        a {
            color: #1e88e5;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><?php echo $isAdmin ? 'Admin Dashboard' : 'File Upload'; ?></h2>
        <h3>Welcome, <?php echo htmlspecialchars($_SESSION['userID']); ?>!</h3>
        <form method="post">
            <button type="submit" name="logout" class="logout-btn">Logout</button>
        </form>
        <?php if ($isAdmin) { ?>
            <table>
                <tr>
                    <th></th>
                    <?php foreach ($users as $user) { ?>
                        <th><?php echo htmlspecialchars($user); ?></th>
                    <?php } ?>
                </tr>
                    <tr>
                        <td>File 1</td>
                        <?php foreach ($users as $user) { ?>
                            <td>
                                <?php
                                $date = $adminUploadInfo[$user]['file1']['date'];
                                $file = $adminUploadInfo[$user]['file1']['file'];
                                if ($date !== '-' && file_exists($file)) {
                                    echo '<a href="' . htmlspecialchars($file) . '" target="_blank">' . $date . '</a>';
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <td>File 2</td>
                        <?php foreach ($users as $user) { ?>
                            <td>
                                <?php
                                $date = $adminUploadInfo[$user]['file2']['date'];
                                $file = $adminUploadInfo[$user]['file2']['file'];
                                if ($date !== '-' && file_exists($file)) {
                                    echo '<a href="' . htmlspecialchars($file) . '" target="_blank">' . $date . '</a>';
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <td>File 3</td>
                        <?php foreach ($users as $user) { ?>
                            <td>
                                <?php
                                $date = $adminUploadInfo[$user]['file3']['date'];
                                $file = $adminUploadInfo[$user]['file3']['file'];
                                if ($date !== '-' && file_exists($file)) {
                                    echo '<a href="' . htmlspecialchars($file) . '" target="_blank">' . $date . '</a>';
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <td>File 4</td>
                        <?php foreach ($users as $user) { ?>
                            <td>
                                <?php
                                $date = $adminUploadInfo[$user]['file4']['date'];
                                $file = $adminUploadInfo[$user]['file4']['file'];
                                if ($date !== '-' && file_exists($file)) {
                                    echo '<a href="' . htmlspecialchars($file) . '" target="_blank">' . $date . '</a>';
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <td>File 5</td>
                        <?php foreach ($users as $user) { ?>
                            <td>
                                <?php
                                $date = $adminUploadInfo[$user]['file5']['date'];
                                $file = $adminUploadInfo[$user]['file5']['file'];
                                if ($date !== '-' && file_exists($file)) {
                                    echo '<a href="' . htmlspecialchars($file) . '" target="_blank">' . $date . '</a>';
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                        <?php } ?>
                    </tr>
            </table>
        <?php } else { ?>
            <table>
                <tr>
                    <th>Upload</th>
                    <th>Time</th>
                    <th>Size</th>
                </tr>
                <tr>
                    <td>File 1</td>
                    <td><?php echo $uploadInfo['file1']['time']; ?></td>
                    <td><?php echo $uploadInfo['file1']['size']; ?></td>
                </tr>
                <tr>
                    <td>File 2</td>
                    <td><?php echo $uploadInfo['file2']['time']; ?></td>
                    <td><?php echo $uploadInfo['file2']['size']; ?></td>
                </tr>
                <tr>
                    <td>File 3</td>
                    <td><?php echo $uploadInfo['file3']['time']; ?></td>
                    <td><?php echo $uploadInfo['file3']['size']; ?></td>
                </tr>
                <tr>
                    <td>File 4</td>
                    <td><?php echo $uploadInfo['file4']['time']; ?></td>
                    <td><?php echo $uploadInfo['file4']['size']; ?></td>
                </tr>
                <tr>
                    <td>File 5</td>
                    <td><?php echo $uploadInfo['file5']['time']; ?></td>
                    <td><?php echo $uploadInfo['file5']['size']; ?></td>
                </tr>
            </table>
            <form method="post" enctype="multipart/form-data">
                <select name="fileType" required>
                    <option value="" disabled selected>Select file type</option>
                <option value="file1">File 1</option>
                <option value="file2">File 2</option>
                <option value="file3">File 3</option>
                <option value="file4">File 4</option>
                <option value="file5">File 5</option>
                </select>
                <div class="drop-zone" id="dropZone">
                    Drag and drop a PDF file here or click to select
                </div>
                <input type="file" id="fileInput" name="pdfFile" accept=".pdf" style="display: none;" required>
                <button type="submit">Upload File</button>
            </form>
            <?php if (isset($uploadSuccess) && $uploadSuccess) { ?>
                <p class="success">File uploaded successfully!</p>
            <?php } elseif (isset($uploadError)) { ?>
                <p class="error"><?php echo $uploadError; ?></p>
            <?php } ?>
        <?php } ?>
    </div>
    <script>
        window.addEventListener('unload', function () {
            navigator.sendBeacon('logout.php');
        });
        <?php if (!$isAdmin) { ?>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        dropZone.addEventListener('click', () => fileInput.click());
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });
        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            fileInput.files = e.dataTransfer.files;
            updateDropZone();
        });
        fileInput.addEventListener('change', updateDropZone);
        function updateDropZone() {
            if (fileInput.files.length) {
                dropZone.textContent = `Ready to upload: ${fileInput.files[0].name}`;
                dropZone.classList.add('ready');
            } else {
                dropZone.textContent = 'Drag and drop a PDF file here or click to select';
                dropZone.classList.remove('ready');
            }
        }
        <?php } ?>
    </script>
</body>
</html>
<?php
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $password = trim($_POST['password']);
    $lines = file('/home/cb5kqn0l/secure/pwd.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $userID = null;
    foreach ($lines as $line) {
        list($id, $pwd) = explode(' ', trim($line));
        if ($pwd === $password) {
            $userID = $id;
            break;
        }
    }
    if ($userID) {
        $_SESSION['userID'] = $userID;
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(to bottom, #e3f2fd, #bbdefb);
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        h2 {
            color: #1e88e5;
            margin-bottom: 20px;
        }
        input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #1e88e5;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #1565c0;
        }
        .error {
            color: #e53935;
            font-weight: bold;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <form method="post">
            <input type="password" name="password" placeholder="Enter password" required>
            <button type="submit">Login</button>
        </form>
        <?php if (isset($error)) { ?>
            <p class="error"><?php echo $error; ?></p>
        <?php } ?>
    </div>
</body>
</html>
