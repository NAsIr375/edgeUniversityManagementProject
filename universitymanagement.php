<?php
session_start();

// Database Connection
$conn = new mysqli("localhost", "root", "", "university_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// User Authentication
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $result = $conn->query("SELECT * FROM users WHERE email='$email'");
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if ($password == $user['password']) { // Store hashed passwords in production!
            $_SESSION['user'] = $user;
        } else {
            echo "<p style='color:red;'>Invalid password!</p>";
        }
    } else {
        echo "<p style='color:red;'>User not found!</p>";
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
}

// If not logged in, show login form
if (!isset($_SESSION['user'])) { ?>
    <html>
    <head>
        <title>University Management System</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; }
            form { width: 300px; margin: auto; padding: 20px; border: 1px solid #ccc; }
            input { width: 100%; margin: 10px 0; padding: 10px; }
            button { background: blue; color: white; padding: 10px; width: 100%; border: none; }
        </style>
    </head>
    <body>
        <h2>Login</h2>
        <form method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Login</button>
        </form>
    </body>
    </html>
    <?php exit();
}

// Fetch Logged-in User Details
$user = $_SESSION['user'];
$role = $user['role'];

// Admin Panel
if ($role == "admin") {
    if (isset($_POST['add_course'])) {
        $name = $_POST['name'];
        $conn->query("INSERT INTO courses (name) VALUES ('$name')");
    }
    $courses = $conn->query("SELECT * FROM courses");
    ?>
    <html>
    <head>
        <title>Admin Dashboard</title>
    </head>
    <body>
        <h2>Welcome, Admin</h2>
        <a href="index.php?logout=1">Logout</a>
        <h3>Add Course</h3>
        <form method="POST">
            <input type="text" name="name" placeholder="Course Name" required>
            <button type="submit" name="add_course">Add Course</button>
        </form>
        <h3>Courses</h3>
        <ul>
            <?php while ($course = $courses->fetch_assoc()) {
                echo "<li>" . $course['name'] . "</li>";
            } ?>
        </ul>
    </body>
    </html>
    <?php
}

// Student Panel
if ($role == "student") {
    if (isset($_POST['enroll'])) {
        $course_id = $_POST['course_id'];
        $student_id = $user['id'];
        $conn->query("INSERT INTO enrollments (student_id, course_id) VALUES ($student_id, $course_id)");
    }
    $courses = $conn->query("SELECT * FROM courses");
    ?>
    <html>
    <head>
        <title>Student Dashboard</title>
    </head>
    <body>
        <h2>Welcome, Student</h2>
        <a href="index.php?logout=1">Logout</a>
        <h3>Enroll in a Course</h3>
        <form method="POST">
            <select name="course_id">
                <?php while ($course = $courses->fetch_assoc()) {
                    echo "<option value='" . $course['id'] . "'>" . $course['name'] . "</option>";
                } ?>
            </select>
            <button type="submit" name="enroll">Enroll</button>
        </form>
    </body>
    </html>
    <?php
}

// Faculty Panel
if ($role == "faculty") {
    if (isset($_POST['assign_grade'])) {
        $enrollment_id = $_POST['enrollment_id'];
        $grade = $_POST['grade'];
        $conn->query("UPDATE enrollments SET grade='$grade' WHERE id=$enrollment_id");
    }
    $enrollments = $conn->query("SELECT enrollments.id, users.name, courses.name AS course_name 
                                 FROM enrollments 
                                 JOIN users ON enrollments.student_id = users.id
                                 JOIN courses ON enrollments.course_id = courses.id
                                 WHERE courses.faculty_id=" . $user['id']);
    ?>
    <html>
    <head>
        <title>Faculty Dashboard</title>
    </head>
    <body>
        <h2>Welcome, Faculty</h2>
        <a href="index.php?logout=1">Logout</a>
        <h3>Assign Grades</h3>
        <form method="POST">
            <select name="enrollment_id">
                <?php while ($row = $enrollments->fetch_assoc()) {
                    echo "<option value='" . $row['id'] . "'>" . $row['name'] . " - " . $row['course_name'] . "</option>";
                } ?>
            </select>
            <input type="text" name="grade" placeholder="Grade" required>
            <button type="submit" name="assign_grade">Assign</button>
        </form>
    </body>
    </html>
    <?php
}
?>
