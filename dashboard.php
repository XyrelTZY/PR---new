<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "school";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Clear students who were added more than 24 hours ago
$sql = "DELETE FROM students WHERE created_at < NOW() - INTERVAL 1 DAY";
$conn->query($sql);

// Handle form submission to add or update student
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_student'])) {
        $name = $_POST['name'];
        $building = $_POST['building'];
        $track = $_POST['track'];
        $grade_level = $_POST['grade_level'];
        $community_service_done = isset($_POST['community_service_done']) ? 1 : 0;
        $expelled = isset($_POST['expelled']) ? 1 : 0;

        $sql = "INSERT INTO students (name, building, track, grade_level, community_service_done, expelled) VALUES ('$name', '$building', '$track', '$grade_level', '$community_service_done', '$expelled')";
        if ($conn->query($sql) === TRUE) {
            $message = "New record created successfully";
        } else {
            $message = "Error: " . $sql . "<br>" . $conn->error;
        }

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST['update_student'])) {
        $id = $_POST['id'];
        $building = $_POST['building'];
        $track = $_POST['track'];
        $grade_level = $_POST['grade_level'];
        $community_service_done = isset($_POST['community_service_done']) ? 1 : 0;
        $expelled = isset($_POST['expelled']) ? 1 : 0;

        $sql = "UPDATE students SET building='$building', track='$track', grade_level='$grade_level', community_service_done='$community_service_done', expelled='$expelled' WHERE id='$id'";
        if ($conn->query($sql) === TRUE) {
            $message = "Record updated successfully";
        } else {
            $message = "Error: " . $sql . "<br>" . $conn->error;
        }

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle search query and filters
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$track_filter = isset($_GET['track']) ? $conn->real_escape_string($_GET['track']) : '';
$building_filter = isset($_GET['building']) ? $conn->real_escape_string($_GET['building']) : '';
$name_filter = isset($_GET['name']) ? $conn->real_escape_string($_GET['name']) : '';
$grade_filter = isset($_GET['grade']) ? $conn->real_escape_string($_GET['grade']) : '';

// Pagination settings
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Handle sorting
$sort_column = isset($_GET['sort']) ? $conn->real_escape_string($_GET['sort']) : 'id';
$sort_order = isset($_GET['order']) ? $conn->real_escape_string($_GET['order']) : 'ASC';

// Validate sort column and order
$valid_columns = ['id', 'name', 'building', 'track', 'grade_level', 'community_service_done', 'expelled'];
if (!in_array($sort_column, $valid_columns)) $sort_column = 'id';
if ($sort_order !== 'ASC' && $sort_order !== 'DESC') $sort_order = 'ASC';

// Fetch student data with search, sort, and pagination
$sql = "SELECT * FROM students 
        WHERE (name LIKE '%$search%' 
        OR track LIKE '%$search%' 
        OR building LIKE '%$search%' 
        OR grade_level LIKE '%$search%') 
        ORDER BY $sort_column $sort_order 
        LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Fetch total number of records for pagination
$total_sql = "SELECT COUNT(*) as total FROM students 
              WHERE (name LIKE '%$search%' 
              OR track LIKE '%$search%' 
              OR building LIKE '%$search%' 
              OR grade_level LIKE '%$search%')";
$total_result = $conn->query($total_sql);
$total_row = $total_result->fetch_assoc();
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $limit);

// Handle form to fetch student for editing
$edit_student = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $sql = "SELECT * FROM students WHERE id='$id'";
    $edit_student = $conn->query($sql)->fetch_assoc();
}

// Dashboard Data
$students_count = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$expelled_count = $conn->query("SELECT COUNT(*) as count FROM students WHERE expelled = 1")->fetch_assoc()['count'];
$community_service_count = $conn->query("SELECT COUNT(*) as count FROM students WHERE community_service_done = 1")->fetch_assoc()['count'];
$recent_students_sql = "SELECT name, track, created_at, building, grade_level, community_service_done, expelled, status FROM students 
                        WHERE (name LIKE '%$search%' 
                        OR track LIKE '%$search%' 
                        OR building LIKE '%$search%' 
                        OR grade_level LIKE '%$search%') 
                        ORDER BY id DESC LIMIT 5";
$recent_students_result = $conn->query($recent_students_sql);

$conn->close();

// while ($row = $recent_students_result->fetch_assoc()) {
//     // Output the entire row for debugging
//     var_dump($row);
// }
?>





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap" />
    <link rel="shortcut icon" href="Zamboanga_del_Sur_National_Highschool.png" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Students Guidance</title>
</head>
<body>
    <nav>
        <div class="logo">
            <div class="logo-icon">
                <img src="Zamboanga_del_Sur_National_Highschool.png" alt="" width="70px" height="70px">
            </div>
            <span class="logo_name">Guidance</span>
        </div>
        <div class="menu-items">
    <ul class="nav-links">
        <li>
            <a href="dashboard.php?filter=dashboard">
                <i class="fab fa-microsoft"></i>
                <span class="link-name">Dashboard</span>
            </a>
        </li>
        <!-- Grade 11 -->
<li class="dropdown grade-11">
    <a href="#">
        <i class="fas fa-clapperboard"></i>
        <span class="link-name">Grade 11</span>
        <i class="fas fa-chevron-down dropdown-icon"></i>
    </a>
    <ul class="dropdown-menu">
        <li class="dropdown-submenu">
            <a href="dashboard.php?grade=11&building=Building%20A">Building A</a>
            <ul class="submenu">
                <li><a href="dashboard.php?grade=11&building=Building%20A&track=HUMMS">HUMMS</a></li>
                <li><a href="dashboard.php?grade=11&building=Building%20A&track=ABM">ABM</a></li>
            </ul>
        </li>
        <li class="dropdown-submenu">
            <a href="dashboard.php?grade=11&building=Building%20C">Building C</a>
            <ul class="submenu 11C ">
                <li><a href="dashboard.php?grade=11&building=Building%20C&track=STEM">STEM</a></li>
                <li><a href="dashboard.php?grade=11&building=Building%20C&track=SPORTS">SPORTS</a></li>
                <li><a href="dashboard.php?grade=11&building=Building%20C&track=ARTS%20&%20DESIGN">ARTS & DESIGN</a></li>
                <li><a href="dashboard.php?grade=11&building=Building%20C&track=TVL">TVL</a></li>
            </ul>
        </li>
    </ul>
</li>
<!-- Grade 12 -->
<li class="dropdown grade-12">
    <a href="#">
        <i class="fas fa-clapperboard"></i>
        <span class="link-name">Grade 12</span>
        <i class="fas fa-chevron-down dropdown-icon"></i>
    </a>
    <ul class="dropdown-menu">
        <li class="dropdown-submenu">
            <a href="dashboard.php?grade=12&building=Building%20A">Building A</a>
            <ul class="submenu">
                <li><a href="dashboard.php?grade=12&building=Building%20A&track=HUMMS">HUMMS</a></li>
                <li><a href="dashboard.php?grade=12&building=Building%20A&track=ABM">ABM</a></li>
            </ul>
        </li>
        <li class="dropdown-submenu">
            <a href="dashboard.php?grade=12&building=Building%20C">Building C</a>
            <ul class="submenu 12C">
                <li><a href="dashboard.php?grade=12&building=Building%20C&track=STEM">STEM</a></li>
                <li><a href="dashboard.php?grade=12&building=Building%20C&track=SPORTS">SPORTS</a></li>
                <li><a href="dashboard.php?grade=12&building=Building%20C&track=ARTS%20&%20DESIGN">ARTS & DESIGN</a></li>
                <li><a href="dashboard.php?grade=12&building=Building%20C&track=TVL">TVL</a></li>
            </ul>
        </li>
    </ul>
</li>
        <!-- Other menu items -->
        <li>
            <a href="#">
                <i class="fas fa-chart-simple"></i>
                <span class="link-name">Analytics</span>
            </a>
        </li>
        <li>
            <a href="#">
                <i class="fas fa-message"></i>
                <span class="link-name">Comments</span>
            </a>
        </li>
        <li>
            <a href="#">
                <i class="fas fa-closed-captioning"></i>
                <span class="link-name">Subtitles</span>
            </a>
        </li>
        <li>
            <a href="#">
                <i class="fas fa-copyright"></i>
                <span class="link-name">Copyright</span>
            </a>
        </li>
        <li>
            <a href="#">
                <i class="fas fa-sack-dollar"></i>
                <span class="link-name">Earn</span>
            </a>
        </li>
        <li>
            <a href="#">
                <i class="fas fa-square-pen"></i>
                <span class="link-name">Customization</span>
            </a>
        </li>
    </ul>

    <ul class="logout-mode">
        <li>
            <a href="#">
                <i class="fas fa-right-to-bracket"></i>
                <span class="link-name">Logout</span>
            </a>
        </li>

        <li class="mode">
            <a href="#">
                <i class="fas fa-moon"></i>
                <span class="link-name">Dark Mode</span>
            </a>

            <div class="mode-toggle">
                <span class="switch"></span>
            </div>
        </li>
    </ul>
</div>
</nav>
<!-- Header -->
<section class="dashboard">
    <div class="top flex items-center justify-between p-4 bg-gray-800 text-white">
        <!-- Sidebar toggle -->
        <div class="sidebar-toggle">
        <i class="fas fa-bars text-xl cursor-pointer" id="sidebar-icon"></i>
        </div>
        <!-- Search form -->
        <form action="dashboard.php" method="GET" class="flex items-center rounded-md p-2 w-full max-w-lg">
    <div class="relative w-full">
        <i class="fas fa-search absolute inset-y-0 left-2 text-gray-400 flex items-center pl-2"></i>
        <input type="text" name="search" id="search-input" placeholder="Search by name..."
            class="pl-[40px] py-2 bg-gray-800 text-white rounded-md outline-none focus:ring-2 focus:ring-blue-500 transition duration-200 ease-in-out w-full"
            value="<?php echo htmlspecialchars(isset($_GET['search']) ? $_GET['search'] : '', ENT_QUOTES, 'UTF-8'); ?>" />
    </div>
</form>


        <!-- Profile image -->
        <img src="https://raw.githubusercontent.com/Tivotal/Responsive-Admin-Dashboard-Panel-using-HTML-CSS-JavaScript/main/profile.jpg"
            alt="" class="w-10 h-10 rounded-full">
    </div>

        <!-- Dashboard -->
        <div class="dash-content">
            <div class="overview">
                <div class="title">
                    <i class="fas fa-gauge"></i>
                    <span class="text">Dashboard</span>
                </div>
    
                <div class="boxes">
                    <div class="box box1">
                        <i class="fas fa-user"></i>
                        <br>
                        <span class="text">Total Students</span>
                        <div class="text-3xl font-bold"><?php echo htmlspecialchars($students_count, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <div class="box box2">
                        <i class="fas fa-eye"></i>
                        <br>
                        <span class="text">Community Service</span>
                        <div class="text-3xl font-bold"><?php echo htmlspecialchars($community_service_count, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <div class="box box3">
                        <i class="fas fa-clock"></i>
                        <br>
                        <span class="text">Expelled/Dropout</span>
                        <div class="text-3xl font-bold"><?php echo htmlspecialchars($expelled_count, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </div>
            </div>

<!-- Recent Students -->
<div class="activity">
    <div class="title">
        <i class="fas fa-clock"></i>
        <span class="text">Recent Students</span>
    </div>
    <div class="activity-data">
        <div class="data names">
            <span class="data-title">Name</span>
            <?php while ($row = $recent_students_result->fetch_assoc()): ?>
                <span class="data-list"><?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endwhile; ?>
        </div>
        <div class="data section">
            <span class="data-title">Track</span>
            <?php 
            // Reset result pointer to fetch the data again
            $recent_students_result->data_seek(0); 
            while ($row = $recent_students_result->fetch_assoc()): ?>
                <span class="data-list"><?php echo htmlspecialchars($row['track'], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endwhile; ?>
        </div>
        <div class="data building">
            <span class="data-title">Building</span>
            <?php 
            // Reset result pointer to fetch the data again
            $recent_students_result->data_seek(0); 
            while ($row = $recent_students_result->fetch_assoc()): ?>
                <span class="data-list"><?php echo htmlspecialchars($row['building'], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endwhile; ?>
        </div>
        <div class="data grade-level">
    <span class="data-title">Grade Level</span>
    <?php 
    // Reset result pointer to fetch the data again
    $recent_students_result->data_seek(0); 
    while ($row = $recent_students_result->fetch_assoc()): 
        // Check if 'grade_level' is set and not null
        $grade_level = isset($row['grade_level']) ? htmlspecialchars($row['grade_level'], ENT_QUOTES, 'UTF-8') : 'N/A';
    ?>
        <span class="data-list"><?php echo $grade_level; ?></span>
    <?php endwhile; ?>
</div>

        <div class="data community-service">
            <span class="data-title">Community Service</span>
            <?php 
            // Reset result pointer to fetch the data again
            $recent_students_result->data_seek(0); 
            while ($row = $recent_students_result->fetch_assoc()): ?>
                <span class="data-list" style="color: <?php echo $row['community_service_done'] ? 'green' : 'yellow'; ?>;">
                    <?php echo $row['community_service_done'] ? 'Done' : 'Not Done'; ?>
                </span>
            <?php endwhile; ?>
        </div>
        <div class="data expelled">
            <span class="data-title">Expelled</span>
            <?php 
            // Reset result pointer to fetch the data again
            $recent_students_result->data_seek(0); 
            while ($row = $recent_students_result->fetch_assoc()): ?>
                <span class="data-list" style="color: <?php echo $row['expelled'] ? 'red' : 'black'; ?>;">
                    <?php echo $row['expelled'] ? 'Yes' : 'No'; ?>
                </span>
            <?php endwhile; ?>
        </div>
        <div class="data status">
            <span class="data-title">Status</span>
            <?php 
            // Reset result pointer to fetch the data again
            $recent_students_result->data_seek(0); 
            while ($row = $recent_students_result->fetch_assoc()): ?>
                <span class="data-list"><?php echo htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endwhile; ?>
        </div>
        <div class="data date">
            <span class="data-title">Date</span>
            <?php 
            // Reset result pointer to fetch the data again
            $recent_students_result->data_seek(0); 
            while ($row = $recent_students_result->fetch_assoc()): ?>
                <span class="data-list"><?php echo htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endwhile; ?>
        </div>
    </div>
</div>

    </section>
<script src="Dashboard.js">
    
</script>
</body>
</html>