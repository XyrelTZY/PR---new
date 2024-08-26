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

// Handle form submission to add new student
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_student'])) {
        $name = $_POST['name'];
        $building = $_POST['building'];
        $track = $_POST['track'];
        $grade_level = $_POST['grade_level'];
        $offense = $_POST['offense'];
        $community_service_done = isset($_POST['community_service_done']) ? 1 : 0;
        $expelled = isset($_POST['expelled']) ? 1 : 0;

        $sql = "INSERT INTO students (name, building, track, grade_level, offense, community_service_done, expelled) VALUES ('$name', '$building', '$track', '$grade_level', '$offense', '$community_service_done', '$expelled')";
        if ($conn->query($sql) === TRUE) {
            $message = "New record created successfully";
        } else {
            $message = "Error: " . $sql . "<br>" . $conn->error;
        }
        
        // Redirect to the same page to prevent re-submission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST['update_student'])) {
        $id = $_POST['id'];
        $building = $_POST['building'];
        $track = $_POST['track'];
        $grade_level = $_POST['grade_level'];
        $offense = $_POST['offense'];
        $community_service_done = isset($_POST['community_service_done']) ? 1 : 0;
        $expelled = isset($_POST['expelled']) ? 1 : 0;

        $sql = "UPDATE students SET building='$building', track='$track', grade_level='$grade_level', offense='$offense', community_service_done='$community_service_done', expelled='$expelled' WHERE id='$id'";
        if ($conn->query($sql) === TRUE) {
            $message = "Record updated successfully";
        } else {
            $message = "Error: " . $sql . "<br>" . $conn->error;
        }

        // Redirect to the same page to prevent re-submission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle search query
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination settings
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Handle sorting
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Validate sort column and order
$valid_columns = ['id', 'name', 'building', 'track', 'grade_level', 'offense', 'community_service_done', 'expelled'];
if (!in_array($sort_column, $valid_columns)) $sort_column = 'id';
if ($sort_order !== 'ASC' && $sort_order !== 'DESC') $sort_order = 'ASC';

// Fetch student data with search, sort, and pagination
$sql = "SELECT * FROM students WHERE name LIKE '%$search%' ORDER BY $sort_column $sort_order LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Fetch total number of records for pagination
$total_sql = "SELECT COUNT(*) as total FROM students WHERE name LIKE '%$search%'";
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

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Modal Container */
        .modal {
            visibility: hidden;
            opacity: 0;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
            transition: opacity 0.3s ease;
        }
        /* Show Modal */
        .modal.show {
            visibility: visible;
            opacity: 1;
        }
        /* Modal Content */
        .modal-content {
            background-color: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            max-width: 600px;
            width: 100%;
        }
    </style>
    <title>Student Community Service</title>
</head>
<body class="bg-gray-600">
    <!-- Header -->
    <header class="bg-blue-600 text-white p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <!-- Navigation Links -->
            <nav class="flex space-x-4">
                <a href="dashboard.php" class="bg-blue-400 text-black texthover:bg-blue-700 px-3 py-2 rounded">Dashboard</a>
                <a href="logout.php" class="bg-red-700 hover:bg-blue-700 hover:font-bold px-3 py-2 rounded ">Logout</a>
            </nav>
            <!-- Admin Title -->
            <div class="text-lg font-bold">Admin</div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="p-8">
        <div class="max-w-7xl mx-auto flex flex-col lg:flex-row space-y-8 lg:space-y-0 lg:space-x-8">
            <!-- Student List -->
            <div class="w-full lg:w-2/3 relative">
                <!-- Search Form -->
                <form id="searchForm" method="get" class="bg-white p-6 rounded-lg shadow-md mb-6 flex items-center space-x-4">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="border border-gray-300 rounded-md p-2 w-full" placeholder="Search students...">
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Search</button>
                </form>

                <!-- Students Table -->
                <table class="min-w-full divide-y divide-gray-600">
                    <thead class="bg-gray-50 ">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <a href="?search=<?php echo htmlspecialchars($search); ?>&sort=id&order=<?php echo $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="text-blue-500 hover:underline">ID</a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <a href="?search=<?php echo htmlspecialchars($search); ?>&sort=name&order=<?php echo $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="text-blue-500 hover:underline">Name</a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <a href="?search=<?php echo htmlspecialchars($search); ?>&sort=building&order=<?php echo $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="text-blue-500 hover:underline">Building</a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <a href="?search=<?php echo htmlspecialchars($search); ?>&sort=track&order=<?php echo $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="text-blue-500 hover:underline">track</a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <a href="?search=<?php echo htmlspecialchars($search); ?>&sort=grade_level&order=<?php echo $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="text-blue-500 hover:underline">Grade Level</a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <a href="?search=<?php echo htmlspecialchars($search); ?>&sort=offense&order=<?php echo $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="text-blue-500 hover:underline">Community Service Done</a>
                            </th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-blue-500 hover:underline tracking-wider">Expelled</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900"><?php echo $row['id']; ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($row['building']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($row['track']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($row['grade_level']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo $row['community_service_done'] ? 'Yes' : 'No'; ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo $row['expelled'] ? 'Yes' : 'No'; ?></td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-500 space-x-4">
                                        <a href="?edit=<?php echo $row['id']; ?>" class="text-blue-600 hover:underline">Edit</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="px-6 py-4 text-center text-gray-500">No records found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="mt-6 flex justify-between items-center">
                    <div>
                        <?php if ($page > 1): ?>
                            <a href="?search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>&sort=<?php echo $sort_column; ?>&order=<?php echo $sort_order; ?>" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Previous</a>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if ($page < $total_pages): ?>
                            <a href="?search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>&sort=<?php echo $sort_column; ?>&order=<?php echo $sort_order; ?>" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Add Student Form -->
<div class="w-full lg:w-1/3">
    <form method="post" class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-lg font-semibold mb-4 bg-blue-300 w-auto text-center h-10 pt-1 rounded-lg">Add New Student</h2>
        <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
            <input type="text" id="name" name="name" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
        </div>
        <div class="mb-4">
            <label for="building" class="block text-sm font-medium text-gray-700">Building</label>
            <input type="text" id="building" name="building" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
        </div>
        <div class="mb-4">
            <label for="track" class="block text-sm font-medium text-gray-700">track</label>
            <input type="text" id="track" name="track" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
        </div>
        <div class="mb-4">
            <label for="grade_level" class="block text-sm font-medium text-gray-700">Grade Level</label>
            <input type="text" id="grade_level" name="grade_level" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
        </div>
        <div class="mb-4">
            <label for="offense" class="block text-sm font-medium text-gray-700">Offense</label>
            <input type="text" id="offense" name="offense" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
        </div>
        <div class="mb-4 flex items-center">
            <input type="checkbox" id="community_service_done" name="community_service_done" class="mr-2">
            <label for="community_service_done" class="text-sm font-medium text-gray-700">Community Service Done</label>
        </div>
        <div class="mb-4 flex items-center">
            <input type="checkbox" id="expelled" name="expelled" class="mr-2">
            <label for="expelled" class="text-sm font-medium text-gray-700">Expelled</label>
        </div>
        <input type="hidden" name="add_student" value="1">
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Add Student</button>
    </form>
</div>

        </div>
    </main>
    
    <!-- Edit Student Modal -->
    <div id="updateModal" class="modal">
        <div class="modal-content">
            <h2 class="text-lg font-semibold mb-4">Edit Student</h2>
            <form id="updateForm" method="post">
                <input type="hidden" id="studentId" name="id">
                <div class="mb-4">
                    <label for="modal_building" class="block text-sm font-medium text-gray-700">Building</label>
                    <input type="text" id="modal_building" name="building" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
                </div>
                <div class="mb-4">
                    <label for="modal_track" class="block text-sm font-medium text-gray-700">Track</label>
                    <input type="text" id="modal_track" name="track" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
                </div>
                <div class="mb-4">
                    <label for="modal_grade_level" class="block text-sm font-medium text-gray-700">Grade Level</label>
                    <input type="text" id="modal_grade_level" name="grade_level" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
                </div>
                <div class="mb-4">
                    <label for="modal_offense" class="block text-sm font-medium text-gray-700">Offense</label>
                    <input type="text" id="modal_offense" name="offense" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
                </div>
                <div class="mb-4 flex items-center">
                    <input type="checkbox" id="modal_community_service_done" name="community_service_done" class="mr-2">
                    <label for="modal_community_service_done" class="text-sm font-medium text-gray-700">Community Service Done</label>
                </div>
                <div class="mb-4 flex items-center">
                    <input type="checkbox" id="modal_expelled" name="expelled" class="mr-2">
                    <label for="modal_expelled" class="text-sm font-medium text-gray-700">Expelled</label>
                </div>
                <button type="submit" name="update_student" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Update Student</button>
                <button type="button" id="closeModal" class="ml-4 bg-gray-300 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-400">Close</button>
            </form>
        </div>
    </div>

    <script>
        // Modal functionality
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('updateModal');
            const closeModal = document.getElementById('closeModal');
            const urlParams = new URLSearchParams(window.location.search);
            const editId = urlParams.get('edit');
            
            if (editId) {
                modal.classList.add('show');
                fetch(`fetch_student.php?id=${editId}`)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('studentId').value = data.id;
                        document.getElementById('modal_building').value = data.building;
                        document.getElementById('modal_track').value = data.track;
                        document.getElementById('modal_grade_level').value = data.grade_level;
                        document.getElementById('modal_offense').value = data.offense;
                        document.getElementById('modal_community_service_done').checked = data.community_service_done;
                        document.getElementById('modal_expelled').checked = data.expelled;
                    });
            }
            
            closeModal.addEventListener('click', () => {
                modal.classList.remove('show');
                history.replaceState(null, '', window.location.pathname);
            });
        });

        function showModal(id) {
            window.location.href = `?edit=${id}`;
        }

        function sortTable(column) {
            const urlParams = new URLSearchParams(window.location.search);
            const currentSort = urlParams.get('sort') || 'id';
            const currentOrder = urlParams.get('order') || 'ASC';
            const newOrder = (currentSort === column && currentOrder === 'ASC') ? 'DESC' : 'ASC';
            urlParams.set('sort', column);
            urlParams.set('order', newOrder);
            window.location.search = urlParams.toString();
        }
    </script>
</body>
</html>