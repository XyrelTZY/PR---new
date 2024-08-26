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
        $section = $_POST['section'];
        $grade_level = $_POST['grade_level'];
        $community_service_done = isset($_POST['community_service_done']) ? 1 : 0;
        $expelled = isset($_POST['expelled']) ? 1 : 0;

        $sql = "INSERT INTO students (name, building, section, grade_level, community_service_done, expelled) VALUES ('$name', '$building', '$section', '$grade_level', '$community_service_done', '$expelled')";
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
        $section = $_POST['section'];
        $grade_level = $_POST['grade_level'];
        $community_service_done = isset($_POST['community_service_done']) ? 1 : 0;
        $expelled = isset($_POST['expelled']) ? 1 : 0;

        $sql = "UPDATE students SET building='$building', section='$section', grade_level='$grade_level', community_service_done='$community_service_done', expelled='$expelled' WHERE id='$id'";
        if ($conn->query($sql) === TRUE) {
            $message = "Record updated successfully";
        } else {
            $message = "Error: " . $sql . "<br>" . $conn->error;
        }

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle search query
$search = isset($_GET['search']) ? $_GET['search'] : '';
$section_filter = isset($_GET['section']) ? $_GET['section'] : '';
$building_filter = isset($_GET['building']) ? $_GET['building'] : '';
$name_filter = isset($_GET['name']) ? $_GET['name'] : '';
$grade_filter = isset($_GET['grade']) ? $_GET['grade'] : '';

// Pagination settings
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Handle sorting
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Validate sort column and order
$valid_columns = ['id', 'name', 'building', 'section', 'grade_level', 'community_service_done', 'expelled'];
if (!in_array($sort_column, $valid_columns)) $sort_column = 'id';
if ($sort_order !== 'ASC' && $sort_order !== 'DESC') $sort_order = 'ASC';

// Fetch student data with search, sort, and pagination
$sql = "SELECT * FROM students WHERE name LIKE '%$search%' AND section LIKE '%$section_filter%' AND building LIKE '%$building_filter%' AND grade_level LIKE '%$grade_filter%' ORDER BY $sort_column $sort_order LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Fetch total number of records for pagination
$total_sql = "SELECT COUNT(*) as total FROM students WHERE name LIKE '%$search%' AND section LIKE '%$section_filter%' AND building LIKE '%$building_filter%' AND grade_level LIKE '%$grade_filter%'";
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
$recent_students_sql = "SELECT * FROM students ORDER BY id DESC LIMIT 5";
$recent_students_result = $conn->query($recent_students_sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.0.0/dist/tailwind.min.css" rel="stylesheet">
    <title>Student Community Service</title>
    <style>
        /* Modal Styles */
        .modal {
            display: none;
        }
        .modal.show {
            display: flex;
        }
        .modal-content {
            background-color: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            max-width: 600px;
            width: 100%;
        }
        .icon {
            font-size: 2rem;
        }
        .bg-community-service-done {
            background-color: #d4edda;
        }
        .bg-community-service-not-done {
            background-color: #f8d7da;
        }
        .bg-expelled {
            background-color: #f8d7da;
        }
        .bg-not-expelled {
            background-color: #d4edda;
        }
    </style>
</head>
<body class="bg-gray-600 font-sans">
    <!-- Header -->
    <header class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="text-lg font-bold text-black">Student's Dashboard</div>
            <nav class="flex space-x-4">
                <a href="index.php" class="bg-blue-400 text-black hover:bg-blue-700 px-3 py-2 rounded">Home</a>
                <a href="logout.php" class="bg-red-700 hover:bg-red-800 px-3 py-2 rounded">Logout</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto p-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Dashboard -->
            <section class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-2xl font-semibold mb-4">Dashboard</h2>
                <div class="flex flex-wrap gap-4">
                    <div class="bg-blue-500 text-white rounded-lg shadow-lg p-4 flex items-center space-x-4">
                        <i class="fas fa-user-graduate icon"></i>
                        <div>
                            <div class="text-3xl font-bold"><?php echo htmlspecialchars($students_count, ENT_QUOTES, 'UTF-8'); ?></div>
                            <p class="text-sm">Total Students</p>
                        </div>
                    </div>
                    <div class="bg-red-500 text-white rounded-lg shadow-lg p-4 flex items-center space-x-4">
                        <i class="fas fa-user-times icon"></i>
                        <div>
                            <div class="text-3xl font-bold"><?php echo htmlspecialchars($expelled_count, ENT_QUOTES, 'UTF-8'); ?></div>
                            <p class="text-sm">Total Expelled</p>
                        </div>
                    </div>
                    <div class="bg-green-500 text-white rounded-lg shadow-lg p-4 flex items-center space-x-4">
                        <i class="fas fa-hands-helping icon"></i>
                        <div>
                            <div class="text-3xl font-bold"><?php echo htmlspecialchars($community_service_count, ENT_QUOTES, 'UTF-8'); ?></div>
                            <p class="text-sm">Community Service Done</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Recent Students -->
            <section class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-2xl font-semibold mb-4">Recent Students</h2>
                <ul class="divide-y divide-gray-200">
                    <?php while ($row = $recent_students_result->fetch_assoc()): ?>
                        <li class="py-3 flex justify-between items-center">
                            <div>
                                <h3><strong>Name: </strong><?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <br>
                                <p><strong>Building: </strong><?php echo htmlspecialchars($row['building'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <br>
                                <p><strong>Section: </strong><?php echo htmlspecialchars($row['section'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <br>
                                <p><strong>Date: </strong> <?php echo date("F j, Y, g:i a", strtotime($row['created_at'])); ?></p>
                            </div>
                            <!-- <a href="?edit=<?php echo htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8'); ?>" class="text-blue-600 hover:text-blue-800">Edit</a> -->
                        </li>
                    <?php endwhile; ?>
                </ul>
            </section>
        </div>

        <!-- Student Table -->
        <section class="mt-8 bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-semibold mb-4">Student List</h2>
            <form method="GET" class="mb-4">
                <div class="flex flex-wrap gap-4">
                    <input type="text" name="search" placeholder="Search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2 ">
                    <input type="text" name="section" placeholder="Section" value="<?php echo htmlspecialchars($section_filter, ENT_QUOTES, 'UTF-8'); ?>" class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2">
                    <input type="text" name="building" placeholder="Building" value="<?php echo htmlspecialchars($building_filter, ENT_QUOTES, 'UTF-8'); ?>" class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2">
                    <input type="text" name="name" placeholder="Name" value="<?php echo htmlspecialchars($name_filter, ENT_QUOTES, 'UTF-8'); ?>" class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2">
                    <input type="text" name="grade" placeholder="Grade Level" value="<?php echo htmlspecialchars($grade_filter, ENT_QUOTES, 'UTF-8'); ?>" class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2">
                    <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">Search</button>
                </div>
            </form>
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="?sort=id&order=<?php echo $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="flex items-center">
                                ID
                                <?php if ($sort_column === 'id'): ?>
                                    <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $sort_order === 'ASC' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7'; ?>"></path>
                                    </svg>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider ">Name</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Building</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Grade Level</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Community Service Done</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Expelled</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
    <?php while ($row = $result->fetch_assoc()): ?>
        <?php
            // Default background color
            $bg_color = '';

            // Apply color based on community service and expelled status
            if ($row['expelled']) {
                $bg_color = 'bg-red-200 '; // Expelled - Red
            } elseif ($row['community_service_done']) {
                $bg_color = 'bg-green-200'; // Community service done - Green
            } else {
                $bg_color = 'bg-yellow-200'; // Community service not done - Yellow
            }
        ?>
        <tr class="<?php echo htmlspecialchars($bg_color, ENT_QUOTES, 'UTF-8'); ?>">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center font-bold"><?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center font-bold"><?php echo htmlspecialchars($row['building'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center font-bold"><?php echo htmlspecialchars($row['section'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center font-bold"><?php echo htmlspecialchars($row['grade_level'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center font-bold">
                <?php echo $row['community_service_done'] ? 'Yes' : 'No'; ?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center font-bold">
                <?php echo $row['expelled'] ? 'Yes' : 'No'; ?>
            </td>
        </tr>
    <?php endwhile; ?>
</tbody>

            </table>
            <div class="mt-4 flex justify-between items-center">
                <div class="text-sm text-gray-600">Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $limit, $total_records); ?> of <?php echo $total_records; ?> results</div>
                <div class="flex space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>" class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">Previous</a>
                    <?php endif; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>" class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <!-- Modal for Adding/Editing Student -->
    <div id="modal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 justify-center items-center">
        <div class="modal-content">
            <span class="close cursor-pointer float-right text-gray-500 hover:text-gray-700">&times;</span>
            <h2 class="text-2xl font-semibold mb-4">Modal Title</h2>
            <p class="text-gray-700">Modal Content</p>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-modal]').forEach(button => {
            button.addEventListener('click', () => {
                document.getElementById(button.getAttribute('data-modal')).classList.add('show');
            });
        });

        document.querySelectorAll('.modal .close').forEach(close => {
            close.addEventListener('click', () => {
                close.closest('.modal').classList.remove('show');
            });
        });

        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('show');
            }
        });
    </script>
</body>
</html>