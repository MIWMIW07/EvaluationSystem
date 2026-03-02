<?php
// local_reports_generator.php
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

ob_start();

// Function to get rating description based on score ranges
function getRatingDescription($score) {
    if ($score >= 4.5) return 'Outstanding';
    if ($score >= 3.5) return 'Very Satisfactory';
    if ($score >= 2.5) return 'Satisfactory';
    if ($score >= 1.5) return 'Fair';
    if ($score >= 1.0) return 'Poor';
    return 'Not Rated';
}

// Function to get interpretation for teaching competence
function getTeachingInterpretation($score) {
    if ($score >= 4.5) {
        return 'The teacher demonstrates Outstanding teaching competence. Lessons are well-prepared, clearly delivered, and enriched with effective instructional strategies. The teacher shows mastery of the subject matter and connects lessons with real-life applications, resulting in active and meaningful student learning.';
    } elseif ($score >= 3.5) {
        return 'The teacher exhibits Very Satisfactory teaching competence. Lessons are clearly discussed, and students are effectively engaged. The teacher demonstrates good command of the subject matter and uses suitable strategies to support learning.';
    } elseif ($score >= 2.5) {
        return 'The teacher shows Satisfactory performance in teaching. Instructional methods are adequate, but further improvement in delivery and student engagement is encouraged.';
    } elseif ($score >= 1.5) {
        return 'The teacher\'s teaching competence is Fair. There are areas that need improvement, such as lesson organization and clarity of instruction. Coaching or additional training may help enhance performance.';
    } elseif ($score >= 1.0) {
        return 'The teacher\'s teaching competence is rated Poor. Lessons may lack structure or engagement. Immediate mentoring and professional development are highly recommended.';
    }
    return 'Not rated.';
}

// Function to get interpretation for management skills
function getManagementInterpretation($score) {
    if ($score >= 4.5) {
        return 'The teacher demonstrates Outstanding management skills. A well-disciplined, safe, and motivating classroom environment is consistently maintained. Students show respect and positive behavior, reflecting strong classroom leadership.';
    } elseif ($score >= 3.5) {
        return 'The teacher shows Very Satisfactory management ability. Classroom procedures are well-implemented, and a conducive learning environment is sustained. The teacher handles students with fairness and professionalism.';
    } elseif ($score >= 2.5) {
        return 'The teacher\'s management skills are Satisfactory. Classroom order and discipline are generally maintained, though consistency and student engagement can still be improved.';
    } elseif ($score >= 1.5) {
        return 'The teacher\'s management skills are Fair. Some issues in maintaining classroom control or organization may affect learning efficiency. Support and mentoring are suggested.';
    } elseif ($score >= 1.0) {
        return 'The teacher\'s management skills are Poor. The classroom environment may not be conducive to learning. Immediate intervention and training are needed.';
    }
    return 'Not rated.';
}

// Function to get interpretation for guidance skills
function getGuidanceInterpretation($score) {
    if ($score >= 4.5) {
        return 'The teacher exhibits Outstanding guidance skills. Genuine concern for students\' personal growth and well-being is evident. The teacher provides fair and empathetic support, encouraging students to be confident and self-disciplined.';
    } elseif ($score >= 3.5) {
        return 'The teacher demonstrates Very Satisfactory guidance skills. Students feel supported and respected, and the teacher shows fairness and understanding in handling their concerns.';
    } elseif ($score >= 2.5) {
        return 'The teacher\'s guidance skills are Satisfactory. The teacher provides adequate student support but can strengthen counseling and motivational approaches.';
    } elseif ($score >= 1.5) {
        return 'The teacher\'s guidance skills are Fair. Limited engagement with students\' personal and academic issues is observed. More effort in student interaction and empathy is encouraged.';
    } elseif ($score >= 1.0) {
        return 'The teacher\'s guidance skills are Poor. Minimal concern or support for students\' well-being is perceived. Training on student relations and counseling is recommended.';
    }
    return 'Not rated.';
}

// Function to get interpretation for personal qualities
function getPersonalInterpretation($score) {
    if ($score >= 4.5) {
        return 'The teacher displays Outstanding personal and social qualities. Professionalism, emotional balance, and enthusiasm are consistently evident. The teacher maintains neat grooming, clear communication, and harmonious relationships with students and colleagues.';
    } elseif ($score >= 3.5) {
        return 'The teacher exhibits Very Satisfactory personal and social qualities. Professional conduct, good communication, and positive interpersonal skills are consistently observed.';
    } elseif ($score >= 2.5) {
        return 'The teacher shows Satisfactory personal and social qualities. The teacher interacts well but may still enhance emotional stability or professional presentation.';
    } elseif ($score >= 1.5) {
        return 'The teacher\'s personal and social qualities are Fair. Improvement is needed in maintaining professionalism, communication clarity, and social interactions.';
    } elseif ($score >= 1.0) {
        return 'The teacher\'s personal and social qualities are Poor. Lack of emotional balance or professionalism may be evident. Immediate development through mentoring is advised.';
    }
    return 'Not rated.';
}

// Function to get overall interpretation
function getOverallInterpretation($score) {
    if ($score >= 4.5) {
        return 'The teacher\'s Overall Performance is Outstanding. This reflects exceptional competence across all areas — teaching, management, guidance, and personal qualities. The teacher consistently exceeds expectations and serves as an excellent role model.';
    } elseif ($score >= 3.5) {
        return 'The teacher\'s Overall Performance is Very Satisfactory. The teacher meets and often exceeds expectations, demonstrating effective teaching, sound classroom management, and good rapport with students.';
    } elseif ($score >= 2.5) {
        return 'The teacher\'s Overall Performance is Satisfactory. The teacher meets the minimum standards and performs adequately but would benefit from ongoing professional development.';
    } elseif ($score >= 1.5) {
        return 'The teacher\'s Overall Performance is Fair. Certain areas require improvement. Focused support and guidance are recommended.';
    } elseif ($score >= 1.0) {
        return 'The teacher\'s Overall Performance is Poor. Immediate intervention and professional coaching are necessary to improve competency and effectiveness.';
    }
    return 'Not rated.';
}

// Function to convert image to base64 for embedding
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
    
    // Get file extension
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

// Function to create Word document content
function createWordDocument($content, $filename) {
    // Word document header with proper XML structure for A4 paper
    $html = '<html xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns:m="http://schemas.microsoft.com/office/2004/12/omml" xmlns="http://www.w3.org/TR/REC-html40">
    <head>
        <meta charset="UTF-8">
        <title>Evaluation Report</title>
        <style>
            /* A4 Paper Settings */
            @page {
                size: A4;
                margin: 2.54cm 3.17cm 2.54cm 3.17cm;
            }
            
            body { 
                font-family: "Arial", sans-serif; 
                margin: 0;
                padding: 0;
                line-height: 1.5;
            }
            
            /* Center all tables */
            table {
                margin-left: auto;
                margin-right: auto;
                width: 100%;
                border-collapse: collapse;
            }
            
            /* Header styles */
            .header {
                text-align: center;
                margin-bottom: 30px;
            }
            
            .header-content {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 20px;
                margin-bottom: 15px;
            }
            
            .school-name {
                font-size: 18pt;
                font-weight: bold;
                color: #800000;
                text-align: center;
            }
            
            .logo {
                width: 100px;
                height: 100px;
                object-fit: contain;
                display: inline-block;
            }
            
            /* Signature table */
            .signature-table {
                width: 100%;
                margin-top: 50px;
                border: none;
            }
            
            .signature-table td {
                border: none;
                text-align: center;
                width: 50%;
                padding: 10px;
            }
            
            .signature-img {
                width: 200px;
                height: 70px;
                object-fit: contain;
                display: block;
                margin: 10px auto;
            }
            
            /* Page break */
            .page-break {
                page-break-after: always;
            }
            
            /* Center headings */
            h1, h2, h3 {
                text-align: center;
                color: #800000;
            }
            
            h1 {
                font-size: 24pt;
                margin: 20px 0;
            }
            
            h2 {
                font-size: 18pt;
                border-bottom: 2px solid #800000;
                padding-bottom: 8px;
            }
            
            /* Stat box */
            .stat-box {
                background: #f8f9fa;
                padding: 15px;
                margin: 20px auto;
                border: 1px solid #dee2e6;
                border-radius: 5px;
                width: 80%;
            }
            
            /* Comment styles */
            .positive-comment {
                background: #d4edda;
                padding: 12px;
                margin: 8px 0;
                border-left: 5px solid #28a745;
            }
            
            .negative-comment {
                background: #fff3cd;
                padding: 12px;
                margin: 8px 0;
                border-left: 5px solid #ffc107;
            }
            
            /* Fix for image rendering in Word */
            img {
                max-width: 100%;
                height: auto;
            }
            
            /* Table styles */
            th {
                background: #800000;
                color: white;
                padding: 12px;
                border: 1px solid #660000;
            }
            
            td {
                padding: 10px;
                border: 1px solid #999;
                vertical-align: top;
            }
            
            /* Rating scale */
            .rating-scale {
                text-align: center;
                font-size: 10pt;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
        ' . $content . '
    </body>
    </html>';
    
    file_put_contents($filename, $html);
    return true;
}

// Function to generate evaluation cover page HTML
function getCoverPageHTML($teacherName, $program, $teachingScore, $managementScore, $guidanceScore, $personalScore, $overallScore) {
    // Convert images to base64
    $logoBase64 = imageToBase64(__DIR__ . '/images/logo-original.png');
    $signature1Base64 = imageToBase64(__DIR__ . '/images/Picture1.png');
    $signature2Base64 = imageToBase64(__DIR__ . '/images/Picture2.png');
    
    $logoHtml = '';
    $signature1Html = '';
    $signature2Html = '';
    
    if ($logoBase64) {
        $logoHtml = '<img src="' . $logoBase64 . '" class="logo" alt="School Logo">';
    } else {
        $logoHtml = '<div style="width: 100px; height: 100px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border: 1px dashed #800000;">Logo</div>';
    }
    
    if ($signature1Base64) {
        $signature1Html = '<img src="' . $signature1Base64 . '" class="signature-img" alt="Signature of Joanne P. Castro">';
    } else {
        $signature1Html = '<div style="height: 70px; width: 200px; margin: 0 auto; border-bottom: 2px solid #800000;"></div>';
    }
    
    if ($signature2Base64) {
        $signature2Html = '<img src="' . $signature2Base64 . '" class="signature-img" alt="Signature of Myra V. Jumantoc">';
    } else {
        $signature2Html = '<div style="height: 70px; width: 200px; margin: 0 auto; border-bottom: 2px solid #800000;"></div>';
    }
    
    $html = '
    <div class="header">
        <div class="header-content">
            ' . $logoHtml . '
            <div>
                <div class="school-name">PHILIPPINE TECHNOLOGICAL INSTITUTE OF SCIENCE ARTS AND TRADE, INC.</div>
                <div style="font-size: 14pt; color: #666; margin-top: 5px;">GMA-BRANCH (2ND Semester 2025-2026)</div>
            </div>
        </div>
        <hr style="border: 2px solid #800000; margin: 20px 0;">
        $content .= '
        <h2>Summary Report - Detailed Results</h2>
        <div class="stat-box">
            <p><strong>Teacher:</strong> ' . strtoupper($teacherName) . '</p>
        </div>';
        <h1>Teacher Evaluation by the students result</h1>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 25%;">Indicators</th>
                <th style="width: 10%;">Rating</th>
                <th style="width: 20%;">Description</th>
                <th style="width: 45%;">Interpretation</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>1. Teaching Competencies</strong></td>
                <td style="text-align: center;"><strong>' . number_format($teachingScore, 2) . '</strong></td>
                <td>' . getRatingDescription($teachingScore) . '</td>
                <td>' . getTeachingInterpretation($teachingScore) . '</td>
            </tr>
            <tr>
                <td><strong>2. Management Skills</strong></td>
                <td style="text-align: center;"><strong>' . number_format($managementScore, 2) . '</strong></td>
                <td>' . getRatingDescription($managementScore) . '</td>
                <td>' . getManagementInterpretation($managementScore) . '</td>
            </tr>
            <tr>
                <td><strong>3. Guidance Skills</strong></td>
                <td style="text-align: center;"><strong>' . number_format($guidanceScore, 2) . '</strong></td>
                <td>' . getRatingDescription($guidanceScore) . '</td>
                <td>' . getGuidanceInterpretation($guidanceScore) . '</td>
            </tr>
            <tr>
                <td><strong>4. Personal and Social Qualities/Skills</strong></td>
                <td style="text-align: center;"><strong>' . number_format($personalScore, 2) . '</strong></td>
                <td>' . getRatingDescription($personalScore) . '</td>
                <td>' . getPersonalInterpretation($personalScore) . '</td>
            </tr>
            <tr style="background: #f0e6e6;">
                <td style="font-weight: bold;">OVERALL PERFORMANCE</td>
                <td style="text-align: center; font-weight: bold;">' . number_format($overallScore, 2) . '</td>
                <td style="font-weight: bold;">' . getRatingDescription($overallScore) . '</td>
                <td>' . getOverallInterpretation($overallScore) . '</td>
            </tr>
        </tbody>
    </table>
    
    <p class="rating-scale"><strong>Rating Scale:</strong> 5 - Outstanding | 4 - Very Satisfactory | 3 - Satisfactory | 2 - Fair | 1 - Poor</p>
    
    <table class="signature-table">
        <tr>
            <td>
                <div style="font-size: 14pt; font-weight: bold; margin-bottom: 15px;">TABULATED BY:</div>
                ' . $signature1Html . '
                <div style="font-weight: bold; font-size: 12pt; margin-top: 5px;">Joanne P. Castro</div>
                <div style="color: #666;">Guidance Associate</div>
            </td>
            <td>
                <div style="font-size: 14pt; font-weight: bold; margin-bottom: 15px;">NOTED BY:</div>
                ' . $signature2Html . '
                <div style="font-weight: bold; font-size: 12pt; margin-top: 5px;">Myra V. Jumantoc</div>
                <div style="color: #666;">HR Head</div>
            </td>
        </tr>
    </table>
    <div class="page-break"></div>';
    
    return $html;
}

try {
    header('Content-Type: application/json');
    
    require_once 'includes/db_connection.php';
    
    $pdo = getPDO();
    
    $reportsDir = __DIR__ . '/reports/Teacher Evaluation Reports/Reports/';
    if (!file_exists($reportsDir)) {
        mkdir($reportsDir, 0777, true);
    }

    // Create images directory if it doesn't exist
    $imagesDir = __DIR__ . '/images/';
    if (!file_exists($imagesDir)) {
        mkdir($imagesDir, 0777, true);
    }

    $stmt = $pdo->query("
        SELECT DISTINCT 
            teacher_name, 
            program, 
            section 
        FROM evaluations 
        ORDER BY teacher_name, program, section
    ");
    $combinations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $summaryStmt = $pdo->query("
        SELECT DISTINCT 
            teacher_name, 
            program
        FROM evaluations 
        ORDER BY teacher_name, program
    ");
    $summaryCombinations = $summaryStmt->fetchAll(PDO::FETCH_ASSOC);

    $teachersProcessed = [];
    $individualReports = 0;
    $summaryReports = 0;
    $totalFiles = 0;

    foreach ($combinations as $combo) {
        $teacherName = $combo['teacher_name'];
        $program = $combo['program'];
        $section = $combo['section'];

        $teacherDir = $reportsDir . $teacherName . '/';
        if (!file_exists($teacherDir)) {
            mkdir($teacherDir, 0777, true);
        }

        $programDir = $teacherDir . $program . '/';
        if (!file_exists($programDir)) {
            mkdir($programDir, 0777, true);
        }

        if (!in_array($teacherName, $teachersProcessed)) {
            $teachersProcessed[] = $teacherName;
        }

        $evalStmt = $pdo->prepare("
            SELECT * FROM evaluations 
            WHERE teacher_name = ? AND program = ? AND section = ?
        ");
        $evalStmt->execute([$teacherName, $program, $section]);
        $evaluations = $evalStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($evaluations as $eval) {
            $filename = $programDir . 'Individual_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $eval['student_name']) . '_' . $section . '.doc';
            $result = generateIndividualReport($eval, $filename);
            if ($result['success']) {
                $individualReports++;
                $totalFiles++;
            }
        }
    }

    foreach ($summaryCombinations as $combo) {
        $teacherName = $combo['teacher_name'];
        $program = $combo['program'];

        $teacherDir = $reportsDir . $teacherName . '/';
        $programDir = $teacherDir . $program . '/';

        $summaryFilename = $programDir . 'Summary_' . $program . '_ALL_SECTIONS.doc';
        $result = generateSummaryReport($pdo, $teacherName, $program, $summaryFilename);
        if ($result['success']) {
            $summaryReports++;
            $totalFiles++;
        }
    }

    ob_end_clean();
    
    $response = [
        'success' => true,
        'message' => 'Reports generated successfully as Word documents!',
        'teachers_processed' => count($teachersProcessed),
        'individual_reports' => $individualReports,
        'summary_reports' => $summaryReports,
        'total_files' => $totalFiles,
        'reports_location' => 'reports/Teacher Evaluation Reports/Reports/'
    ];
    
    echo json_encode($response);

} catch (Exception $e) {
    ob_end_clean();
    
    error_log("Report Generation Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'details' => 'Check error_log.txt for more information'
    ]);
}

function generateIndividualReport($evaluation, $outputPath) {
    try {
        // Calculate scores WITHOUT rounding - keep as decimals
        $teachingScore = ($evaluation['q1_1'] + $evaluation['q1_2'] + $evaluation['q1_3'] + 
                         $evaluation['q1_4'] + $evaluation['q1_5'] + $evaluation['q1_6']) / 6;
        
        $managementScore = ($evaluation['q2_1'] + $evaluation['q2_2'] + 
                           $evaluation['q2_3'] + $evaluation['q2_4']) / 4;
        
        $guidanceScore = ($evaluation['q3_1'] + $evaluation['q3_2'] + 
                         $evaluation['q3_3'] + $evaluation['q3_4']) / 4;
        
        $personalScore = ($evaluation['q4_1'] + $evaluation['q4_2'] + $evaluation['q4_3'] + 
                         $evaluation['q4_4'] + $evaluation['q4_5'] + $evaluation['q4_6']) / 6;
        
        $overallScore = ($teachingScore + $managementScore + $guidanceScore + $personalScore) / 4;
        
        // Start building HTML content
        $content = getCoverPageHTML($evaluation['teacher_name'], $evaluation['program'], 
                                   $teachingScore, $managementScore, $guidanceScore, $personalScore, $overallScore);
        
        // Student Info
        $content .= '
        <h2>Detailed Evaluation Results</h2>
        <div class="stat-box">
            <p><strong>Teacher:</strong> ' . strtoupper($evaluation['teacher_name']) . '</p>
            <p><strong>Evaluated by:</strong> ' . $evaluation['student_name'] . '</p>
            <p><strong>Program:</strong> ' . $evaluation['program'] . ' | Section: ' . $evaluation['section'] . '</p>
            <p><strong>Date of Evaluation:</strong> ' . date('F j, Y', strtotime($evaluation['submitted_at'])) . '</p>
        </div>';

        // Questions table
        $questions = [
            'TEACHING COMPETENCE' => [
                'q1_1' => 'Analyzes and explains lessons without reading from the book in class',
                'q1_2' => 'Uses audio-visual and devices to support teaching',
                'q1_3' => 'Presents ideas/concepts clearly and convincingly',
                'q1_4' => 'Allows students to use concepts',
                'q1_5' => 'Gives fair tests and returns results',
                'q1_6' => 'Teaches effectively using proper language',
            ],
            'MANAGEMENT SKILLS' => [
                'q2_1' => 'Maintains orderly, disciplined and safe classroom',
                'q2_2' => 'Follows systematic class schedule',
                'q2_3' => 'Instills respect and courtesy in students',
                'q2_4' => 'Allows students to express their opinions',
            ],
            'GUIDANCE SKILLS' => [
                'q3_1' => 'Accepts students as individuals',
                'q3_2' => 'Shows confidence and self-composure',
                'q3_3' => 'Manages class and student problems',
                'q3_4' => 'Shows genuine concern for personal matters',
            ],
            'PERSONAL AND SOCIAL ATTRIBUTES' => [
                'q4_1' => 'Maintains emotional balance; not overly critical',
                'q4_2' => 'Free from habitual movements that disrupt the process',
                'q4_3' => 'Neat and presentable; Clean and orderly clothes',
                'q4_4' => 'Does not show favoritism',
                'q4_5' => 'Has good sense of humor and shows vitality',
                'q4_6' => 'Has good diction, clear and proper voice modulation',
            ]
        ];

        $categoryNum = 1;
        $totalScore = 0;
        $questionCount = 0;

        foreach ($questions as $category => $categoryQuestions) {
            $content .= '<h3>' . $category . '</h3>';
            $content .= '<table>';
            $content .= '<tr><th style="width: 10%">No.</th><th style="width: 70%">Question</th><th style="width: 20%">Score</th></tr>';
            
            $qNum = 1;
            foreach ($categoryQuestions as $key => $question) {
                $score = $evaluation[$key] ?? 0;
                $totalScore += $score;
                $questionCount++;
                
                $content .= '<tr>';
                $content .= '<td style="text-align: center;">' . $categoryNum . '.' . $qNum . '</td>';
                $content .= '<td>' . $question . '</td>';
                $content .= '<td style="text-align: center;"><strong>' . $score . '</strong></td>';
                $content .= '</tr>';
                $qNum++;
            }
            $content .= '</table><br>';
            $categoryNum++;
        }

        $averageScore = $questionCount > 0 ? $totalScore / $questionCount : 0;
        
        $content .= '<table style="background: #ffe6cc; width: 60%; margin: 20px auto;">';
        $content .= '<tr><td style="text-align: right; font-weight: bold;">AVERAGE SCORE:</td><td style="text-align: center; font-weight: bold;">' . number_format($averageScore, 2) . '</td></tr>';
        $content .= '</table><br>';

        // Comments section
        $positiveComments = !empty(trim($evaluation['positive_comments'] ?? '')) ? $evaluation['positive_comments'] : '';
        $negativeComments = !empty(trim($evaluation['negative_comments'] ?? '')) ? $evaluation['negative_comments'] : '';

        $content .= '<div class="comments-section">';
        $content .= '<h3>STUDENT COMMENTS:</h3>';
        
        // Positive feedback
        $content .= '<div class="positive-comment">';
        $content .= '<strong style="font-size: 11pt;">✅ POSITIVE FEEDBACK</strong><br><br>';
        $content .= !empty($positiveComments) ? nl2br(htmlspecialchars($positiveComments)) : '<em>No positive feedback provided.</em>';
        $content .= '</div>';
        
        // Areas for improvement
        $content .= '<div class="negative-comment">';
        $content .= '<strong style="font-size: 11pt;">⚠️ AREAS FOR IMPROVEMENT</strong><br><br>';
        $content .= !empty($negativeComments) ? nl2br(htmlspecialchars($negativeComments)) : '<em>No areas for improvement mentioned.</em>';
        $content .= '</div>';
        $content .= '</div>';

        // Add footer
        $content .= '<div class="footer" style="text-align: center; margin-top: 30px; color: #666; border-top: 1px solid #ccc; padding-top: 10px;">';
        $content .= 'Generated by the Teacher Evaluation System on ' . date('F j, Y \a\t g:i A');
        $content .= '</div>';

        // Create Word document
        createWordDocument($content, $outputPath);
        
        return ['success' => true];

    } catch (Exception $e) {
        error_log("Error generating individual report: " . $e->getMessage());
        return ['success' => false];
    }
}

function generateSummaryReport($pdo, $teacherName, $program, $outputPath) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM evaluations 
            WHERE teacher_name = ? AND program = ?
        ");
        $stmt->execute([$teacherName, $program]);
        $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($evaluations)) {
            return ['success' => false];
        }

        $sections = array_unique(array_column($evaluations, 'section'));
        sort($sections);
        $sectionsText = implode(', ', $sections);

        $totalStudents = count($evaluations);
        
        // Calculate average scores for each question
        $questions = [
            'q1_1' => ['sum' => 0, 'label' => 'Analyzes and explains lessons without reading from the book in class'],
            'q1_2' => ['sum' => 0, 'label' => 'Uses audio-visual and devices to support teaching'],
            'q1_3' => ['sum' => 0, 'label' => 'Presents ideas/concepts clearly and convincingly'],
            'q1_4' => ['sum' => 0, 'label' => 'Allows students to use concepts'],
            'q1_5' => ['sum' => 0, 'label' => 'Gives fair tests and returns results'],
            'q1_6' => ['sum' => 0, 'label' => 'Teaches effectively using proper language'],
            
            'q2_1' => ['sum' => 0, 'label' => 'Maintains orderly, disciplined and safe classroom'],
            'q2_2' => ['sum' => 0, 'label' => 'Follows systematic class schedule'],
            'q2_3' => ['sum' => 0, 'label' => 'Instills respect and courtesy in students'],
            'q2_4' => ['sum' => 0, 'label' => 'Allows students to express their opinions'],
            
            'q3_1' => ['sum' => 0, 'label' => 'Accepts students as individuals'],
            'q3_2' => ['sum' => 0, 'label' => 'Shows confidence and self-composure'],
            'q3_3' => ['sum' => 0, 'label' => 'Manages class and student problems'],
            'q3_4' => ['sum' => 0, 'label' => 'Shows genuine concern for personal matters'],
            
            'q4_1' => ['sum' => 0, 'label' => 'Maintains emotional balance; not overly critical'],
            'q4_2' => ['sum' => 0, 'label' => 'Free from habitual movements that disrupt the process'],
            'q4_3' => ['sum' => 0, 'label' => 'Neat and presentable; Clean and orderly clothes'],
            'q4_4' => ['sum' => 0, 'label' => 'Does not show favoritism'],
            'q4_5' => ['sum' => 0, 'label' => 'Has good sense of humor and shows vitality'],
            'q4_6' => ['sum' => 0, 'label' => 'Has good diction, clear and proper voice modulation'],
        ];

        foreach ($evaluations as $eval) {
            foreach ($questions as $key => $data) {
                $questions[$key]['sum'] += ($eval[$key] ?? 0);
            }
        }

        foreach ($questions as $key => $data) {
            $questions[$key]['avg'] = $data['sum'] / $totalStudents;
        }

        // Calculate category averages
        $teachingScore = ($questions['q1_1']['avg'] + $questions['q1_2']['avg'] + $questions['q1_3']['avg'] + 
                         $questions['q1_4']['avg'] + $questions['q1_5']['avg'] + $questions['q1_6']['avg']) / 6;
        
        $managementScore = ($questions['q2_1']['avg'] + $questions['q2_2']['avg'] + 
                           $questions['q2_3']['avg'] + $questions['q2_4']['avg']) / 4;
        
        $guidanceScore = ($questions['q3_1']['avg'] + $questions['q3_2']['avg'] + 
                         $questions['q3_3']['avg'] + $questions['q3_4']['avg']) / 4;
        
        $personalScore = ($questions['q4_1']['avg'] + $questions['q4_2']['avg'] + $questions['q4_3']['avg'] + 
                         $questions['q4_4']['avg'] + $questions['q4_5']['avg'] + $questions['q4_6']['avg']) / 6;
        
        $overallScore = ($teachingScore + $managementScore + $guidanceScore + $personalScore) / 4;

        // Start building HTML content
        $content = getCoverPageHTML($teacherName, $program, $teachingScore, $managementScore, $guidanceScore, $personalScore, $overallScore);
        
        // Summary info
        $content .= '
        <h2>Summary Report - Detailed Results</h2>
        <div class="stat-box">
            <p><strong>Teacher:</strong> ' . strtoupper($teacherName) . '</p>
            <p><strong>Program:</strong> ' . $program . ' (ALL SECTIONS)</p>
            <p><strong>Total Students Evaluated:</strong> ' . $totalStudents . '</p>
            <p><strong>Date Generated:</strong> ' . date('F j, Y') . '</p>
        </div>';

        // Detailed criteria table
        $categories = [
            'TEACHING COMPETENCE' => ['q1_1', 'q1_2', 'q1_3', 'q1_4', 'q1_5', 'q1_6'],
            'MANAGEMENT SKILLS' => ['q2_1', 'q2_2', 'q2_3', 'q2_4'],
            'GUIDANCE SKILLS' => ['q3_1', 'q3_2', 'q3_3', 'q3_4'],
            'PERSONAL AND SOCIAL ATTRIBUTES' => ['q4_1', 'q4_2', 'q4_3', 'q4_4', 'q4_5', 'q4_6']
        ];

        $categoryNum = 1;
        foreach ($categories as $categoryName => $questionKeys) {
            $content .= '<h3>' . $categoryName . '</h3>';
            $content .= '<table>';
            $content .= '<tr><th style="width: 10%">No.</th><th style="width: 70%">Criteria</th><th style="width: 20%">Average Score</th></tr>';
            
            $qNum = 1;
            foreach ($questionKeys as $key) {
                $content .= '<tr>';
                $content .= '<td style="text-align: center;">' . $categoryNum . '.' . $qNum . '</td>';
                $content .= '<td>' . $questions[$key]['label'] . '</td>';
                $content .= '<td style="text-align: center;"><strong>' . number_format($questions[$key]['avg'], 2) . '</strong></td>';
                $content .= '</tr>';
                $qNum++;
            }
            $content .= '</table><br>';
            $categoryNum++;
        }

        $content .= '<table style="background: #ffe6cc; width: 60%; margin: 20px auto;">';
        $content .= '<tr><td style="text-align: right; font-weight: bold;">OVERALL AVERAGE SCORE:</td><td style="text-align: center; font-weight: bold;">' . number_format($overallScore, 2) . '</td></tr>';
        $content .= '</table><br>';

        // COMMENTS SECTION
        $allPositiveComments = [];
        $allNegativeComments = [];

        foreach ($evaluations as $eval) {
            $positiveComment = !empty(trim($eval['positive_comments'] ?? '')) ? trim($eval['positive_comments']) : null;
            $negativeComment = !empty(trim($eval['negative_comments'] ?? '')) ? trim($eval['negative_comments']) : null;

            if ($positiveComment) {
                $allPositiveComments[] = $positiveComment;
            }

            if ($negativeComment) {
                $allNegativeComments[] = $negativeComment;
            }
        }

        if (!empty($allPositiveComments) || !empty($allNegativeComments)) {
            $content .= '<div class="comments-section">';
            $content .= '<h3>STUDENT COMMENTS SUMMARY</h3>';
            
            // Statistics
            $content .= '<div class="stat-box">';
            $content .= '<p><strong>Comment Statistics:</strong></p>';
            $content .= '<p>• Total Evaluations: ' . $totalStudents . '</p>';
            $content .= '<p>• With Positive Comments: ' . count($allPositiveComments) . ' (' . round(count($allPositiveComments)/$totalStudents*100) . '%)</p>';
            $content .= '<p>• With Areas for Improvement: ' . count($allNegativeComments) . ' (' . round(count($allNegativeComments)/$totalStudents*100) . '%)</p>';
            $content .= '</div>';

            // Positive feedback
            if (!empty($allPositiveComments)) {
                $content .= '<h4>✅ ALL POSITIVE FEEDBACK COMMENTS</h4>';
                $commentNum = 1;
                foreach ($allPositiveComments as $comment) {
                    $content .= '<div class="positive-comment">';
                    $content .= '<strong>' . $commentNum . '.</strong> ' . nl2br(htmlspecialchars($comment));
                    $content .= '</div>';
                    $commentNum++;
                }
            }

            // Areas for improvement
            if (!empty($allNegativeComments)) {
                $content .= '<h4>⚠️ ALL AREAS FOR IMPROVEMENT COMMENTS</h4>';
                $commentNum = 1;
                foreach ($allNegativeComments as $comment) {
                    $content .= '<div class="negative-comment">';
                    $content .= '<strong>' . $commentNum . '.</strong> ' . nl2br(htmlspecialchars($comment));
                    $content .= '</div>';
                    $commentNum++;
                }
            }
            $content .= '</div>';
        } else {
            $content .= '<p><em>No comments provided by students.</em></p>';
        }

        // Add footer
        $content .= '<div class="footer">';
        $content .= 'Generated by the Teacher Evaluation System on ' . date('F j, Y \a\t g:i A');
        $content .= '</div>';

        // Create Word document
        createWordDocument($content, $outputPath);
        
        return ['success' => true];

    } catch (Exception $e) {
        error_log("Error generating summary report: " . $e->getMessage());
        return ['success' => false];
    }
}
?>
