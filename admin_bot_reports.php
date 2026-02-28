<?php
// admin_bot_reports.php - Admin view for BOT evaluation reports
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once 'includes/db_connection.php';

$pdo = getPDO();

// Handle AJAX refresh request
if (isset($_GET['action']) && $_GET['action'] === 'refresh') {
    header('Content-Type: application/json');
    echo json_encode(getBotReportsData($pdo));
    exit;
}

// Handle report generation request
if (isset($_POST['generate_report']) && isset($_POST['teacher_name'])) {
    generateBotSummaryReport($pdo, $_POST['teacher_name']);
    exit;
}

function getBotReportsData($pdo) {
    // Get all teachers with their BOT evaluation statistics
    $stmt = $pdo->query("
        SELECT 
            bt.teacher_name,
            bt.branch,
            bt.area_of_specialization,
            COUNT(be.id) as evaluation_count,
            AVG((be.a1 + be.a2 + be.a3 + be.a4 + be.a5 + be.a6) / 6 * 0.40 +
                (be.b1 + be.b2 + be.b3 + be.b4 + be.b5) / 5 * 0.30 +
                (be.c1 + be.c2 + be.c3 + be.c4 + be.c5) / 5 * 0.30) as average_score,
            MAX(be.created_at) as last_evaluation
        FROM bot_teachers bt
        LEFT JOIN bot_evaluations be ON bt.teacher_name = be.teacher_name
        GROUP BY bt.teacher_name, bt.branch, bt.area_of_specialization
        ORDER BY bt.teacher_name
    ");
    
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent evaluations
    $recentStmt = $pdo->query("
        SELECT 
            be.*,
            u.full_name as evaluator_name
        FROM bot_evaluations be
        JOIN users u ON be.bot_username = u.username
        ORDER BY be.created_at DESC
        LIMIT 10
    ");
    $recent = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $statsStmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT teacher_name) as teachers_with_eval,
            COUNT(*) as total_evaluations,
            COUNT(DISTINCT bot_username) as total_bots
        FROM bot_evaluations
    ");
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'teachers' => $teachers,
        'recent' => $recent,
        'stats' => $stats,
        'timestamp' => time()
    ];
}

function getDescriptiveRating($score) {
    if ($score >= 3.25 && $score <= 4.00) return 'Distinguished';
    if ($score >= 2.50 && $score <= 3.24) return 'Competent';
    if ($score >= 1.75 && $score <= 2.49) return 'Progressing';
    if ($score >= 1.00 && $score <= 1.74) return 'Needs Improvement';
    return 'Not Rated';
}

function generateBotSummaryReport($pdo, $teacherName) {
    // Get all evaluations for this teacher
    $stmt = $pdo->prepare("
        SELECT 
            be.*,
            u.full_name as evaluator_name
        FROM bot_evaluations be
        JOIN users u ON be.bot_username = u.username
        WHERE be.teacher_name = ?
        ORDER BY be.created_at
    ");
    $stmt->execute([$teacherName]);
    $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($evaluations)) {
        echo json_encode(['success' => false, 'error' => 'No evaluations found']);
        return;
    }
    
    // Calculate aggregate scores
    $totalEvals = count($evaluations);
    $aSum = $bSum = $cSum = 0;
    $allComments = [];
    
    foreach ($evaluations as $eval) {
        $aAvg = ($eval['a1'] + $eval['a2'] + $eval['a3'] + $eval['a4'] + $eval['a5'] + $eval['a6']) / 6;
        $aSum += $aAvg;
        
        $bAvg = ($eval['b1'] + $eval['b2'] + $eval['b3'] + $eval['b4'] + $eval['b5']) / 5;
        $bSum += $bAvg;
        
        $cAvg = ($eval['c1'] + $eval['c2'] + $eval['c3'] + $eval['c4'] + $eval['c5']) / 5;
        $cSum += $cAvg;
        
        if (!empty($eval['comments'])) {
            $allComments[] = $eval['comments'];
        }
    }
    
    $aOverall = $aSum / $totalEvals;
    $bOverall = $bSum / $totalEvals;
    $cOverall = $cSum / $totalEvals;
    
    $totalScore = ($aOverall * 0.40) + ($bOverall * 0.30) + ($cOverall * 0.30);
    
    // Generate HTML report - same style as student reports
    $html = '
    <html>
    <head>
        <title>BOT Evaluation Summary - ' . htmlspecialchars($teacherName) . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 1in; }
            h1 { color: #800000; text-align: center; border-bottom: 2px solid #800000; padding-bottom: 10px; }
            h2 { color: #800000; border-bottom: 1px solid #d4af37; padding-bottom: 5px; }
            .header { text-align: center; margin-bottom: 30px; }
            .school-name { font-size: 18pt; font-weight: bold; color: #800000; }
            table { border-collapse: collapse; width: 100%; margin: 20px 0; }
            th { background: #800000; color: white; padding: 10px; }
            td { padding: 8px; border: 1px solid #999; }
            .summary { background: #f9f5eb; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 5px solid #800000; }
            .score { font-size: 24px; font-weight: bold; color: #800000; text-align: center; }
            .rating { display: inline-block; padding: 5px 20px; background: #800000; color: white; border-radius: 20px; font-weight: bold; }
            .comments { background: #f0f0f0; padding: 10px; margin: 10px 0; border-left: 4px solid #800000; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 10pt; border-top: 1px solid #ccc; padding-top: 10px; }
        </style>
    </head>
    <body>
        <div class="header">
            <div class="school-name">PHILIPPINE TECHNOLOGICAL INSTITUTE OF SCIENCE ARTS AND TRADE, INC.</div>
            <div>GMA-BRANCH</div>
            <h1>BOT Classroom Observation Summary Report</h1>
        </div>
        
        <div class="summary">
            <h2>Teacher Information</h2>
            <p><strong>Name:</strong> ' . htmlspecialchars($teacherName) . '</p>
            <p><strong>Branch:</strong> ' . htmlspecialchars($evaluations[0]['branch']) . '</p>
            <p><strong>Department:</strong> ' . htmlspecialchars($evaluations[0]['department']) . '</p>
            <p><strong>Specialization:</strong> ' . htmlspecialchars($evaluations[0]['area_of_specialization']) . '</p>
            <p><strong>Total Evaluators:</strong> ' . $totalEvals . '</p>
            <p><strong>Report Generated:</strong> ' . date('F j, Y \a\t g:i A') . '</p>
        </div>
        
        <h2>Overall Performance</h2>
        <table>
            <tr>
                <th>Indicator</th>
                <th>Average Score</th>
                <th>Weight</th>
                <th>Weighted Score</th>
            </tr>
            <tr>
                <td>Instructional Competence</td>
                <td>' . number_format($aOverall, 2) . '</td>
                <td>40%</td>
                <td>' . number_format($aOverall * 0.40, 2) . '</td>
            </tr>
            <tr>
                <td>Classroom Management</td>
                <td>' . number_format($bOverall, 2) . '</td>
                <td>30%</td>
                <td>' . number_format($bOverall * 0.30, 2) . '</td>
            </tr>
            <tr>
                <td>Professionalism</td>
                <td>' . number_format($cOverall, 2) . '</td>
                <td>30%</td>
                <td>' . number_format($cOverall * 0.30, 2) . '</td>
            </tr>
            <tr style="background: #f0e6e6; font-weight: bold;">
                <td colspan="3" style="text-align: right;">TOTAL SCORE</td>
                <td>' . number_format($totalScore, 2) . '</td>
            </tr>
        </table>
        
        <div style="text-align: center; margin: 20px 0;">
            <span class="rating">Rating: ' . getDescriptiveRating($totalScore) . '</span>
        </div>
        
        <h2>Individual Evaluations</h2>
        <table>
            <tr>
                <th>Evaluator</th>
                <th>Date</th>
                <th>Instructional</th>
                <th>Management</th>
                <th>Professional</th>
                <th>Total</th>
            </tr>';
    
    foreach ($evaluations as $eval) {
        $aInd = ($eval['a1'] + $eval['a2'] + $eval['a3'] + $eval['a4'] + $eval['a5'] + $eval['a6']) / 6;
        $bInd = ($eval['b1'] + $eval['b2'] + $eval['b3'] + $eval['b4'] + $eval['b5']) / 5;
        $cInd = ($eval['c1'] + $eval['c2'] + $eval['c3'] + $eval['c4'] + $eval['c5']) / 5;
        $totalInd = ($aInd * 0.40) + ($bInd * 0.30) + ($cInd * 0.30);
        
        $html .= '
            <tr>
                <td>' . htmlspecialchars($eval['evaluator_name']) . '</td>
                <td>' . date('M d, Y', strtotime($eval['created_at'])) . '</td>
                <td>' . number_format($aInd, 2) . '</td>
                <td>' . number_format($bInd, 2) . '</td>
                <td>' . number_format($cInd, 2) . '</td>
                <td><strong>' . number_format($totalInd, 2) . '</strong></td>
            </tr>';
    }
    
    $html .= '</table>';
    
    if (!empty($allComments)) {
        $html .= '<h2>Comments and Recommendations</h2>';
        foreach ($allComments as $index => $comment) {
            $html .= '<div class="comments">';
            $html .= '<strong>Evaluator ' . ($index + 1) . ':</strong><br>';
            $html .= nl2br(htmlspecialchars($comment));
            $html .= '</div>';
        }
    }
    
    $html .= '
        <div class="footer">
            <p>Generated by PHILTECH GMA Teacher Evaluation System</p>
        </div>
    </body>
    </html>';
    
    // Save to file
    $reportsDir = __DIR__ . '/reports/BOT Evaluation Reports/';
    if (!file_exists($reportsDir)) {
        mkdir($reportsDir, 0777, true);
    }
    
    $filename = $reportsDir . 'BOT_Summary_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $teacherName) . '_' . date('Ymd_His') . '.html';
    file_put_contents($filename, $html);
    
    echo json_encode([
        'success' => true,
        'filename' => basename($filename),
        'path' => 'reports/BOT Evaluation Reports/' . basename($filename)
    ]);
}

$data = getBotReportsData($pdo);
$teachers = $data['teachers'];
$recent = $data['recent'];
$stats = $data['stats'];

// Helper function for safe display
function safe_display($value, $default = 'N/A') {
    return !empty($value) ? htmlspecialchars($value) : $default;
}

function formatBytes($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - BOT Evaluation Reports</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #800000 0%, #A52A2A 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header h1 {
            color: #800000;
            margin-bottom: 10px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-primary {
            background: #800000;
        }

        .btn-primary:hover:not(:disabled) {
            background: #660000;
        }

        .btn-refresh {
            background: #28a745;
        }

        .btn-refresh:hover:not(:disabled) {
            background: #218838;
        }

        .btn-refresh.loading {
            background: #6c757d;
        }

        .spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #800000;
        }

        .stat-label {
            color: #666;
            margin-top: 5px;
        }

        .section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .section h2 {
            color: #800000;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #800000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #800000;
            color: white;
            padding: 12px;
            text-align: left;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }

        tr:hover {
            background: #f9f5eb;
        }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .rating-distinguished {
            background: #800000;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
        }

        .rating-competent {
            background: #28a745;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
        }

        .rating-progressing {
            background: #ffc107;
            color: #333;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
        }

        .rating-needs {
            background: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
        }

        .action-btn {
            padding: 5px 10px;
            background: #800000;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            margin-right: 5px;
        }

        .action-btn:hover {
            background: #660000;
        }

        .action-btn.secondary {
            background: #17a2b8;
        }

        .action-btn.secondary:hover {
            background: #138496;
        }

        .last-updated {
            font-size: 0.85em;
            color: #666;
            margin-top: 5px;
        }

        #notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            max-width: 400px;
        }

        .notification {
            background: white;
            padding: 15px 20px;
            margin-bottom: 10px;
            border-radius: 5px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideInRight 0.3s ease-out;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .notification.success {
            border-left: 4px solid #28a745;
        }

        .notification.error {
            border-left: 4px solid #dc3545;
        }

        .notification.info {
            border-left: 4px solid #17a2b8;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
            text-align: center;
        }

        .modal-content h3 {
            color: #800000;
            margin-bottom: 15px;
        }

        .modal-content p {
            margin-bottom: 20px;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        @media (max-width: 768px) {
            table {
                font-size: 14px;
            }
            
            td, th {
                padding: 8px 5px;
            }
        }
    </style>
</head>
<body>
    <div id="notification-container"></div>

    <div class="container">
        <div class="header">
            <h1>BOT Classroom Observation Reports</h1>
            <div class="header-actions">
                <a href="admin.php" class="btn btn-primary">← Back to Admin Dashboard</a>
                <button id="refreshBtn" class="btn btn-refresh" onclick="refreshReports()">
                    <span id="refreshIcon">🔄</span>
                    <span id="refreshText">Refresh Data</span>
                </button>
            </div>
            <div class="last-updated" id="lastUpdated">
                Last updated: Just now
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['teachers_with_eval'] ?? 0; ?></div>
                <div class="stat-label">Teachers with Evaluations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_evaluations'] ?? 0; ?></div>
                <div class="stat-label">Total Evaluations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_bots'] ?? 0; ?></div>
                <div class="stat-label">Active BOT Evaluators</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($teachers); ?></div>
                <div class="stat-label">Total Teachers</div>
            </div>
        </div>

        <!-- Teacher Evaluation Summary -->
        <div class="section">
            <h2>👥 Teacher Evaluation Summary</h2>
            <table>
                <thead>
                    <tr>
                        <th>Teacher Name</th>
                        <th>Branch</th>
                        <th>Specialization</th>
                        <th>Evaluations</th>
                        <th>Average Score</th>
                        <th>Rating</th>
                        <th>Last Evaluation</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teachers as $teacher): ?>
                    <tr>
                        <td><strong><?php echo safe_display($teacher['teacher_name']); ?></strong></td>
                        <td><?php echo safe_display($teacher['branch']); ?></td>
                        <td><?php echo safe_display($teacher['area_of_specialization']); ?></td>
                        <td>
                            <?php if ($teacher['evaluation_count'] > 0): ?>
                                <span class="badge badge-success"><?php echo $teacher['evaluation_count']; ?> eval</span>
                            <?php else: ?>
                                <span class="badge badge-warning">No evaluations</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($teacher['average_score']): ?>
                                <?php echo number_format($teacher['average_score'], 2); ?>
                            <?php else: ?>
                                --
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($teacher['average_score']): ?>
                                <?php 
                                $rating = getDescriptiveRating($teacher['average_score']);
                                $ratingClass = '';
                                if ($rating == 'Distinguished') $ratingClass = 'rating-distinguished';
                                elseif ($rating == 'Competent') $ratingClass = 'rating-competent';
                                elseif ($rating == 'Progressing') $ratingClass = 'rating-progressing';
                                else $ratingClass = 'rating-needs';
                                ?>
                                <span class="<?php echo $ratingClass; ?>"><?php echo $rating; ?></span>
                            <?php else: ?>
                                --
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($teacher['last_evaluation']): ?>
                                <?php echo date('M d, Y', strtotime($teacher['last_evaluation'])); ?>
                            <?php else: ?>
                                --
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($teacher['evaluation_count'] > 0): ?>
                                <button class="action-btn" onclick="generateReport('<?php echo safe_display($teacher['teacher_name']); ?>')">Generate Report</button>
                            <?php else: ?>
                                <button class="action-btn" disabled style="opacity: 0.5;">No Data</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Report Generation Modal -->
    <div class="modal" id="reportModal">
        <div class="modal-content">
            <h3>Generating Report...</h3>
            <p id="modalMessage">Please wait while we generate the summary report.</p>
            <div class="modal-buttons">
                <button class="btn btn-primary" id="downloadBtn" style="display: none;" onclick="downloadReport()">Download Report</button>
                <button class="btn btn-refresh" onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        let isRefreshing = false;
        let currentReportPath = '';

        function showNotification(message, type = 'info') {
            const container = document.getElementById('notification-container');
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            
            const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️';
            notification.innerHTML = `
                <span style="font-size: 1.2em;">${icon}</span>
                <span>${message}</span>
            `;
            
            container.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideInRight 0.3s ease-out reverse';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        async function refreshReports() {
            if (isRefreshing) return;
            
            isRefreshing = true;
            const refreshBtn = document.getElementById('refreshBtn');
            const refreshIcon = document.getElementById('refreshIcon');
            const refreshText = document.getElementById('refreshText');
            
            refreshBtn.disabled = true;
            refreshBtn.classList.add('loading');
            refreshIcon.innerHTML = '<span class="spinner"></span>';
            refreshText.textContent = 'Refreshing...';
            
            try {
                const response = await fetch('?action=refresh&t=' + Date.now());
                
                if (!response.ok) {
                    throw new Error('Failed to fetch data');
                }
                
                const data = await response.json();
                
                // Update last updated time
                const now = new Date();
                document.getElementById('lastUpdated').textContent = 
                    `Last updated: ${now.toLocaleTimeString()}`;
                
                showNotification('Data refreshed successfully!', 'success');
                
                // Reload page to show updated data
                setTimeout(() => {
                    location.reload();
                }, 1000);
                
            } catch (error) {
                console.error('Refresh error:', error);
                showNotification('Failed to refresh data. Please try again.', 'error');
            } finally {
                isRefreshing = false;
                refreshBtn.disabled = false;
                refreshBtn.classList.remove('loading');
                refreshIcon.innerHTML = '🔄';
                refreshText.textContent = 'Refresh Data';
            }
        }

        async function generateReport(teacherName) {
            const modal = document.getElementById('reportModal');
            const modalMessage = document.getElementById('modalMessage');
            const downloadBtn = document.getElementById('downloadBtn');
            
            modal.classList.add('active');
            modalMessage.textContent = 'Generating summary report for ' + teacherName + '...';
            downloadBtn.style.display = 'none';
            
            try {
                const formData = new FormData();
                formData.append('generate_report', '1');
                formData.append('teacher_name', teacherName);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    modalMessage.textContent = 'Report generated successfully!';
                    currentReportPath = result.path;
                    downloadBtn.style.display = 'inline-block';
                    showNotification('Report generated successfully!', 'success');
                } else {
                    modalMessage.textContent = 'Error: ' + (result.error || 'Failed to generate report');
                    showNotification('Failed to generate report', 'error');
                }
            } catch (error) {
                console.error('Generation error:', error);
                modalMessage.textContent = 'Error generating report. Please try again.';
                showNotification('Error generating report', 'error');
            }
        }

        function downloadReport() {
            if (currentReportPath) {
                window.open(currentReportPath, '_blank');
            }
        }

        function closeModal() {
            document.getElementById('reportModal').classList.remove('active');
        }

        function viewDetails(teacherName) {
            // You can implement a details view modal here
            showNotification('View details feature coming soon!', 'info');
        }

        // Auto-refresh every 5 minutes
        setInterval(() => {
            refreshReports();
        }, 300000);
    </script>
</body>
</html>
