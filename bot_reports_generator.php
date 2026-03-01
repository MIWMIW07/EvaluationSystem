<?php
// bot_reports_generator.php - Generate BOT evaluation reports as Word documents
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once 'includes/db_connection.php';

function imageToBase64($path) {
    if (!file_exists($path)) {
        error_log("Image not found: " . $path);
        return false;
    }
    
    $data = file_get_contents($path);
    if ($data === false) {
        error_log("Failed to read image: " . $path);
        return false;
    }
    
    $extension = pathinfo($path, PATHINFO_EXTENSION);
    $mimeType = '';
    
    switch(strtolower($extension)) {
        case 'png':
            $mimeType = 'image/png';
            break;
        case 'jpg':
        case 'jpeg':
            $mimeType = 'image/jpeg';
            break;
        default:
            $mimeType = 'image/png';
    }
    
    return 'data:' . $mimeType . ';base64,' . base64_encode($data);
}

function getBotDescriptiveRating($score) {
    $rounded = round($score, 2);
    if ($rounded >= 3.25) return 'Distinguished';
    if ($rounded >= 2.50) return 'Competent';
    if ($rounded >= 1.75) return 'Progressing';
    if ($rounded >= 1.00) return 'Needs Improvement';
    return 'Not Rated';
}

function getInstructionalInterpretation($score) {
    $rounded = round($score, 2);
    if ($rounded >= 3.25) {
        return 'The teacher demonstrates Distinguished instructional competence. Lessons are exceptionally well-prepared, clearly delivered, and enriched with varied effective strategies that cater to diverse learner needs.';
    } elseif ($rounded >= 2.50) {
        return 'The teacher exhibits Competent instructional competence. Lessons are clearly discussed, and students are effectively engaged.';
    } elseif ($rounded >= 1.75) {
        return 'The teacher shows Progressing instructional competence. Instructional methods are adequate, but further improvement is encouraged.';
    } else {
        return 'The teacher\'s instructional competence Needs Improvement. Immediate mentoring and professional development are highly recommended.';
    }
}

function getManagementInterpretation($score) {
    $rounded = round($score, 2);
    if ($rounded >= 3.25) {
        return 'The teacher demonstrates Distinguished classroom management skills. A well-disciplined, safe, and highly motivating classroom environment is consistently maintained.';
    } elseif ($rounded >= 2.50) {
        return 'The teacher shows Competent management ability. Classroom procedures are well-implemented, and a conducive learning environment is sustained.';
    } elseif ($rounded >= 1.75) {
        return 'The teacher\'s management skills are Progressing. Classroom order and discipline are generally maintained.';
    } else {
        return 'The teacher\'s management skills Need Improvement. The classroom environment may not be consistently conducive to learning.';
    }
}

function getProfessionalismInterpretation($score) {
    $rounded = round($score, 2);
    if ($rounded >= 3.25) {
        return 'The teacher displays Distinguished professional and ethical qualities. Professionalism, emotional balance, and enthusiasm are consistently evident.';
    } elseif ($rounded >= 2.50) {
        return 'The teacher exhibits Competent professional and social qualities. Professional conduct and positive interpersonal skills are consistently observed.';
    } elseif ($rounded >= 1.75) {
        return 'The teacher shows Progressing professional qualities. The teacher interacts adequately but may still enhance professional presentation.';
    } else {
        return 'The teacher\'s professional qualities Need Improvement. Immediate development through mentoring is advised.';
    }
}

function getOverallInterpretation($score) {
    $rounded = round($score, 2);
    if ($rounded >= 3.25) {
        return 'The teacher\'s Overall Performance is Distinguished. This reflects exceptional competence across all areas — instructional competence, classroom management, and professionalism.';
    } elseif ($rounded >= 2.50) {
        return 'The teacher\'s Overall Performance is Competent. The teacher meets and often exceeds expectations across all areas.';
    } elseif ($rounded >= 1.75) {
        return 'The teacher\'s Overall Performance is Progressing. The teacher meets minimum standards but would benefit from ongoing professional development.';
    } else {
        return 'The teacher\'s Overall Performance Needs Improvement. Immediate intervention and professional coaching are necessary.';
    }
}

if (isset($_POST['teacher_name'])) {
    $teacherName = $_POST['teacher_name'];
    $pdo = getPDO();
    
    $stmt = $pdo->prepare("
        SELECT be.*, u.full_name as evaluator_name
        FROM bot_evaluations be
        JOIN users u ON be.bot_username = u.username
        WHERE be.teacher_name = ?
        ORDER BY be.created_at
    ");
    $stmt->execute([$teacherName]);
    $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($evaluations)) {
        die('No evaluations found');
    }
    
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
    
    $logoBase64 = imageToBase64(__DIR__ . '/images/logo-original.png');
    $logoHtml = $logoBase64 ? '<img src="' . $logoBase64 . '" style="width: 100px; height: 100px; object-fit: contain;">' : '';
    
    // Word document with proper headers
    $html = '<html xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns:m="http://schemas.microsoft.com/office/2004/12/omml" xmlns="http://www.w3.org/TR/REC-html40">
    <head>
        <meta charset="UTF-8">
        <title>BOT Evaluation Report - ' . htmlspecialchars($teacherName) . '</title>
        <style>
            @page { size: A4; margin: 2.54cm 3.17cm; }
            body { font-family: Arial, sans-serif; line-height: 1.5; margin: 0; padding: 0; }
            .header { display: flex; align-items: center; justify-content: center; gap: 20px; margin-bottom: 20px; }
            .school-name { font-size: 18pt; font-weight: bold; color: #800000; text-align: center; }
            h1 { color: #800000; text-align: center; border-bottom: 2px solid #800000; padding-bottom: 10px; }
            h2 { color: #800000; border-bottom: 1px solid #d4af37; padding-bottom: 5px; }
            table { border-collapse: collapse; width: 100%; margin: 20px 0; }
            th { background: #800000; color: white; padding: 10px; border: 1px solid #660000; }
            td { padding: 8px; border: 1px solid #999; vertical-align: top; }
            .summary { background: #f9f5eb; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 5px solid #800000; }
            .rating { display: inline-block; padding: 5px 20px; background: #800000; color: white; border-radius: 20px; font-weight: bold; }
            .comments { background: #f0f0f0; padding: 10px; margin: 10px 0; border-left: 4px solid #800000; }
            .rating-scale { margin: 20px 0; padding: 15px; background: #f0f0f0; border-radius: 8px; }
            .footer { text-align: center; margin-top: 30px; color: #666; border-top: 1px solid #ccc; padding-top: 10px; font-size: 9pt; }
        </style>
    </head>
    <body>
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="display: flex; align-items: center; justify-content: center; gap: 20px;">
                ' . $logoHtml . '
                <div>
                    <div style="font-size: 18pt; font-weight: bold; color: #800000;">PHILIPPINE TECHNOLOGICAL INSTITUTE OF SCIENCE ARTS AND TRADE, INC.</div>
                    <div style="font-size: 12pt;">GMA-BRANCH</div>
                </div>
            </div>
            <hr style="border: 2px solid #800000; margin: 20px 0;">
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
        
        <div class="rating-scale">
            <h3 style="color: #800000;">Rating Scale</h3>
            <table>
                <tr><th>Score Range</th><th>Descriptive Rating</th></tr>
                <tr><td>3.25 - 4.00</td><td><strong style="color:#800000;">Distinguished</strong></td></tr>
                <tr><td>2.50 - 3.24</td><td><strong style="color:#28a745;">Competent</strong></td></tr>
                <tr><td>1.75 - 2.49</td><td><strong style="color:#ffc107;">Progressing</strong></td></tr>
                <tr><td>1.00 - 1.74</td><td><strong style="color:#dc3545;">Needs Improvement</strong></td></tr>
            </table>
        </div>
        
        <h2>Overall Performance</h2>
        <table>
            <tr>
                <th>Indicator</th>
                <th>Average Score</th>
                <th>Weight</th>
                <th>Weighted Score</th>
                <th>Interpretation</th>
            </tr>
            <tr>
                <td><strong>Instructional Competence</strong></td>
                <td>' . number_format($aOverall, 2) . '</td>
                <td>40%</td>
                <td>' . number_format($aOverall * 0.40, 2) . '</td>
                <td>' . getInstructionalInterpretation($aOverall) . '</td>
            </tr>
            <tr>
                <td><strong>Classroom Management</strong></td>
                <td>' . number_format($bOverall, 2) . '</td>
                <td>30%</td>
                <td>' . number_format($bOverall * 0.30, 2) . '</td>
                <td>' . getManagementInterpretation($bOverall) . '</td>
            </tr>
            <tr>
                <td><strong>Professionalism</strong></td>
                <td>' . number_format($cOverall, 2) . '</td>
                <td>30%</td>
                <td>' . number_format($cOverall * 0.30, 2) . '</td>
                <td>' . getProfessionalismInterpretation($cOverall) . '</td>
            </tr>
            <tr style="background: #f0e6e6; font-weight: bold;">
                <td colspan="4" style="text-align: right;">OVERALL PERFORMANCE</td>
                <td>' . getOverallInterpretation($totalScore) . '</td>
            </tr>
        </table>
        
        <div style="text-align: center; margin: 20px 0;">
            <span class="rating">Rating: ' . getBotDescriptiveRating($totalScore) . ' (' . number_format($totalScore, 2) . ')</span>
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
        $commentNum = 1;
        foreach ($allComments as $comment) {
            $html .= '<div class="comments">';
            $html .= '<strong>Evaluator ' . $commentNum . ':</strong><br>';
            $html .= nl2br(htmlspecialchars($comment));
            $html .= '</div>';
            $commentNum++;
        }
    }
    
    $html .= '
        <div class="footer">
            <p>Generated by PHILTECH GMA Teacher Evaluation System on ' . date('F j, Y \a\t g:i A') . '</p>
        </div>
    </body>
    </html>';
    
    $reportsDir = __DIR__ . '/reports/BOT Evaluation Reports/';
    if (!file_exists($reportsDir)) mkdir($reportsDir, 0777, true);
    
    $filename = 'BOT_Summary_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $teacherName) . '_' . date('Ymd_His') . '.doc';
    file_put_contents($reportsDir . $filename, $html);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'path' => 'reports/BOT Evaluation Reports/' . $filename
    ]);
    exit;
}
?>
