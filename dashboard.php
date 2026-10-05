<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$user = $_SESSION['user'];

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['announcement_title'])) {
    $title = trim($_POST['announcement_title']);
    $description = trim($_POST['announcement_description']);

    if (!empty($title) && !empty($description)) {
        $stmt = $pdo->prepare("INSERT INTO announcements (title, description, posted_by) VALUES (:title, :description, :posted_by)");
        $stmt->execute([
            'title' => $title,
            'description' => $description,
            'posted_by' => $user['id']
        ]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_complaint'])) {
    $subject = trim($_POST['subject']);
    $description = trim($_POST['description']);

    if (!empty($subject) && !empty($description)) {
        $stmt = $pdo->prepare("INSERT INTO complaints (user_id, subject, description, status) VALUES (:user_id, :subject, :description, 'Pending')");
        $stmt->execute([
            'user_id' => $user['id'],
            'subject' => $subject,
            'description' => $description
        ]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_room'])) {
    $roomId = $_POST['room_id'];
    $bookingDate = $_POST['booking_date'];
    $startTime = $_POST['start_time'];
    $endTime = $_POST['end_time'];
    $purpose = trim($_POST['purpose']);

    if (!empty($roomId) && !empty($bookingDate) && !empty($startTime) && !empty($endTime)) {
        $stmt = $pdo->prepare("INSERT INTO bookings (room_id, user_id, booking_date, start_time, end_time, purpose, status) VALUES (:room_id, :user_id, :booking_date, :start_time, :end_time, :purpose, 'Pending')");
        $stmt->execute([
            'room_id' => $roomId,
            'user_id' => $user['id'],
            'booking_date' => $bookingDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'purpose' => $purpose
        ]);
    }
}

$announcements = $pdo->query("SELECT a.*, u.name AS posted_by FROM announcements a JOIN users u ON u.id = a.posted_by ORDER BY a.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$complaints = $pdo->query("SELECT c.*, u.name FROM complaints c JOIN users u ON u.id = c.user_id ORDER BY c.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$rooms = $pdo->query("SELECT * FROM rooms ORDER BY room_name")->fetchAll(PDO::FETCH_ASSOC);
$timetable = $pdo->query("SELECT t.*, c.course_name, c.course_code FROM timetable t JOIN courses c ON c.id = t.course_id ORDER BY FIELD(t.day, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), t.start_time")->fetchAll(PDO::FETCH_ASSOC);
$role = $user['role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="topbar">
        <div class="brand">Smart Campus</div>
        <div class="nav-links">
            <a href="dashboard.php">Home</a>
            <a href="?logout=1" class="logout">Logout</a>
        </div>
    </div>

    <div class="container">
        <h1>Welcome, <?= htmlspecialchars($user['name']) ?>!</h1>
        <p>Role: <strong><?= ucfirst($user['role']) ?></strong></p>

        <?php if ($role === 'admin'): ?>
            <section class="cards">
                <div class="card">
                    <h3>Total Users</h3>
                    <p><?= $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(); ?></p>
                </div>
                <div class="card">
                    <h3>Total Rooms</h3>
                    <p><?= $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn(); ?></p>
                </div>
                <div class="card">
                    <h3>Total Courses</h3>
                    <p><?= $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn(); ?></p>
                </div>
                <div class="card">
                    <h3>Total Complaints</h3>
                    <p><?= $pdo->query("SELECT COUNT(*) FROM complaints")->fetchColumn(); ?></p>
                </div>
            </section>

            <section class="panel">
                <h2>Add Announcement</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="announcement_title" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="announcement_description" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Post</button>
                </form>
            </section>

            <section class="panel">
                <h2>Announcements</h2>
                <?php if (empty($announcements)): ?>
                    <p>No announcements yet.</p>
                <?php else: ?>
                    <?php foreach ($announcements as $a): ?>
                        <div class="list-item">
                            <h4><?= htmlspecialchars($a['title']) ?></h4>
                            <p><?= htmlspecialchars($a['description']) ?></p>
                            <small>Posted by <?= htmlspecialchars($a['posted_by']) ?> on <?= htmlspecialchars($a['created_at']) ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <section class="panel">
                <h2>Complaints</h2>
                <?php if (empty($complaints)): ?>
                    <p>No complaints submitted.</p>
                <?php else: ?>
                    <?php foreach ($complaints as $c): ?>
                        <div class="list-item">
                            <h4><?= htmlspecialchars($c['subject']) ?></h4>
                            <p><?= htmlspecialchars($c['description']) ?></p>
                            <small>Status: <?= htmlspecialchars($c['status']) ?> | By <?= htmlspecialchars($c['name']) ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

        <?php else: ?>
            <section class="panel">
                <h2>Submit Complaint</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" name="subject" required>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" required></textarea>
                    </div>

                    <button type="submit" name="submit_complaint" class="btn btn-primary">Submit Complaint</button>
                </form>
            </section>

            <section class="panel">
                <h2>Announcements</h2>
                <?php if (empty($announcements)): ?>
                    <p>No announcements.</p>
                <?php else: ?>
                    <?php foreach ($announcements as $a): ?>
                        <div class="list-item">
                            <h4><?= htmlspecialchars($a['title']) ?></h4>
                            <p><?= htmlspecialchars($a['description']) ?></p>
                            <small>By <?= htmlspecialchars($a['posted_by']) ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <section class="panel">
                <h2>Book a Room</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Room</label>
                        <select name="room_id" required>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?= $room['id'] ?>"><?= htmlspecialchars($room['room_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="booking_date" required>
                    </div>

                    <div class="form-group">
                        <label>Start Time</label>
                        <input type="time" name="start_time" required>
                    </div>

                    <div class="form-group">
                        <label>End Time</label>
                        <input type="time" name="end_time" required>
                    </div>

                    <div class="form-group">
                        <label>Purpose</label>
                        <input type="text" name="purpose" required>
                    </div>

                    <button type="submit" name="book_room" class="btn btn-primary">Book Room</button>
                </form>
            </section>

            <section class="panel">
                <h2>Timetable</h2>
                <?php if (empty($timetable)): ?>
                    <p>No timetable entries available.</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Room</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($timetable as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['course_code'] . ' - ' . $row['course_name']) ?></td>
                                    <td><?= htmlspecialchars($row['day']) ?></td>
                                    <td><?= htmlspecialchars($row['start_time'] . ' - ' . $row['end_time']) ?></td>
                                    <td><?= htmlspecialchars($row['room_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</body>
</html>
