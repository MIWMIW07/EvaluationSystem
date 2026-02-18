<?php
// local_reports_generator.php
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

ob_start();

// Enhanced image support detection
$hasImageSupport = false;
if (extension_loaded('gd')) {
    $gd_info = gd_info();
    $hasImageSupport = isset($gd_info['PNG Support']) ? $gd_info['PNG Support'] : false;
} elseif (extension_loaded('imagick')) {
    $hasImageSupport = true;
}

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

try {
    header('Content-Type: application/json');
    
    require_once 'includes/db_connection.php';
    
    if (!class_exists('TCPDF')) {
        require_once __DIR__ . '/tcpdf/tcpdf.php';
    }
    
    class EvaluationPDF extends TCPDF {
        private $hasImageSupport;
        private $imageErrors = [];
        
        public function __construct($orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding='UTF-8', $diskcache=false, $pdfa=false) {
            parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);
            
            // Check for image support
            if (extension_loaded('gd')) {
                $gd_info = gd_info();
                $this->hasImageSupport = isset($gd_info['PNG Support']) ? $gd_info['PNG Support'] : false;
            } else {
                $this->hasImageSupport = extension_loaded('imagick');
            }
        }
        
        public function Header() {
            // Add logo to the header only if image support is available
            $logoPath = __DIR__ . '/images/logo-original.png';
            if (file_exists($logoPath) && $this->hasImageSupport) {
                try {
                    $this->Image($logoPath, 10, 5, 20, 20, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
                    $this->SetX(35); // Move right after logo
                } catch (Exception $e) {
                    $this->SetX(10);
                    $this->imageErrors[] = "Logo: " . $e->getMessage();
                }
            } else {
                $this->SetX(10);
                if (!file_exists($logoPath)) {
                    $this->imageErrors[] = "Logo file not found: $logoPath";
                }
            }
            
            $this->SetFont('helvetica', 'B', 14);
            $this->Cell(0, 10, 'PHILIPPINE TECHNOLOGICAL INSTITUTE OF SCIENCE ARTS AND TRADE, INC.', 0, 1, 'C');
            $this->SetFont('helvetica', '', 10);
            $this->Cell(0, 5, 'GMA-BRANCH (1ST Semester 2025-2026)', 0, 1, 'C');
            $this->Ln(5);
        }
        
        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
        }
        
        // Safe image method that won't crash the PDF
        private function safeImage($file, $x, $y, $w, $h) {
            if (!$this->hasImageSupport || !file_exists($file)) {
                return false;
            }
            
            try {
                $this->Image($file, $x, $y, $w, $h);
                return true;
            } catch (Exception $e) {
                $this->imageErrors[] = basename($file) . ": " . $e->getMessage();
                return false;
            }
        }
        
        public function getImageErrors() {
            return $this->imageErrors;
        }
        
        // Add cover page method with decimal scores
        public function AddEvaluationCoverPage($teacherName, $program, $teachingScore, $managementScore, $guidanceScore, $personalScore, $overallScore) {
            $this->AddPage();
            
            // Title
            $this->SetFont('helvetica', 'B', 16);
            $this->Cell(0, 15, 'Teacher Evaluation by the students result', 0, 1, 'C');
            $this->Ln(10);
            
            // Evaluation Table - with adjusted column widths
            $this->SetFont('helvetica', 'B', 11);
            
            // Table Header
            $this->SetFillColor(200, 200, 200);
            $this->Cell(45, 10, 'Indicators', 1, 0, 'C', true);
            $this->Cell(25, 10, 'Rating', 1, 0, 'C', true);
            $this->Cell(40, 10, 'Description', 1, 0, 'C', true);
            $this->Cell(80, 10, 'Interpretation', 1, 1, 'C', true);
            
            $this->SetFont('helvetica', '', 9);
            
            // Teaching Competencies Row
            $this->Cell(45, 8, '1. Teaching Competencies', 1, 0, 'L');
            $this->Cell(25, 8, number_format($teachingScore, 2), 1, 0, 'C');
            $this->Cell(40, 8, getRatingDescription($teachingScore), 1, 0, 'C');
            $this->MultiCell(80, 4, getTeachingInterpretation($teachingScore), 1, 'L');
            
            // Management Skills Row
            $this->Cell(45, 8, '2. Management Skills', 1, 0, 'L');
            $this->Cell(25, 8, number_format($managementScore, 2), 1, 0, 'C');
            $this->Cell(40, 8, getRatingDescription($managementScore), 1, 0, 'C');
            $this->MultiCell(80, 4, getManagementInterpretation($managementScore), 1, 'L');
            
            // Guidance Skills Row
            $this->Cell(45, 8, '3. Guidance Skills', 1, 0, 'L');
            $this->Cell(25, 8, number_format($guidanceScore, 2), 1, 0, 'C');
            $this->Cell(40, 8, getRatingDescription($guidanceScore), 1, 0, 'C');
            $this->MultiCell(80, 4, getGuidanceInterpretation($guidanceScore), 1, 'L');
            
            // Personal and Social Qualities/Skills Row
            $this->SetFont('helvetica', '', 8);
            $this->MultiCell(45, 4, '4. Personal and Social Qualities/Skills', 1, 'L', 0, 0);
            $this->SetFont('helvetica', '', 9);
            $this->Cell(25, 8, number_format($personalScore, 2), 1, 0, 'C');
            $this->Cell(40, 8, getRatingDescription($personalScore), 1, 0, 'C');
            $this->MultiCell(80, 4, getPersonalInterpretation($personalScore), 1, 'L');
            
            // Reset font for the last row
            $this->SetFont('helvetica', 'B', 9);
            
            // Overall Performance Row (bold)
            $this->SetFillColor(220, 220, 220);
            $this->Cell(45, 8, 'Overall Performance', 1, 0, 'L', true);
            $this->Cell(25, 8, number_format($overallScore, 2), 1, 0, 'C', true);
            $this->Cell(40, 8, getRatingDescription($overallScore), 1, 0, 'C', true);
            $this->MultiCell(80, 4, getOverallInterpretation($overallScore), 1, 'L', true);
            
            $this->Ln(8);
            
            // Rating Scale
            $this->SetFont('helvetica', '', 9);
            $this->Cell(0, 6, 'Rating used: 5 - Outstanding 4 - Very Satisfactory 3 - Satisfactory 2 - Fair 1 - Poor', 0, 1, 'L');
            
            $this->Ln(15);
            
            // Signature Sections - Clean layout
            $currentY = $this->GetY();
            
            // Tabulated by section (Left side)
            $this->SetFont('helvetica', 'B', 11);
            $this->Cell(80, 8, 'Tabulated by :', 0, 1, 'L');
            $this->Ln(8);
            
            // Add Joanne P. Castro signature
            $signature1Path = __DIR__ . '/images/Picture1.png';
            $signature1Added = $this->safeImage($signature1Path, 20, $this->GetY(), 40, 15);
            
            $this->SetY($this->GetY() + 18);
            $this->SetFont('helvetica', 'B', 10);
            $this->Cell(80, 6, 'Joanne P. Castro', 0, 1, 'L');
            $this->SetFont('helvetica', '', 9);
            $this->Cell(80, 6, 'Guidance Associate', 0, 1, 'L');
            
            // Reset Y position for second signature
            $this->SetY($currentY);
            
            // Noted by section (Right side)
            $this->SetX(110);
            $this->SetFont('helvetica', 'B', 11);
            $this->Cell(80, 8, 'Noted by :', 0, 1, 'L');
            $this->SetX(110);
            $this->Ln(8);
            
            // Add Myra V. Jumantoc signature
            $signature2Path = __DIR__ . '/images/Picture2.png';
            $signature2Added = $this->safeImage($signature2Path, 120, $this->GetY(), 40, 15);
            
            $this->SetY($this->GetY() + 18);
            $this->SetX(110);
            $this->SetFont('helvetica', 'B', 10);
            $this->Cell(80, 6, 'Myra V. Jumantoc', 0, 1, 'L');
            $this->SetX(110);
            $this->SetFont('helvetica', '', 9);
            $this->Cell(80, 6, 'HR Head', 0, 1, 'L');
        }
    }

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
    $allImageErrors = [];

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
            $filename = $programDir . 'Individual_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $eval['student_name']) . '_' . $section . '.pdf';
            $result = generateIndividualReport($eval, $filename);
            if ($result['success']) {
                $individualReports++;
                $totalFiles++;
            }
            if (!empty($result['image_errors'])) {
                $allImageErrors = array_merge($allImageErrors, $result['image_errors']);
            }
        }
    }

    foreach ($summaryCombinations as $combo) {
        $teacherName = $combo['teacher_name'];
        $program = $combo['program'];

        $teacherDir = $reportsDir . $teacherName . '/';
        $programDir = $teacherDir . $program . '/';

        $summaryFilename = $programDir . 'Summary_' . $program . '_ALL_SECTIONS.pdf';
        $result = generateSummaryReport($pdo, $teacherName, $program, $summaryFilename);
        if ($result['success']) {
            $summaryReports++;
            $totalFiles++;
        }
        if (!empty($result['image_errors'])) {
            $allImageErrors = array_merge($allImageErrors, $result['image_errors']);
        }
    }

    ob_end_clean();
    
    $response = [
        'success' => true,
        'message' => 'Reports generated successfully!',
        'teachers_processed' => count($teachersProcessed),
        'individual_reports' => $individualReports,
        'summary_reports' => $summaryReports,
        'total_files' => $totalFiles,
        'reports_location' => 'reports/Teacher Evaluation Reports/Reports/'
    ];
    
    // Add warnings if there were image issues
    if (!$hasImageSupport) {
        $response['warning'] = 'GD or Imagick extension not available. Images were not included in the PDFs. Please contact your hosting provider to enable PHP GD extension.';
    }
    
    if (!empty($allImageErrors)) {
        $response['image_errors'] = array_unique($allImageErrors);
    }
    
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
    $imageErrors = [];
    
    try {
        $pdf = new EvaluationPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Teacher Evaluation System');
        $pdf->SetTitle("Evaluation - " . $evaluation['student_name']);
        
        $pdf->SetMargins(10, 40, 10);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(TRUE, 15);
        
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
        
        // Add cover page with evaluation results (decimal scores)
        $pdf->AddEvaluationCoverPage($evaluation['teacher_name'], $evaluation['program'], 
                                   $teachingScore, $managementScore, $guidanceScore, $personalScore, $overallScore);
        
        // Collect image errors
        $imageErrors = $pdf->getImageErrors();
        
        // Start detailed evaluation content on page 2
        $pdf->AddPage();
        
        // Reset margins for detailed content
        $pdf->SetMargins(10, 20, 10);
        
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, "Name: " . strtoupper($evaluation['teacher_name']), 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, "Student: " . $evaluation['student_name'], 0, 1);
        $pdf->Cell(0, 5, "Program: " . $evaluation['program'] . " | Section: " . $evaluation['section'], 0, 1);
        $pdf->Cell(0, 5, "Date: " . date('F j, Y', strtotime($evaluation['submitted_at'])), 0, 1);
        $pdf->Ln(5);

        // English questions with adjusted column widths
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
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetFillColor(220, 220, 220);
            $pdf->Cell(0, 7, $category, 1, 1, 'L', true);
            
            $pdf->SetFont('helvetica', '', 8);
            $qNum = 1;
            foreach ($categoryQuestions as $key => $question) {
                $score = $evaluation[$key] ?? 0;
                $totalScore += $score;
                $questionCount++;
                
                $pdf->Cell(10, 6, "$categoryNum.$qNum", 1, 0, 'C');
                $pdf->Cell(140, 6, $question, 1, 0, 'L');
                $pdf->Cell(40, 6, $score, 1, 1, 'C');
                $qNum++;
            }
            $categoryNum++;
            $pdf->Ln(2);
        }

        $averageScore = $questionCount > 0 ? $totalScore / $questionCount : 0;
        
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetFillColor(255, 200, 150);
        $pdf->Cell(150, 8, 'AVERAGE SCORE', 1, 0, 'R', true);
        $pdf->Cell(40, 8, number_format($averageScore, 2), 1, 1, 'C', true);

        $pdf->Ln(5);

        // Comments section - display only comments without student names
        $positiveComments = !empty(trim($evaluation['positive_comments'] ?? '')) ? $evaluation['positive_comments'] : '';
        $negativeComments = !empty(trim($evaluation['negative_comments'] ?? '')) ? $evaluation['negative_comments'] : '';

        if (!empty($positiveComments) || !empty($negativeComments)) {
            // Check if we need a new page
            if ($pdf->GetY() > 230) {
                $pdf->AddPage();
            }
            
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 6, 'STUDENT COMMENTS:', 0, 1);
            $pdf->Ln(2);

            // POSITIVE FEEDBACK - Full width
            if (!empty($positiveComments)) {
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->SetFillColor(200, 230, 200);
                $pdf->Cell(0, 7, 'POSITIVE FEEDBACK', 1, 1, 'L', true);
                
                $pdf->SetFont('helvetica', '', 8);
                $pdf->MultiCell(0, 5, $positiveComments, 1, 'L', false, 1);
            } else {
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->SetFillColor(200, 230, 200);
                $pdf->Cell(0, 7, 'POSITIVE FEEDBACK', 1, 1, 'L', true);
                
                $pdf->SetFont('helvetica', 'I', 8);
                $pdf->Cell(0, 6, 'No positive feedback provided.', 1, 1, 'C');
            }
            
            $pdf->Ln(2);
            
            // AREAS FOR IMPROVEMENT - Full width
            if (!empty($negativeComments)) {
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->SetFillColor(255, 200, 200);
                $pdf->Cell(0, 7, 'AREAS FOR IMPROVEMENT', 1, 1, 'L', true);
                
                $pdf->SetFont('helvetica', '', 8);
                $pdf->MultiCell(0, 5, $negativeComments, 1, 'L', false, 1);
            } else {
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->SetFillColor(255, 200, 200);
                $pdf->Cell(0, 7, 'AREAS FOR IMPROVEMENT', 1, 1, 'L', true);
                
                $pdf->SetFont('helvetica', 'I', 8);
                $pdf->Cell(0, 6, 'No areas for improvement mentioned.', 1, 1, 'C');
            }
            
        } else {
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 6, 'STUDENT COMMENTS:', 0, 1);
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(0, 6, 'No comments provided by student.', 0, 1, 'C');
        }

        $pdf->Output($outputPath, 'F');
        return ['success' => true, 'image_errors' => $imageErrors];

    } catch (Exception $e) {
        error_log("Error generating individual report: " . $e->getMessage());
        return ['success' => false, 'image_errors' => $imageErrors];
    }
}

function generateSummaryReport($pdo, $teacherName, $program, $outputPath) {
    $imageErrors = [];
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM evaluations 
            WHERE teacher_name = ? AND program = ?
        ");
        $stmt->execute([$teacherName, $program]);
        $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($evaluations)) {
            return ['success' => false, 'image_errors' => $imageErrors];
        }

        $sections = array_unique(array_column($evaluations, 'section'));
        sort($sections);
        $sectionsText = implode(', ', $sections);

        $totalStudents = count($evaluations);
        
        // Calculate average scores for each question WITHOUT rounding
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

        // Calculate category averages for cover page WITHOUT rounding
        $teachingScore = ($questions['q1_1']['avg'] + $questions['q1_2']['avg'] + $questions['q1_3']['avg'] + 
                         $questions['q1_4']['avg'] + $questions['q1_5']['avg'] + $questions['q1_6']['avg']) / 6;
        
        $managementScore = ($questions['q2_1']['avg'] + $questions['q2_2']['avg'] + 
                           $questions['q2_3']['avg'] + $questions['q2_4']['avg']) / 4;
        
        $guidanceScore = ($questions['q3_1']['avg'] + $questions['q3_2']['avg'] + 
                         $questions['q3_3']['avg'] + $questions['q3_4']['avg']) / 4;
        
        $personalScore = ($questions['q4_1']['avg'] + $questions['q4_2']['avg'] + $questions['q4_3']['avg'] + 
                         $questions['q4_4']['avg'] + $questions['q4_5']['avg'] + $questions['q4_6']['avg']) / 6;
        
        $overallScore = ($teachingScore + $managementScore + $guidanceScore + $personalScore) / 4;

        $pdf = new EvaluationPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Teacher Evaluation System');
        $pdf->SetTitle("Summary Report - $teacherName - $program");

        // Set margins for cover page
        $pdf->SetMargins(10, 40, 10);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(TRUE, 15);
        
        // Increase cell height ratio for better text display
        $pdf->setCellHeightRatio(1.5);

        // Add cover page with evaluation results (decimal scores)
        $pdf->AddEvaluationCoverPage($teacherName, $program, $teachingScore, $managementScore, $guidanceScore, $personalScore, $overallScore);

        // Collect image errors
        $imageErrors = $pdf->getImageErrors();

        // Start detailed content on page 2
        $pdf->AddPage();
        
        // Reset margins for detailed content
        $pdf->SetMargins(10, 20, 10);

        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, "Name: " . strtoupper($teacherName), 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, "Program: $program (ALL SECTIONS)", 0, 1);
        $pdf->Cell(0, 5, "Sections Included: $sectionsText", 0, 1);
        $pdf->Cell(0, 5, "Total Students Evaluated: $totalStudents", 0, 1);
        $pdf->Ln(5);

        // Detailed criteria table with adjusted column widths
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('helvetica', 'B', 9);
        
        $pdf->SetFillColor(220, 220, 220);
        $pdf->Cell(10, 7, '', 1, 0, 'C', true);
        $pdf->Cell(135, 7, 'TEACHING COMPETENCE', 1, 0, 'L', true);
        $pdf->Cell(45, 7, 'SCORE', 1, 1, 'C', true);

        $pdf->SetFont('helvetica', '', 8);
        $counter = 1;
        foreach (['q1_1', 'q1_2', 'q1_3', 'q1_4', 'q1_5', 'q1_6'] as $q) {
            $pdf->Cell(10, 6, '1.' . $counter, 1, 0, 'C');
            $pdf->MultiCell(135, 6, $questions[$q]['label'], 1, 'L');
            $pdf->SetXY($pdf->GetX() + 145, $pdf->GetY() - 6);
            $pdf->Cell(45, 6, number_format($questions[$q]['avg'], 2), 1, 1, 'C');
            $counter++;
        }

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(220, 220, 220);
        $pdf->Cell(10, 7, '', 1, 0, 'C', true);
        $pdf->Cell(135, 7, 'MANAGEMENT SKILLS', 1, 0, 'L', true);
        $pdf->Cell(45, 7, '', 1, 1, 'C', true);

        $pdf->SetFont('helvetica', '', 8);
        $counter = 1;
        foreach (['q2_1', 'q2_2', 'q2_3', 'q2_4'] as $q) {
            $pdf->Cell(10, 6, '2.' . $counter, 1, 0, 'C');
            $pdf->MultiCell(135, 6, $questions[$q]['label'], 1, 'L');
            $pdf->SetXY($pdf->GetX() + 145, $pdf->GetY() - 6);
            $pdf->Cell(45, 6, number_format($questions[$q]['avg'], 2), 1, 1, 'C');
            $counter++;
        }

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(220, 220, 220);
        $pdf->Cell(10, 7, '', 1, 0, 'C', true);
        $pdf->Cell(135, 7, 'GUIDANCE SKILLS', 1, 0, 'L', true);
        $pdf->Cell(45, 7, '', 1, 1, 'C', true);

        $pdf->SetFont('helvetica', '', 8);
        $counter = 1;
        foreach (['q3_1', 'q3_2', 'q3_3', 'q3_4'] as $q) {
            $pdf->Cell(10, 6, '3.' . $counter, 1, 0, 'C');
            $pdf->MultiCell(135, 6, $questions[$q]['label'], 1, 'L');
            $pdf->SetXY($pdf->GetX() + 145, $pdf->GetY() - 6);
            $pdf->Cell(45, 6, number_format($questions[$q]['avg'], 2), 1, 1, 'C');
            $counter++;
        }

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(220, 220, 220);
        $pdf->Cell(10, 7, '', 1, 0, 'C', true);
        $pdf->Cell(135, 7, 'PERSONAL AND SOCIAL ATTRIBUTES', 1, 0, 'L', true);
        $pdf->Cell(45, 7, '', 1, 1, 'C', true);

        $pdf->SetFont('helvetica', '', 8);
        $counter = 1;
        foreach (['q4_1', 'q4_2', 'q4_3', 'q4_4', 'q4_5', 'q4_6'] as $q) {
            $pdf->Cell(10, 6, '4.' . $counter, 1, 0, 'C');
            $pdf->MultiCell(135, 6, $questions[$q]['label'], 1, 'L');
            $pdf->SetXY($pdf->GetX() + 145, $pdf->GetY() - 6);
            $pdf->Cell(45, 6, number_format($questions[$q]['avg'], 2), 1, 1, 'C');
            $counter++;
        }

        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetFillColor(255, 200, 150);
        $pdf->Cell(145, 8, 'OVERALL AVERAGE', 1, 0, 'R', true);
        $pdf->Cell(45, 8, number_format($overallScore, 2), 1, 1, 'C', true);

        $pdf->Ln(5);

        // COMMENTS SECTION - FULL WIDTH, NO TRUNCATION
        
        // Collect all comments
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

        // Display comments if there are any
        if (!empty($allPositiveComments) || !empty($allNegativeComments)) {
            // Check if we need a new page
            if ($pdf->GetY() > 210) {
                $pdf->AddPage();
            }
            
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(0, 8, 'STUDENT COMMENTS SUMMARY:', 0, 1, 'L');
            $pdf->Ln(3);

            // Statistics
            $totalWithPositive = count($allPositiveComments);
            $totalWithNegative = count($allNegativeComments);
            
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell(0, 6, 'Statistics:', 0, 1);
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(0, 5, "• Total Evaluations: {$totalStudents}", 0, 1);
            $pdf->Cell(0, 5, "• With Positive Comments: {$totalWithPositive}", 0, 1);
            $pdf->Cell(0, 5, "• With Areas for Improvement: {$totalWithNegative}", 0, 1);
            $pdf->Ln(5);

            // ALL POSITIVE FEEDBACK
            if (!empty($allPositiveComments)) {
                if ($pdf->GetY() > 240) {
                    $pdf->AddPage();
                }
                
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->SetFillColor(200, 230, 200);
                $pdf->Cell(0, 8, 'ALL POSITIVE FEEDBACK COMMENTS', 1, 1, 'C', true);
                
                $pdf->SetFont('helvetica', '', 9);
                
                $commentNum = 1;
                foreach ($allPositiveComments as $comment) {
                    // Check page space before each comment
                    if ($pdf->GetY() > 260) {
                        $pdf->AddPage();
                        $pdf->SetFont('helvetica', 'B', 10);
                        $pdf->SetFillColor(200, 230, 200);
                        $pdf->Cell(0, 8, 'POSITIVE FEEDBACK (continued)', 1, 1, 'C', true);
                        $pdf->SetFont('helvetica', '', 9);
                    }
                    
                    // Display full comment with number - NO TRUNCATION
                    $pdf->MultiCell(0, 6, "{$commentNum}. {$comment}", 1, 'L', false, 1);
                    $commentNum++;
                }
                
                $pdf->Ln(5);
            }
            
            // ALL AREAS FOR IMPROVEMENT
            if (!empty($allNegativeComments)) {
                if ($pdf->GetY() > 240) {
                    $pdf->AddPage();
                }
                
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->SetFillColor(255, 200, 200);
                $pdf->Cell(0, 8, 'ALL AREAS FOR IMPROVEMENT COMMENTS', 1, 1, 'C', true);
                
                $pdf->SetFont('helvetica', '', 9);
                
                $commentNum = 1;
                foreach ($allNegativeComments as $comment) {
                    // Check page space before each comment
                    if ($pdf->GetY() > 260) {
                        $pdf->AddPage();
                        $pdf->SetFont('helvetica', 'B', 10);
                        $pdf->SetFillColor(255, 200, 200);
                        $pdf->Cell(0, 8, 'AREAS FOR IMPROVEMENT (continued)', 1, 1, 'C', true);
                        $pdf->SetFont('helvetica', '', 9);
                    }
                    
                    // Display full comment with number - NO TRUNCATION
                    $pdf->MultiCell(0, 6, "{$commentNum}. {$comment}", 1, 'L', false, 1);
                    $commentNum++;
                }
            }
            
        } else {
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(0, 8, 'STUDENT COMMENTS SUMMARY:', 0, 1);
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(0, 8, 'No comments provided by students.', 0, 1, 'C');
        }

        $pdf->Output($outputPath, 'F');
        return ['success' => true, 'image_errors' => $imageErrors];

    } catch (Exception $e) {
        error_log("Error generating summary report: " . $e->getMessage());
        return ['success' => false, 'image_errors' => $imageErrors];
    }
}
?>
