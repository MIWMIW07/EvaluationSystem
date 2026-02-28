<?php
// bot_evaluation_form.php - Classroom Observation Form for BOT
session_start();

// Check if user is logged in and is a BOT
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'bot') {
    header('Location: bot_login.php');
    exit;
}

require_once 'includes/db_connection.php';

$pdo = getPDO();

// Get teacher information from URL
$teacher_name = isset($_GET['teacher']) ? trim($_GET['teacher']) : '';
$branch = isset($_GET['branch']) ? trim($_GET['branch']) : '';
$department = isset($_GET['department']) ? trim($_GET['department']) : '';
$specialization = isset($_GET['specialization']) ? trim($_GET['specialization']) : '';
$subjects = isset($_GET['subjects']) ? trim($_GET['subjects']) : '';

// Validate parameters
if (empty($teacher_name) || empty($branch)) {
    header('Location: bot_dashboard.php?error=missing_teacher');
    exit;
}

// Check if BOT has already evaluated this teacher
$checkStmt = $pdo->prepare("
    SELECT id FROM bot_evaluations 
    WHERE bot_username = ? AND teacher_name = ?
");
$checkStmt->execute([$_SESSION['username'], $teacher_name]);
if ($checkStmt->fetch()) {
    header('Location: bot_dashboard.php?error=already_evaluated');
    exit;
}

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate all required fields
        $ratings = [];
        
        // Section A: Instructional Competence (6 questions)
        for ($i = 1; $i <= 6; $i++) {
            $rating = intval($_POST["a$i"] ?? 0);
            if ($rating < 1 || $rating > 4) {
                throw new Exception("Invalid rating for question A.$i");
            }
            $ratings["a$i"] = $rating;
        }
        
        // Section B: Classroom Management (5 questions)
        for ($i = 1; $i <= 5; $i++) {
            $rating = intval($_POST["b$i"] ?? 0);
            if ($rating < 1 || $rating > 4) {
                throw new Exception("Invalid rating for question B.$i");
            }
            $ratings["b$i"] = $rating;
        }
        
        // Section C: Professionalism (5 questions)
        for ($i = 1; $i <= 5; $i++) {
            $rating = intval($_POST["c$i"] ?? 0);
            if ($rating < 1 || $rating > 4) {
                throw new Exception("Invalid rating for question C.$i");
            }
            $ratings["c$i"] = $rating;
        }
        
        // Get comments
        $comments = trim($_POST['comments'] ?? '');
        
        // Insert into database
        $insertStmt = $pdo->prepare("
            INSERT INTO bot_evaluations (
                bot_username, bot_name, teacher_name, branch, department, area_of_specialization, subjects_handled,
                a1, a2, a3, a4, a5, a6,
                b1, b2, b3, b4, b5,
                c1, c2, c3, c4, c5,
                comments
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?
            )
        ");
        
        $insertStmt->execute([
            $_SESSION['username'],
            $_SESSION['full_name'],
            $teacher_name,
            $branch,
            $department,        // NEW
            $specialization,
            $subjects,          // NEW
            $ratings['a1'], $ratings['a2'], $ratings['a3'], $ratings['a4'], $ratings['a5'], $ratings['a6'],
            $ratings['b1'], $ratings['b2'], $ratings['b3'], $ratings['b4'], $ratings['b5'],
            $ratings['c1'], $ratings['c2'], $ratings['c3'], $ratings['c4'], $ratings['c5'],
            $comments
        ]);
        
        // Log activity
        logActivity($_SESSION['username'], 'bot', 'Submit Evaluation', "Evaluated teacher: $teacher_name");
        
        $success = "Evaluation submitted successfully! Thank you for your feedback.";
        
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
        error_log("BOT Evaluation Error: " . $e->getMessage());
    }
}

// Helper function for safe display
function safe_display($value, $default = '') {
    return !empty($value) ? htmlspecialchars($value) : $default;
}

// Define questions
$sectionA_questions = [
    'A1' => 'Applies teaching strategy that caters learner\'s need in developing creative and critical thinking (Lower Order Thinking Skills to Higher Order Thinking Skills).',
    'A2' => 'Curriculum Knowledge: Effective application of subject matter knowledge within and across curriculum areas.',
    'A3' => 'Collaboration and Integration is evident to create real-life learning.',
    'A4' => 'Possesses a command of language of instruction.',
    'A5' => 'Uses differentiated, developmentally appropriate learning experiences to address learners\' gender, needs, strengths, interests and experiences.',
    'A6' => 'Use a range of teaching strategies that enhance learner achievment in literacy and numeracy skills.'
];

$sectionB_questions = [
    'B1' => 'Uses an approach that ensures student collaboration and participation in the class.',
    'B2' => 'Uses appropriate teaching and learning methods.',
    'B3' => 'Maintain supportive learning environments that nurture and inspire learners to participate, cooperate and collaborate in continued learning.',
    'B4' => 'The teacher processes students\' understanding by asking clarifying or critical thinking questions related to the lesson being discussed.',
    'B5' => 'Use strategies for providing timely, accurate and constructive feedback to improve learner performance'
];

$sectionC_questions = [
    'C1' => 'Confidence in delivering the subject is evident all throughout the discussion.',
    'C2' => 'Neat in appearance; has professional bearing, wears appropriate attire.',
    'C3' => 'Assumes responsibility and exhibits initiative, resourcefulness and commitment.',
    'C4' => 'Maintains composure in the classroom.',
    'C5' => 'Exudes an attitude that commands student\'s attention and respect.'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classroom Observation Form - <?php echo safe_display($teacher_name); ?></title>
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
            padding-top: 70px;
        }

        .progress-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: white;
            padding: 10px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .progress-bar {
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin: 10px 0;
        }

        .progress {
            height: 100%;
            background: linear-gradient(90deg, #800000, #A52A2A);
            width: 0%;
            transition: width 0.5s ease;
        }

        .progress-text {
            text-align: center;
            font-weight: bold;
            color: #800000;
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
            font-size: 28px;
        }

        .teacher-info {
            background: #f9f5eb;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 5px solid #800000;
        }

        .teacher-info h3 {
            color: #800000;
            margin-bottom: 10px;
        }

        .teacher-info p {
            margin: 5px 0;
            color: #666;
        }

        .rating-scale {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            padding: 15px;
            background: linear-gradient(135deg, #800000, #A52A2A);
            border-radius: 8px;
            color: white;
            font-weight: bold;
        }

        .rating-item {
            text-align: center;
            flex: 1;
        }

        .section {
            margin-bottom: 40px;
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
            font-weight: normal;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background: #800000;
            color: white;
            padding: 12px;
            text-align: left;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: middle;
        }

        tr:hover {
            background: #f9f5eb;
        }

        .rating-options {
            display: flex;
            justify-content: space-between;
            gap: 5px;
        }

        .rating-options label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 70px;
            height: 70px;
            cursor: pointer;
            border: 2px solid #800000;
            border-radius: 8px;
            transition: all 0.3s;
            background: white;
        }

        .rating-options label:hover {
            background: #f0e6e6;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(128,0,0,0.2);
        }

        .rating-options input[type="radio"] {
            display: none;
        }

        .rating-options input[type="radio"]:checked + label {
            background: #800000;
            color: white;
            border-color: #660000;
        }

        .rating-number {
            font-size: 24px;
            font-weight: bold;
        }

        .rating-desc {
            font-size: 11px;
            text-align: center;
        }

        .comments-section {
            margin: 30px 0;
        }

        .comments-section h3 {
            color: #800000;
            margin-bottom: 10px;
        }

        textarea {
            width: 100%;
            height: 150px;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            resize: vertical;
            transition: border-color 0.3s;
        }

        textarea:focus {
            outline: none;
            border-color: #800000;
        }

        .character-count {
            text-align: right;
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }

        .submit-btn {
            display: block;
            width: 300px;
            margin: 40px auto 20px;
            padding: 15px;
            background: linear-gradient(135deg, #800000, #A52A2A);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(128,0,0,0.3);
        }

        .submit-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(128,0,0,0.4);
        }

        .submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            box-shadow: none;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 5px solid #28a745;
            text-align: center;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 5px solid #dc3545;
            text-align: center;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 14px;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #800000;
            text-decoration: none;
            font-weight: bold;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .rating-options label {
                width: 50px;
                height: 60px;
            }
            
            .rating-number {
                font-size: 18px;
            }
            
            .rating-desc {
                font-size: 9px;
            }
            
            .submit-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="progress-container">
        <div class="progress-text" id="progressText">Completion: 0%</div>
        <div class="progress-bar">
            <div class="progress" id="progressBar"></div>
        </div>
    </div>

    <div class="container">
        <div class="header">
            <h1>Classroom Observation Evaluation</h1>
            <p>Board of Trustees - Faculty Evaluation Form</p>
        </div>

        <?php if ($success): ?>
            <div class="success-message">
                <h3>✅ Evaluation Submitted Successfully!</h3>
                <p><?php echo safe_display($success); ?></p>
                <a href="bot_dashboard.php" class="back-link">← Back to Dashboard</a>
            </div>
        <?php elseif ($error): ?>
            <div class="error-message">
                <h3>❌ Error</h3>
                <p><?php echo safe_display($error); ?></p>
                <a href="bot_dashboard.php" class="back-link">← Back to Dashboard</a>
            </div>
        <?php else: ?>

        <div class="teacher-info">
            <h3>👨‍🏫 Teacher Information</h3>
            <p><strong>Name:</strong> <?php echo safe_display($teacher_name); ?></p>
            <p><strong>Branch:</strong> <?php echo safe_display($branch); ?></p>
            <p><strong>Department:</strong> <?php echo safe_display($department); ?></p>
            <p><strong>Area of Specialization:</strong> <?php echo safe_display($specialization); ?></p>
            <p><strong>Subjects Handled:</strong> <?php echo safe_display($subjects); ?></p>
            <p><strong>Evaluator:</strong> <?php echo safe_display($_SESSION['full_name']); ?> (BOT)</p>
        </div>   

        <div class="rating-scale">
            <div class="rating-item">4 - Excellent</div>
            <div class="rating-item">3 - Good</div>
            <div class="rating-item">2 - Fair</div>
            <div class="rating-item">1 - Needs Improvement</div>
        </div>

        <form id="evaluationForm" method="POST" action="">
            <!-- Section A: Instructional Competence (40%) -->
            <div class="section">
                <h2>A. Instructional Competence (40%)</h2>
                <table>
                    <thead>
                        <tr>
                            <th width="60%">Indicator</th>
                            <th width="40%">Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sectionA_questions as $key => $question): ?>
                        <tr>
                            <td><?php echo $key . '. ' . htmlspecialchars($question); ?></td>
                            <td>
                                <div class="rating-options">
                                    <?php for ($i = 4; $i >= 1; $i--): ?>
                                        <input type="radio" name="<?php echo strtolower($key); ?>" value="<?php echo $i; ?>" id="<?php echo strtolower($key).'_'.$i; ?>" required>
                                        <label for="<?php echo strtolower($key).'_'.$i; ?>">
                                            <span class="rating-number"><?php echo $i; ?></span>
                                            <span class="rating-desc">
                                                <?php 
                                                echo $i == 4 ? 'Excellent' : 
                                                     ($i == 3 ? 'Good' : 
                                                     ($i == 2 ? 'Fair' : 'Needs<br>Improvement'));
                                                ?>
                                            </span>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Section B: Classroom Management (30%) -->
            <div class="section">
                <h2>B. Classroom Management and Environment (30%)</h2>
                <table>
                    <thead>
                        <tr>
                            <th width="60%">Indicator</th>
                            <th width="40%">Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sectionB_questions as $key => $question): ?>
                        <tr>
                            <td><?php echo $key . '. ' . htmlspecialchars($question); ?></td>
                            <td>
                                <div class="rating-options">
                                    <?php for ($i = 4; $i >= 1; $i--): ?>
                                        <input type="radio" name="<?php echo strtolower($key); ?>" value="<?php echo $i; ?>" id="<?php echo strtolower($key).'_'.$i; ?>" required>
                                        <label for="<?php echo strtolower($key).'_'.$i; ?>">
                                            <span class="rating-number"><?php echo $i; ?></span>
                                            <span class="rating-desc">
                                                <?php 
                                                echo $i == 4 ? 'Excellent' : 
                                                     ($i == 3 ? 'Good' : 
                                                     ($i == 2 ? 'Fair' : 'Needs<br>Improvement'));
                                                ?>
                                            </span>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Section C: Professionalism (30%) -->
            <div class="section">
                <h2>C. Professionalism / Teacher's Ethics (30%)</h2>
                <table>
                    <thead>
                        <tr>
                            <th width="60%">Indicator</th>
                            <th width="40%">Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sectionC_questions as $key => $question): ?>
                        <tr>
                            <td><?php echo $key . '. ' . htmlspecialchars($question); ?></td>
                            <td>
                                <div class="rating-options">
                                    <?php for ($i = 4; $i >= 1; $i--): ?>
                                        <input type="radio" name="<?php echo strtolower($key); ?>" value="<?php echo $i; ?>" id="<?php echo strtolower($key).'_'.$i; ?>" required>
                                        <label for="<?php echo strtolower($key).'_'.$i; ?>">
                                            <span class="rating-number"><?php echo $i; ?></span>
                                            <span class="rating-desc">
                                                <?php 
                                                echo $i == 4 ? 'Excellent' : 
                                                     ($i == 3 ? 'Good' : 
                                                     ($i == 2 ? 'Fair' : 'Needs<br>Improvement'));
                                                ?>
                                            </span>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Comments and Recommendations -->
            <div class="comments-section">
                <h3>Comments and Recommendations</h3>
                <textarea name="comments" id="comments" placeholder="Please provide your comments and recommendations regarding this teacher's performance..." required></textarea>
                <div class="character-count" id="commentCount">0 characters</div>
            </div>

            <button type="submit" class="submit-btn" id="submitBtn">Submit Evaluation</button>
        </form>

        <div style="text-align: center;">
            <a href="bot_dashboard.php" class="back-link">← Cancel and Back to Dashboard</a>
        </div>

        <?php endif; ?>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> PHILTECH GMA - Board of Trustees Evaluation System</p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('evaluationForm');
            if (!form) return;

            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');
            const submitBtn = document.getElementById('submitBtn');
            const comments = document.getElementById('comments');
            const commentCount = document.getElementById('commentCount');

            // Update comment character count
            if (comments) {
                comments.addEventListener('input', function() {
                    const length = this.value.length;
                    commentCount.textContent = length + ' characters';
                    
                    if (length < 30) {
                        commentCount.style.color = '#dc3545';
                    } else {
                        commentCount.style.color = '#28a745';
                    }
                    
                    updateProgress();
                });
            }

            // Update progress function
            function updateProgress() {
                let totalFields = 0;
                let completedFields = 0;

                // Count all radio groups (16 total questions: 6+5+5)
                const radioGroups = new Set();
                const radioInputs = form.querySelectorAll('input[type="radio"]');
                radioInputs.forEach(input => radioGroups.add(input.name));
                
                totalFields += radioGroups.size;
                
                // Check each radio group
                radioGroups.forEach(name => {
                    const checked = form.querySelector(`input[name="${name}"]:checked`);
                    if (checked) completedFields++;
                });

                // Check comments
                totalFields += 1; // Comments field
                if (comments && comments.value.trim().length >= 30) {
                    completedFields++;
                }

                const progress = totalFields > 0 ? Math.round((completedFields / totalFields) * 100) : 0;
                
                progressBar.style.width = progress + '%';
                progressText.textContent = `Completion: ${progress}%`;
                
                // Enable/disable submit button
                submitBtn.disabled = progress < 100;
            }

            // Add event listeners to all radio buttons
            form.querySelectorAll('input[type="radio"]').forEach(radio => {
                radio.addEventListener('change', updateProgress);
            });

            // Initial progress update
            updateProgress();

            // Form validation before submit
            form.addEventListener('submit', function(e) {
                let valid = true;
                const errors = [];

                // Check all radio groups
                const radioGroups = new Set();
                const radioInputs = form.querySelectorAll('input[type="radio"]');
                radioInputs.forEach(input => radioGroups.add(input.name));

                radioGroups.forEach(name => {
                    const checked = form.querySelector(`input[name="${name}"]:checked`);
                    if (!checked) {
                        valid = false;
                        errors.push(`Please rate all indicators`);
                    }
                });

                // Check comments
                if (!comments || comments.value.trim().length < 30) {
                    valid = false;
                    errors.push('Comments must be at least 30 characters long');
                    if (comments) comments.style.border = '2px solid red';
                } else if (comments) {
                    comments.style.border = '';
                }

                if (!valid) {
                    e.preventDefault();
                    alert('Please complete all required fields:\n\n' + errors.join('\n'));
                    return false;
                }

                // Show loading state
                submitBtn.textContent = 'Submitting...';
                submitBtn.disabled = true;
            });
        });
    </script>
</body>
</html>
