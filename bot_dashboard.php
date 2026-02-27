<?php
// bot_dashboard.php - Dashboard for Board of Trustees
session_start();

// Check if user is logged in and is a BOT
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'bot') {
    header('Location: bot_login.php');
    exit;
}

require_once 'includes/db_connection.php';
require_once 'includes/google_sheets.php'; // You'll need to create this

$pdo = getPDO();

// Get BOT information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$bot = $stmt->fetch(PDO::FETCH_ASSOC);

// Get BOT's evaluation statistics
$statsStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_evaluations,
        COUNT(DISTINCT teacher_name) as unique_teachers,
        MAX(created_at) as last_evaluation
    FROM bot_evaluations 
    WHERE bot_username = ?
");
$statsStmt->execute([$_SESSION['username']]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Get recent evaluations by this BOT
$recentStmt = $pdo->prepare("
    SELECT * FROM bot_evaluations 
    WHERE bot_username = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$recentStmt->execute([$_SESSION['username']]);
$recentEvaluations = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

// Get list of teachers to evaluate from database or Google Sheets
// First try from database, then fallback to Google Sheets
try {
    $teachersStmt = $pdo->query("
        SELECT * FROM bot_teachers 
        WHERE is_active = true 
        ORDER BY teacher_name
    ");
    $teachers = $teachersStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // If database table doesn't exist, fetch from Google Sheets
    $teachers = getBotTeachersFromSheets(); // You'll implement this
}

// Function to get teacher evaluation status
function getTeacherEvaluationStatus($pdo, $botUsername, $teacherName) {
    $stmt = $pdo->prepare("
        SELECT id, created_at FROM bot_evaluations 
        WHERE bot_username = ? AND teacher_name = ?
    ");
    $stmt->execute([$botUsername, $teacherName]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Helper function for safe display
function safe_display($value, $default = 'N/A') {
    return !empty($value) ? htmlspecialchars($value) : $default;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOT Dashboard - Classroom Observation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }

        .navbar {
            background: #800000;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar h1 {
            font-size: 24px;
        }

        .navbar h1 span {
            font-size: 14px;
            background: rgba(255,255,255,0.2);
            padding: 4px 10px;
            border-radius: 20px;
            margin-left: 10px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info span {
            font-size: 16px;
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .welcome-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-left: 5px solid #800000;
        }

        .welcome-card h2 {
            color: #800000;
            margin-bottom: 10px;
        }

        .welcome-card p {
            color: #666;
            line-height: 1.6;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #800000;
        }

        .stat-label {
            color: #666;
            margin-top: 10px;
            font-size: 14px;
        }

        .section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .section h3 {
            color: #800000;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #800000;
        }

        .teacher-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .teacher-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s;
            background: #fafafa;
        }

        .teacher-card:hover {
            box-shadow: 0 5px 15px rgba(128,0,0,0.2);
            transform: translateY(-3px);
        }

        .teacher-header {
            background: #800000;
            color: white;
            padding: 15px;
            font-weight: bold;
        }

        .teacher-body {
            padding: 15px;
        }

        .teacher-info {
            margin: 10px 0;
            color: #666;
        }

        .teacher-info strong {
            color: #800000;
        }

        .teacher-status {
            margin: 15px 0;
            padding: 8px;
            border-radius: 5px;
            text-align: center;
            font-weight: 500;
        }

        .status-evaluated {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            width: 100%;
            text-align: center;
        }

        .btn-evaluate {
            background: #28a745;
            color: white;
        }

        .btn-evaluate:hover {
            background: #218838;
        }

        .btn-view {
            background: #17a2b8;
            color: white;
        }

        .btn-view:hover {
            background: #138496;
        }

        .btn-view-eval {
            background: #800000;
            color: white;
        }

        .btn-view-eval:hover {
            background: #660000;
        }

        .recent-evaluations table {
            width: 100%;
            border-collapse: collapse;
        }

        .recent-evaluations th {
            background: #800000;
            color: white;
            padding: 12px;
            text-align: left;
        }

        .recent-evaluations td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }

        .recent-evaluations tr:hover {
            background: #f9f5eb;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            color: #666;
            font-size: 14px;
        }

        .search-box {
            margin-bottom: 20px;
        }

        .search-box input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 16px;
        }

        .search-box input:focus {
            outline: none;
            border-color: #800000;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .empty-state h4 {
            margin-bottom: 10px;
            color: #666;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .teacher-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>Board of Trustees Dashboard <span>Classroom Observation</span></h1>
        <div class="user-info">
            <span>Welcome, <?php echo safe_display($_SESSION['full_name']); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="welcome-card">
            <h2>Classroom Observation Evaluation</h2>
            <p>Welcome to the Board of Trustees evaluation system. Here you can evaluate teachers based on classroom observation. Each evaluation helps us maintain and improve the quality of education at PHILTECH GMA.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_evaluations'] ?? 0; ?></div>
                <div class="stat-label">Total Evaluations Done</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['unique_teachers'] ?? 0; ?></div>
                <div class="stat-label">Unique Teachers Evaluated</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php 
                    if (!empty($stats['last_evaluation'])) {
                        echo date('M d', strtotime($stats['last_evaluation']));
                    } else {
                        echo 'None';
                    }
                    ?>
                </div>
                <div class="stat-label">Last Evaluation</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($teachers ?? []); ?></div>
                <div class="stat-label">Total Teachers Available</div>
            </div>
        </div>

        <div class="section">
            <h3>Teachers to Evaluate</h3>
            
            <div class="search-box">
                <input type="text" id="teacherSearch" placeholder="Search teachers by name, branch, or specialization..." onkeyup="filterTeachers()">
            </div>

            <?php if (empty($teachers)): ?>
                <div class="empty-state">
                    <h4>No Teachers Available</h4>
                    <p>There are no teachers to evaluate at the moment.</p>
                </div>
            <?php else: ?>
                <div class="teacher-grid" id="teacherGrid">
                    <?php foreach ($teachers as $teacher): ?>
                        <?php 
                        $evaluation = getTeacherEvaluationStatus($pdo, $_SESSION['username'], $teacher['teacher_name']);
                        $isEvaluated = !empty($evaluation);
                        ?>
                        <div class="teacher-card" data-name="<?php echo strtolower($teacher['teacher_name']); ?>" data-branch="<?php echo strtolower($teacher['branch']); ?>" data-specialization="<?php echo strtolower($teacher['area_of_specialization']); ?>">
                            <div class="teacher-header">
                                <?php echo safe_display($teacher['teacher_name']); ?>
                            </div>
                            <div class="teacher-body">
                                <div class="teacher-info">
                                    <strong>Branch:</strong> <?php echo safe_display($teacher['branch']); ?>
                                </div>
                                <div class="teacher-info">
                                    <strong>Specialization:</strong> <?php echo safe_display($teacher['area_of_specialization']); ?>
                                </div>
                                
                                <?php if ($isEvaluated): ?>
                                    <div class="teacher-status status-evaluated">
                                        ✅ Evaluated on <?php echo date('M d, Y', strtotime($evaluation['created_at'])); ?>
                                    </div>
                                    <a href="bot_view_evaluation.php?id=<?php echo $evaluation['id']; ?>" class="btn btn-view-eval">View Evaluation</a>
                                <?php else: ?>
                                    <div class="teacher-status status-pending">
                                        ⏳ Pending Evaluation
                                    </div>
                                    <a href="bot_evaluation_form.php?teacher=<?php echo urlencode($teacher['teacher_name']); ?>&branch=<?php echo urlencode($teacher['branch']); ?>&specialization=<?php echo urlencode($teacher['area_of_specialization']); ?>" class="btn btn-evaluate">Start Evaluation</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($recentEvaluations)): ?>
        <div class="section recent-evaluations">
            <h3>Your Recent Evaluations</h3>
            <table>
                <thead>
                    <tr>
                        <th>Teacher Name</th>
                        <th>Branch</th>
                        <th>Date Evaluated</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentEvaluations as $eval): ?>
                    <tr>
                        <td><strong><?php echo safe_display($eval['teacher_name']); ?></strong></td>
                        <td><?php echo safe_display($eval['branch']); ?></td>
                        <td><?php echo date('F j, Y \a\t g:i A', strtotime($eval['created_at'])); ?></td>
                        <td>
                            <a href="bot_view_evaluation.php?id=<?php echo $eval['id']; ?>" class="btn btn-view" style="padding: 5px 10px; font-size: 14px;">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function filterTeachers() {
            const searchInput = document.getElementById('teacherSearch').value.toLowerCase();
            const teacherCards = document.querySelectorAll('.teacher-card');
            
            teacherCards.forEach(card => {
                const name = card.getAttribute('data-name');
                const branch = card.getAttribute('data-branch');
                const specialization = card.getAttribute('data-specialization');
                
                if (name.includes(searchInput) || branch.includes(searchInput) || specialization.includes(searchInput)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>

    <div class="footer">
        <p>&copy; <?php echo date('Y'); ?> PHILTECH GMA - Board of Trustees Evaluation System. All rights reserved.</p>
    </div>
</body>
</html>
