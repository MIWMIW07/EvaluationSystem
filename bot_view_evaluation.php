<?php
// bot_view_evaluation.php - View submitted BOT evaluation
session_start();

// Check if user is logged in and is a BOT
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'bot') {
    header('Location: bot_login.php');
    exit;
}

require_once 'includes/db_connection.php';

$pdo = getPDO();

// Get evaluation ID from URL
$evaluation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$evaluation_id) {
    header('Location: bot_dashboard.php?error=invalid_evaluation');
    exit;
}

// Fetch evaluation details
$stmt = $pdo->prepare("
    SELECT * FROM bot_evaluations 
    WHERE id = ? AND bot_username = ?
");
$stmt->execute([$evaluation_id, $_SESSION['username']]);
$evaluation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$evaluation) {
    header('Location: bot_dashboard.php?error=evaluation_not_found');
    exit;
}

// Calculate scores and ratings
function calculateScores($eval) {
    // Section A: Instructional Competence (6 questions)
    $aTotal = $eval['a1'] + $eval['a2'] + $eval['a3'] + $eval['a4'] + $eval['a5'] + $eval['a6'];
    $aAverage = $aTotal / 6;
    $aWeighted = $aAverage * 0.40; // 40%
    
    // Section B: Classroom Management (5 questions)
    $bTotal = $eval['b1'] + $eval['b2'] + $eval['b3'] + $eval['b4'] + $eval['b5'];
    $bAverage = $bTotal / 5;
    $bWeighted = $bAverage * 0.30; // 30%
    
    // Section C: Professionalism (5 questions)
    $cTotal = $eval['c1'] + $eval['c2'] + $eval['c3'] + $eval['c4'] + $eval['c5'];
    $cAverage = $cTotal / 5;
    $cWeighted = $cAverage * 0.30; // 30%
    
    // Total Score
    $totalScore = $aWeighted + $bWeighted + $cWeighted;
    
    return [
        'a' => [
            'total' => $aTotal,
            'average' => round($aAverage, 2),
            'weighted' => round($aWeighted, 2)
        ],
        'b' => [
            'total' => $bTotal,
            'average' => round($bAverage, 2),
            'weighted' => round($bWeighted, 2)
        ],
        'c' => [
            'total' => $cTotal,
            'average' => round($cAverage, 2),
            'weighted' => round($cWeighted, 2)
        ],
        'total' => round($totalScore, 2)
    ];
}

function getDescriptiveRating($score) {
    if ($score >= 3.25 && $score <= 4.00) return 'Distinguished';
    if ($score >= 2.50 && $score <= 3.24) return 'Competent';
    if ($score >= 1.75 && $score <= 2.49) return 'Progressing';
    if ($score >= 1.00 && $score <= 1.74) return 'Needs Improvement';
    return 'Not Rated';
}

$scores = calculateScores($evaluation);
$descriptiveRating = getDescriptiveRating($scores['total']);

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
    <title>View Evaluation - <?php echo safe_display($evaluation['teacher_name']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #800000;
            padding-bottom: 20px;
        }

        .header h1 {
            color: #800000;
            margin-bottom: 10px;
        }

        .evaluation-info {
            background: #f9f5eb;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 5px solid #800000;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .info-item {
            padding: 10px;
            background: white;
            border-radius: 5px;
        }

        .info-item strong {
            color: #800000;
        }

        .score-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .score-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-top: 4px solid #800000;
        }

        .score-card.total {
            background: linear-gradient(135deg, #800000, #A52A2A);
            color: white;
        }

        .score-card.total .score-number {
            color: white;
        }

        .score-number {
            font-size: 36px;
            font-weight: bold;
            color: #800000;
            margin: 10px 0;
        }

        .score-label {
            color: #666;
            font-size: 14px;
        }

        .score-card.total .score-label {
            color: rgba(255,255,255,0.9);
        }

        .rating-badge {
            display: inline-block;
            padding: 8px 20px;
            background: #800000;
            color: white;
            border-radius: 25px;
            font-weight: bold;
            margin: 15px 0;
        }

        .section {
            margin-bottom: 30px;
            background: #fafafa;
            padding: 20px;
            border-radius: 8px;
        }

        .section h2 {
            color: #800000;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #800000;
        }

        .section h3 {
            color: #A52A2A;
            margin-bottom: 15px;
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

        .rating-value {
            font-weight: bold;
            color: #800000;
        }

        .rating-desc {
            font-size: 12px;
            color: #666;
        }

        .comments-section {
            background: #f9f5eb;
            padding: 20px;
            border-radius: 8px;
            margin: 30px 0;
            border-left: 5px solid #800000;
        }

        .comments-section h3 {
            color: #800000;
            margin-bottom: 10px;
        }

        .comments-content {
            background: white;
            padding: 20px;
            border-radius: 5px;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #800000;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background 0.3s;
            margin-right: 10px;
        }

        .btn:hover {
            background: #660000;
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 14px;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .container {
                box-shadow: none;
                padding: 20px;
            }
            
            .btn, .btn-secondary {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Classroom Observation Evaluation</h1>
            <p>Board of Trustees - Evaluation Result</p>
        </div>

        <div class="evaluation-info">
            <h3>📋 Evaluation Details</h3>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Teacher:</strong> <?php echo safe_display($evaluation['teacher_name']); ?>
                </div>
                <div class="info-item">
                    <strong>Branch:</strong> <?php echo safe_display($evaluation['branch']); ?>
                </div>
                <div class="info-item">
                    <strong>Specialization:</strong> <?php echo safe_display($evaluation['area_of_specialization']); ?>
                </div>
                <div class="info-item">
                    <strong>Evaluator:</strong> <?php echo safe_display($evaluation['bot_name']); ?>
                </div>
                <div class="info-item">
                    <strong>Date:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($evaluation['created_at'])); ?>
                </div>
            </div>
        </div>

        <div class="score-summary">
            <div class="score-card">
                <div class="score-label">Instructional Competence</div>
                <div class="score-number"><?php echo $scores['a']['average']; ?></div>
                <div class="score-label">Average (40% weight)</div>
                <div class="score-label" style="margin-top: 5px;">Weighted: <?php echo $scores['a']['weighted']; ?></div>
            </div>
            <div class="score-card">
                <div class="score-label">Classroom Management</div>
                <div class="score-number"><?php echo $scores['b']['average']; ?></div>
                <div class="score-label">Average (30% weight)</div>
                <div class="score-label" style="margin-top: 5px;">Weighted: <?php echo $scores['b']['weighted']; ?></div>
            </div>
            <div class="score-card">
                <div class="score-label">Professionalism</div>
                <div class="score-number"><?php echo $scores['c']['average']; ?></div>
                <div class="score-label">Average (30% weight)</div>
                <div class="score-label" style="margin-top: 5px;">Weighted: <?php echo $scores['c']['weighted']; ?></div>
            </div>
            <div class="score-card total">
                <div class="score-label">Total Score</div>
                <div class="score-number"><?php echo $scores['total']; ?></div>
                <div class="score-label"><?php echo $descriptiveRating; ?></div>
            </div>
        </div>

        <div style="text-align: center;">
            <span class="rating-badge">Rating: <?php echo $descriptiveRating; ?></span>
        </div>

        <!-- Section A Details -->
        <div class="section">
            <h2>A. Instructional Competence (40%)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Indicator</th>
                        <th width="100">Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>A1. Clear, engaging, well-paced; uses varied strategies to meet diverse needs.</td>
                        <td class="rating-value"><?php echo $evaluation['a1']; ?> - <span class="rating-desc"><?php 
                            echo $evaluation['a1'] == 4 ? 'Excellent' : 
                                 ($evaluation['a1'] == 3 ? 'Good' : 
                                 ($evaluation['a1'] == 2 ? 'Fair' : 'Needs Improvement')); 
                        ?></span></td>
                    </tr>
                    <tr>
                        <td>A2. Applies teaching strategy that caters learner's need in developing creative and critical thinking.</td>
                        <td class="rating-value"><?php echo $evaluation['a2']; ?> - <span class="rating-desc"><?php 
                            echo $evaluation['a2'] == 4 ? 'Excellent' : 
                                 ($evaluation['a2'] == 3 ? 'Good' : 
                                 ($evaluation['a2'] == 2 ? 'Fair' : 'Needs Improvement')); 
                        ?></span></td>
                    </tr>
                    <tr>
                        <td>A3. Curriculum Knowledge: Effective application of subject matter knowledge.</td>
                        <td class="rating-value"><?php echo $evaluation['a3']; ?> - <span class="rating-desc"><?php 
                            echo $evaluation['a3'] == 4 ? 'Excellent' : 
                                 ($evaluation['a3'] == 3 ? 'Good' : 
                                 ($evaluation['a3'] == 2 ? 'Fair' : 'Needs Improvement')); 
                        ?></span></td>
                    </tr>
                    <tr>
                        <td>A4. Collaboration and Integration is evident to create real-life learning.</td>
                        <td class="rating-value"><?php echo $evaluation['a4']; ?> - <span class="rating-desc"><?php 
                            echo $evaluation['a4'] == 4 ? 'Excellent' : 
                                 ($evaluation['a4'] == 3 ? 'Good' : 
                                 ($evaluation['a4'] == 2 ? 'Fair' : 'Needs Improvement')); 
                        ?></span></td>
                    </tr>
                    <tr>
                        <td>A5. Possesses a command of language of instruction.</td>
                        <td class="rating-value"><?php echo $evaluation['a5']; ?> - <span class="rating-desc"><?php 
                            echo $evaluation['a5'] == 4 ? 'Excellent' : 
                                 ($evaluation['a5'] == 3 ? 'Good' : 
                                 ($evaluation['a5'] == 2 ? 'Fair' : 'Needs Improvement')); 
                        ?></span></td>
                    </tr>
                    <tr>
                        <td>A6. Uses differentiated, developmentally appropriate learning experiences.</td>
                        <td class="rating-value"><?php echo $evaluation['a6']; ?> - <span class="rating-desc"><?php 
                            echo $evaluation['a6'] == 4 ? 'Excellent' : 
                                 ($evaluation['a6'] == 3 ? 'Good' : 
                                 ($evaluation['a6'] == 2 ? 'Fair' : 'Needs Improvement')); 
                        ?></span></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Section B Details -->
        <div class="section">
            <h2>B. Classroom Management and Environment (30%)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Indicator</th>
                        <th width="100">Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>B1. Maintains order effortlessly; routines are smooth and respectful.</td>
                        <td class="rating-value"><?php echo $evaluation['b1']; ?> - <span class="rating-desc"><?php 
                            echo $evaluation['b1'] == 4 ? 'Excellent' : 
                                 ($evaluation['b1'] == 3 ? 'Good' : 
                                 ($evaluation['b1'] == 2 ? 'Fair' : 'Needs Improvement')); 
                        ?></span></td>
                    </tr>
                    <tr>
                        <td>B2. Uses an approach that ensures student collaboration and participation.</td>
                        <td class="rating-value"><?php echo $evaluation['b2']; ?> - <span class="rating-desc"><?php 
                            echo $evaluation['b2'] == 4 ? 'Excellent' : 
                                 ($evaluation['b2'] == 3 ? 'Good' : 
                                 ($evaluation['b2'] == 2 ? 'Fair' : 'Needs Improvement')); 
                        ?></span></td>
                    </tr>
                    <tr>
                        <td>B3. Uses appropriate teaching and learning methods.</td>
                        <td class="rating-value"><?php echo $evaluation['b3']; ?> - <span class="rating-desc"><?php 
                            echo $evaluation['b3'] == 4 ? 'Excellent' : 
                                 ($evaluation['b3'] == 3 ? 'Good' : 
                                 ($evaluation['b3'] == 2 ? 'Fair' : 'Needs Improvement')); 
                        ?></span></td>
                    </tr>
                    <tr>
                        <td>B4. Maintain supportive learning environments that nurture and inspire learners.</td>
                        <td class="rating-value"><?php echo $evaluation['b4']; ?> - <span class="rating-desc"><?php 
                            echo $evaluation['b4'] == 4 ? 'Excellent' : 
                                 ($evaluation['b4'] == 3 ? 'Good' : 
                                 ($evaluation['b4'] == 2 ? 'Fair' : 'Needs Improvement')); 
                        ?></span></td>
                    </tr>
                    <tr>
                        <td>B5. Uses strategies for providing timely, accurate and constructive feedback.</td>
                        <td class="rating-value"><?php echo $evaluation['b5']; ?> - <span class="rating-desc"><?php 
                            echo $evaluation['b5'] == 4 ? 'Excellent' : 
                                 ($evaluation['b5'] == 3 ? 'Good' : 
                                 ($evaluation['b5'] == 2 ? 'Fair' : 'Needs Improvement')); 
                        ?></span></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Section C Details -->
        <div class="section">
            <h2>C. Professionalism / Teacher's Ethics (30%)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Indicator</th>
                        <th width="100">Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>C1. Confidence in delivering the subject is evident all throughout the discussion.</td>
                        <td class="rating-value"><?php echo $evaluation['c1']; ?> - <span class="rating-desc"><?php 
                            echo $evaluation['c1'] == 4 ? 'Excellent' : 
                                 ($evaluation['c1'] == 3 ? 'Good' : 
                                 ($evaluation['c1'] == 2 ? 'Fair' : 'Needs Improvement')); 
                        ?></span></td>
                    </tr>
                    <tr>
                        <td>C2. Neat in appearance; has professional bearing, wears appropriate attire.</td>
                        <td class="rating-value"><?php echo $evaluation['c2']; ?> - <span class="rating-desc"><?php 
                            echo $evaluation['c2'] == 4 ? 'Excellent' : 
                                 ($evaluation['c2'] == 3 ? 'Good' : 
                                 ($evaluation['c2'] == 2 ? 'Fair' : 'Needs Improvement')); 
                        ?></span></td>
                    </tr>
                    <tr>
                        <td>C3. Assumes responsibility and exhibits initiative, resourcefulness and commitment.</td>
                        <td class="rating-value"><?php echo $evaluation['c3']; ?> - <span class="rating-desc"><?php 
                            echo $evaluation['c3'] == 4 ? 'Excellent' : 
                                 ($evaluation['c3'] == 3 ? 'Good' : 
                                 ($evaluation['c3'] == 2 ? 'Fair' : 'Needs Improvement')); 
                        ?></span></td>
                    </tr>
                    <tr>
                        <td>C4. Maintains composure in the classroom.</td>
                        <td class="rating-value"><?php echo $evaluation['c4']; ?> - <span class="rating-desc"><?php 
                            echo $evaluation['c4'] == 4 ? 'Excellent' : 
                                 ($evaluation['c4'] == 3 ? 'Good' : 
                                 ($evaluation['c4'] == 2 ? 'Fair' : 'Needs Improvement')); 
                        ?></span></td>
                    </tr>
                    <tr>
                        <td>C5. Exudes an attitude that commands student's attention and respect.</td>
                        <td class="rating-value"><?php echo $evaluation['c5']; ?> - <span class="rating-desc"><?php 
                            echo $evaluation['c5'] == 4 ? 'Excellent' : 
                                 ($evaluation['c5'] == 3 ? 'Good' : 
                                 ($evaluation['c5'] == 2 ? 'Fair' : 'Needs Improvement')); 
                        ?></span></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Comments Section -->
        <div class="comments-section">
            <h3>💬 Comments and Recommendations</h3>
            <div class="comments-content">
                <?php echo !empty($evaluation['comments']) ? nl2br(htmlspecialchars($evaluation['comments'])) : '<em>No comments provided.</em>'; ?>
            </div>
        </div>

        <div style="text-align: center;">
            <a href="bot_dashboard.php" class="btn">← Back to Dashboard</a>
            <button onclick="window.print()" class="btn btn-secondary">🖨️ Print / Save as PDF</button>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> PHILTECH GMA - Board of Trustees Evaluation System</p>
        </div>
    </div>
</body>
</html>
