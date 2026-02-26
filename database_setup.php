<?php
// database_setup.php
// FINAL VERSION - Complete database setup with all real teacher assignments
// Students data stored in Google Sheets, Teacher Assignments in PostgreSQL

require_once __DIR__ . '/includes/db_connection.php';

// Check if database is available
if (!isDatabaseAvailable()) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Database Setup - No Connection</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f0f0f0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
            .error { color: #e74c3c; font-weight: bold; }
            .info { background: #e8f4fd; padding: 15px; border-radius: 5px; margin: 15px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Database Setup</h1>
            <div class="error">❌ Database connection not available</div>
            
            <div class="info">
                <h3>Hybrid System Information:</h3>
                <ul>
                    <li><strong>Students Data:</strong> Stored in Google Sheets (Student_ID, Last_Name, First_Name, Section, Program, Username, Password)</li>
                    <li><strong>Teachers List:</strong> Stored in PostgreSQL (teacher_assignments table)</li>
                    <li><strong>Evaluations:</strong> Stored in PostgreSQL (evaluations table)</li>
                </ul>
                
                <p><strong>Next Steps:</strong></p>
                <ol>
                    <li>Set up your PostgreSQL database on Railway</li>
                    <li>Ensure <code>DATABASE_URL</code> environment variable is set</li>
                    <li>Reload this setup to create the necessary tables</li>
                </ol>
            </div>
            
            <p><a href="index.php" style="background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">← Back to Home</a></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Function to clean teacher names
function cleanTeacherName($name) {
    $name = trim($name);
    $name = str_replace(['**', '*'], '', $name); // Remove markdown formatting
    $name = preg_replace('/\s+/', ' ', $name); // Replace multiple spaces with single space
    $name = preg_replace('/[,\s]+$/', '', $name); // Remove trailing commas and spaces
    return trim($name);
}

// Function to insert all real teacher assignments
function insertAllTeacherAssignments($pdo) {
    echo "<p>📝 Inserting all teacher assignments...</p>";
    
    $inserted = 0;
    $duplicates = 0;
    $errors = 0;
    $errorMessages = [];
    
    $stmt = $pdo->prepare("
        INSERT INTO teacher_assignments (teacher_name, section, program, school_year, semester, is_active)
        VALUES (?, ?, ?, '2025-2026', '1st', true)
        ON CONFLICT (teacher_name, section, school_year, semester) 
        DO UPDATE SET is_active = true, updated_at = CURRENT_TIMESTAMP
    ");
    
    // COLLEGE TEACHERS
    echo "<p>&nbsp;&nbsp;📚 <strong>Adding College Teachers...</strong></p>";
    
    $collegeTeachers = [
        // BSCS Programs
        'BSCS-1M1' => ['MR. VELE', 'MR. V. GORDON', 'MR. JIMENEZ', 'MS. RENDORA', 'MR. ATIENZA'],
        'BSCS-2N1' => ['MR. RODRIGUEZ', 'MR. JIMENEZ', 'MS. RENDORA', 'MR. GORDON'],
        'BSCS-3M1' => ['MR. PATALEN', 'MS. DIMAPILIS', 'MR. V. GORDON', 'MR. RODRIGUEZ'],
        'BSCS-4N1' => ['MR. CALCEÑA', 'MR. V. GORDON', 'MR. MATILA'],
        'BSCS-1-SUNDAYCLASS' => ['MR. LACERNA', 'MR. PATIAM', 'MR. MATILA', 'MR. ESPEÑA', 'MR. VELE', 'MS. OCTAVO'],
        'BSCS-2-SUNDAYCLASS' => ['MR. ICABANDE', 'MR. PATIAM', 'MS. DIMAPILIS', 'MR. ESPEÑA', 'MR. RODRIGUEZ'],
        
        // BSOA Programs  
        'BSOA-1M1' => ['MR. PATIAM', 'MR. LACERNA', 'MS. CARMONA', 'MS. IGHARAS', 'MS. RENDORA', 'MR. ATIENZA'],
        'BSOA-2N1' => ['MR. ICABANDE', 'MR. JIMENEZ', 'MRS. VELE', 'MR. CALCEÑA', 'MS. CARMONA', 'MS. RENDORA'],
        'BSOA-3M1' => ['MS. CARMONA', 'MS. IGHARAS', 'MR. CALCEÑA'],
        'BSOA-4N1' => ['MR. CALCEÑA'],
        'BSOA-1-SUNDAYCLASS' => ['MR. PATIAM', 'MR. MATILA', 'MR. RODRIGUEZ', 'MS. IGHARAS', 'MS. OCTAVO', 'MR. ICABANDE'],
        'BSOA-2-SUNDAYCLASS' => ['MR. PATIAM', 'MR. ICABANDE', 'MS. IGHARAS', 'MS. DIMAPILIS', 'MS. OCTAVO', 'MR. VELE', 'MR. LACERNA'],
        
        // BTVTED Programs
        'BTVTED-1M1' => ['MR. VELE', 'MR. MATILA', 'MR. ORNACHO', 'MR. PATIAM', 'MR. VALENZUELA', 'MS. OCTAVO', 'MS. RENDORA', 'MR. ATIENZA', 'MS. TESORO'],
        'BTVTED-2N1' => ['MRS. VELE', 'MR. VELE', 'MR. ICABANDE', 'MS. TESORO', 'MR. MATILA', 'MS. OCTAVO', 'MS. RENDORA', 'MS. MAGNO'],
        'BTVTED-3M1' => ['MS. OCTAVO', 'MR. VALENZUELA', 'MS. MAGNO', 'MS. TESORO'],
        'BTVTED-4M1' => ['MS. TESORO'],
        'BTVTED-1-SUNDAYCLASS' => ['MR. PATIAM', 'MR. VELE', 'MR. RODRIGUEZ', 'MRS. VELE', 'MS. GENTEROY', 'MS. OCTAVO', 'MR. MATILA', 'MR. VALENZUELA'],
        'BTVTED-2-SUNDAYCLASS' => ['MR. ORNACHO', 'MR. PATIAM', 'MS. OCTAVO', 'MR. VALENZUELA', 'MS. GENTEROY', 'MR. VELE', 'MS. VELE', 'MS. DIMAPILIS'],
    ];
    
    $collegeCount = 0;
    foreach ($collegeTeachers as $section => $teachers) {
        $sectionCount = 0;
        foreach ($teachers as $teacher) {
            $teacher = cleanTeacherName($teacher);
            if (!empty($teacher)) {
                try {
                    $stmt->execute([$teacher, $section, 'COLLEGE']);
                    $sectionCount++;
                    $inserted++;
                } catch (Exception $e) {
                    $errors++;
                    $errorMessages[] = "Error inserting {$teacher} in {$section}: " . $e->getMessage();
                }
            }
        }
        $collegeCount += $sectionCount;
        echo "<p>&nbsp;&nbsp;&nbsp;&nbsp;✓ Added {$sectionCount} teachers to <strong>{$section}</strong></p>";
    }
    echo "<p>&nbsp;&nbsp;&nbsp;&nbsp;<strong>Total College assignments: {$collegeCount}</strong></p>";
    
    // SHS GRADE 11 TEACHERS
    echo "<p>&nbsp;&nbsp;🎓 <strong>Adding SHS Grade 11 Teachers...</strong></p>";
    
    $shs11Teachers = [
        // HE Sections
        'HE2M1' => ['MR. ALCEDO', 'MRS. YABUT', 'MS. GAJIRAN', 'MS. ANGELES', 'MR. UMALI', 'MS. TINGSON', 'MR. PATIAM', 'MS. LAGUADOR', 'MR. R. GORDON', 'MR. GARCIA'],
        'HE2M2' => ['MR. ALCEDO', 'MRS. YABUT', 'MS. GAJIRAN', 'MS. ANGELES', 'MR. UMALI', 'MS. TINGSON', 'MR. PATIAM', 'MS. LAGUADOR', 'MR. R. GORDON', 'MR. GARCIA'],
        'HE2M3' => ['MR. ALCEDO', 'MRS. YABUT', 'MS. GAJIRAN', 'MS. ANGELES', 'MR. UMALI', 'MS. TINGSON', 'MR. PATIAM', 'MS. LAGUADOR', 'MR. R. GORDON', 'MR. GARCIA'],
        'HE2N1' => ['MR. ALCEDO', 'MRS. YABUT', 'MS. GAJIRAN', 'MS. ANGELES', 'MR. UMALI', 'MS. TINGSON', 'MR. PATIAM', 'MS. LAGUADOR', 'MR. R. GORDON', 'MR. GARCIA'],
        'HE2N2' => ['MR. ALCEDO', 'MRS. YABUT', 'MS. GAJIRAN', 'MS. ANGELES', 'MR. UMALI', 'MS. TINGSON', 'MR. PATIAM', 'MS. LAGUADOR', 'MR. R. GORDON', 'MR. GARCIA'],
        'HE2N3' => ['MR. ALCEDO', 'MRS. YABUT', 'MS. GAJIRAN', 'MS. ANGELES', 'MR. UMALI', 'MS. TINGSON', 'MR. PATIAM', 'MS. LAGUADOR', 'MR. R. GORDON', 'MR. GARCIA'],
        
        // ICT Sections
        'ICT2M1' => ['MR. ALCEDO', 'MS. LAGUADOR', 'MR. PATIAM', 'MR. ORNACHO', 'MR. UMALI', 'MS. TINGSON', 'MS. ROQUIOS', 'MR. JIMENEZ'],
        'ICT2M2' => ['MR. ALCEDO', 'MS. LAGUADOR', 'MR. PATIAM', 'MR. ORNACHO', 'MR. UMALI', 'MS. TINGSON', 'MS. ROQUIOS', 'MR. JIMENEZ'],
        'ICT2N1' => ['MR. ALCEDO', 'MS. LAGUADOR', 'MR. PATIAM', 'MR. ORNACHO', 'MR. UMALI', 'MS. TINGSON', 'MS. ROQUIOS', 'MR. JIMENEZ'],
        'ICT2N2' => ['MR. ALCEDO', 'MS. LAGUADOR', 'MR. PATIAM', 'MR. ORNACHO', 'MR. UMALI', 'MS. TINGSON', 'MS. ROQUIOS', 'MR. JIMENEZ'],
        
        // HUMSS Sections
        'HUMSS2M1' => ['MS. ROQUIOS', 'MRS. YABUT', 'MS. GAJIRAN', 'MS. ANGELES', 'MR. UMALI', 'MS. TESORO', 'MR. PATIAM', 'MR. BATILES', 'MR. LACERNA'],
        'HUMSS2M2' => ['MS. ROQUIOS', 'MRS. YABUT', 'MS. GAJIRAN', 'MS. ANGELES', 'MR. UMALI', 'MS. TESORO', 'MR. PATIAM', 'MR. BATILES', 'MR. LACERNA'],
        'HUMSS2M3' => ['MS. ROQUIOS', 'MRS. YABUT', 'MS. GAJIRAN', 'MS. ANGELES', 'MR. UMALI', 'MS. TESORO', 'MR. PATIAM', 'MR. BATILES', 'MR. LACERNA'],
        'HUMSS2M4' => ['MS. ROQUIOS', 'MRS. YABUT', 'MS. GAJIRAN', 'MS. ANGELES', 'MR. UMALI', 'MS. TESORO', 'MR. PATIAM', 'MR. BATILES', 'MR. LACERNA'],
        'HUMSS2N1' => ['MS. ROQUIOS', 'MRS. YABUT', 'MS. GAJIRAN', 'MS. ANGELES', 'MR. UMALI', 'MS. TESORO', 'MR. PATIAM', 'MR. BATILES', 'MR. LACERNA'], 
        'HUMSS2N2' => ['MS. ROQUIOS', 'MRS. YABUT', 'MS. GAJIRAN', 'MS. ANGELES', 'MR. UMALI', 'MS. TESORO', 'MR. PATIAM', 'MR. BATILES', 'MR. LACERNA'], 
        'HUMSS2N3' => ['MS. ROQUIOS', 'MRS. YABUT', 'MS. GAJIRAN', 'MS. ANGELES', 'MR. UMALI', 'MS. TESORO', 'MR. PATIAM', 'MR. BATILES', 'MR. LACERNA'], 
        
        // ABM Sections
        'ABM2M1' => ['MR. ALCEDO', 'MS. LAGUADOR', 'MR. RODRIGUEZ', 'MS. ANGELES', 'MR. UMALI', 'MS. ROQUIOS', 'MR. CALCEÑA'],
        'ABM2M2' => ['MR. ALCEDO', 'MS. LAGUADOR', 'MR. RODRIGUEZ', 'MS. ANGELES', 'MR. UMALI', 'MS. ROQUIOS', 'MR. CALCEÑA'],
        'ABM2N1' => ['MR. ALCEDO', 'MS. LAGUADOR', 'MR. RODRIGUEZ', 'MR. ORNACHO', 'MR. UMALI', 'MS. ROQUIOS', 'MR. CALCEÑA'],
    ];
    
    $shs11Count = 0;
    foreach ($shs11Teachers as $section => $teachers) {
        $sectionCount = 0;
        foreach ($teachers as $teacher) {
            $teacher = cleanTeacherName($teacher);
            if (!empty($teacher)) {
                try {
                    $stmt->execute([$teacher, $section, 'SHS']);
                    $sectionCount++;
                    $inserted++;
                } catch (Exception $e) {
                    $errors++;
                }
            }
        }
        $shs11Count += $sectionCount;
        echo "<p>&nbsp;&nbsp;&nbsp;&nbsp;✓ Added {$sectionCount} teachers to <strong>{$section}</strong></p>";
    }
    echo "<p>&nbsp;&nbsp;&nbsp;&nbsp;<strong>Total Grade 11 SHS assignments: {$shs11Count}</strong></p>";
    
    // SHS GRADE 12 TEACHERS
    echo "<p>&nbsp;&nbsp;🎓 <strong>Adding SHS Grade 12 Teachers...</strong></p>";
    
    $shs12Teachers = [
        // HE4 Sections (Grade 12)
        'HE4M1' => ['MS. LIBRES', 'MR. ICABANDE', 'MS. RENDORA', 'MS. MAGNO', 'MR. R. GORDON'],
        'HE4M2' => ['MS. LIBRES', 'MR. ICABANDE', 'MS. RENDORA', 'MS. MAGNO', 'MR. R. GORDON'],
        'HE4M3' => ['MS. LIBRES', 'MR. ICABANDE', 'MS. RENDORA', 'MS. MAGNO', 'MR. R. GORDON'],
        'HE4M4' => ['MS. LIBRES', 'MR. ICABANDE', 'MS. RENDORA', 'MS. MAGNO', 'MR. R. GORDON'],
        'HE4N1' => ['MS. LIBRES', 'MR. ICABANDE', 'MS. RENDORA', 'MS. MAGNO', 'MR. R. GORDON'],
        'HE4N2' => ['MS. LIBRES', 'MR. ICABANDE', 'MS. RENDORA', 'MS. MAGNO', 'MR. R. GORDON'],
        'HE4N3' => ['MS. LIBRES', 'MR. ICABANDE', 'MS. RENDORA', 'MS. MAGNO', 'MR. R. GORDON'],
        
        // ICT4 Sections (Grade 12)
        'ICT4M1' => ['MS. TINGSON', 'MR. ICABANDE', 'MS. RENDORA', 'MS. CARMONA', 'MR. V. GORDON', 'MS. RIVERA'],
        'ICT4M2' => ['MS. TINGSON', 'MR. ICABANDE', 'MS. RENDORA', 'MS. CARMONA', 'MR. V. GORDON', 'MS. RIVERA'],
        'ICT4N1' => ['MS. TINGSON', 'MR. ICABANDE', 'MS. RENDORA', 'MS. CARMONA', 'MR. V. GORDON', 'MS. RIVERA'],
        'ICT4N2' => ['MS. TINGSON', 'MR. ICABANDE', 'MS. RENDORA', 'MS. CARMONA', 'MR. V. GORDON', 'MS. RIVERA'],
    
        // HUMSS4 Sections (Grade 12)
        'HUMSS4M1' => ['MS. LIBRES', 'MR. ICABANDE', 'MS. RENDORA', 'MS. CARMONA', 'MR. LACERNA', 'MR. GARCIA', 'MR. BATILES'],
        'HUMSS4M2' => ['MS. LIBRES', 'MR. ICABANDE', 'MS. RENDORA', 'MS. CARMONA', 'MR. LACERNA', 'MR. GARCIA', 'MR. BATILES'],
        'HUMSS4M3' => ['MS. LIBRES', 'MR. ICABANDE', 'MS. RENDORA', 'MS. CARMONA', 'MR. LACERNA', 'MR. GARCIA', 'MR. BATILES'],
        'HUMSS4N1' => ['MS. LIBRES', 'MR. ICABANDE', 'MS. RENDORA', 'MS. CARMONA', 'MR. LACERNA', 'MR. GARCIA', 'MR. BATILES'],
        'HUMSS4N2' => ['MS. LIBRES', 'MR. ICABANDE', 'MS. RENDORA', 'MS. CARMONA', 'MR. LACERNA', 'MR. GARCIA', 'MR. BATILES'],
        'HUMSS4N3' => ['MS. LIBRES', 'MR. ICABANDE', 'MS. RENDORA', 'MS. CARMONA', 'MR. LACERNA', 'MR. GARCIA', 'MR. BATILES'],
        'HUMSS4N4' => ['MS. LIBRES', 'MR. ICABANDE', 'MS. RENDORA', 'MS. CARMONA', 'MR. LACERNA', 'MR. GARCIA', 'MR. BATILES'],
        
        // ABM4 Sections (Grade 12)
        'ABM4M1' => ['MS. LIBRES', 'MR. ALCEDO', 'MS. RENDORA', 'MS. CARMONA', 'MR. CALCEÑA'],
        'ABM4M2' => ['MS. LIBRES', 'MR. ALCEDO', 'MS. RENDORA', 'MS. CARMONA', 'MR. CALCEÑA'],
        'ABM4N1' => ['MS. LIBRES', 'MR. ALCEDO', 'MS. RENDORA', 'MS. CARMONA', 'MR. CALCEÑA'],
    ];
    
    $shs12Count = 0;
    foreach ($shs12Teachers as $section => $teachers) {
        $sectionCount = 0;
        foreach ($teachers as $teacher) {
            $teacher = cleanTeacherName($teacher);
            if (!empty($teacher)) {
                try {
                    $stmt->execute([$teacher, $section, 'SHS']);
                    $sectionCount++;
                    $inserted++;
                } catch (Exception $e) {
                    $errors++;
                }
            }
        }
        $shs12Count += $sectionCount;
        echo "<p>&nbsp;&nbsp;&nbsp;&nbsp;✓ Added {$sectionCount} teachers to <strong>{$section}</strong></p>";
    }
    echo "<p>&nbsp;&nbsp;&nbsp;&nbsp;<strong>Total Grade 12 SHS assignments: {$shs12Count}</strong></p>";
    
    // SHS SC (Special Classes) TEACHERS
    echo "<p>&nbsp;&nbsp;🌟 <strong>Adding SHS Special Classes Teachers...</strong></p>";
    
    $shsSCTeachers = [
        // Grade 11 SC
        'HE-11-SUNDAY CLASS' => ['MR. LACERNA', 'MR. ORNACHO', 'MR. ICABANDE', 'MS. GENTEROY'],
        'ICT-11-SUNDAY CLASS' => ['MR. LACERNA', 'MR. ORNACHO', 'MR. VALENZUELA', 'MR. ICABANDE', 'MR. ESPEÑA'],
        'HUMSS-11-SUNDAY CLASS' => ['MR. VALENZUELA', 'MR. ORNACHO', 'MRS. VELE', 'MR. VELE', 'MR. ICABANDE', 'MR. MATILA'],
        'ABM-11-SUNDAY CLASS' => ['MR. VALENZUELA', 'MR. ORNACHO', 'MRS. VELE', 'MR. ICABANDE', 'MR. MATILA', 'MR. RODRIGUEZ'],
        
        // Grade 12 SC
        'HE-12-SUNDAY CLASS' => ['MS. GENTEROY', 'MS. OCTAVO', 'MR. ICABANDE', 'MS. GENTEROY'],
        'ICT-12-SUNDAY CLASS' => ['MS. OCTAVO', 'MR. ICABANDE', 'MR. ESPEÑA'],
        'HUMSS-12-SUNDAY CLASS' => ['MS. OCTAVO', 'MR. PATIAM', 'MRS. VELE', 'MR. ICABANDE', 'MR. LACERNA'],
        'ABM-12-SUNDAY CLASS' => ['MS. OCTAVO', 'MR. PATIAM', 'MR. ORNACHO', 'MR. ICABANDE', 'MR. LACERNA', 'MRS. VELE'],
    ];
    
    $shsSCCount = 0;
    foreach ($shsSCTeachers as $section => $teachers) {
        $sectionCount = 0;
        foreach ($teachers as $teacher) {
            $teacher = cleanTeacherName($teacher);
            if (!empty($teacher)) {
                try {
                    $stmt->execute([$teacher, $section, 'SHS']);
                    $sectionCount++;
                    $inserted++;
                } catch (Exception $e) {
                    $errors++;
                }
            }
        }
        $shsSCCount += $sectionCount;
        echo "<p>&nbsp;&nbsp;&nbsp;&nbsp;✓ Added {$sectionCount} teachers to <strong>{$section}</strong></p>";
    }
    echo "<p>&nbsp;&nbsp;&nbsp;&nbsp;<strong>Total Special Classes SHS assignments: {$shsSCCount}</strong></p>";
    
    $totalAssigned = $collegeCount + $shs11Count + $shs12Count + $shsSCCount;
    echo "<p>&nbsp;&nbsp;<strong>Total teacher assignments inserted: {$totalAssigned}</strong></p>";
    
    return $totalAssigned;
}

try {
    $pdo = getPDO();
    echo "<!DOCTYPE html>";
    echo "<html><head><title>Database Setup - Complete</title>";
    echo "<style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #800000; border-bottom: 2px solid #800000; padding-bottom: 10px; }
        h3 { color: #A52A2A; margin-top: 20px; }
        p { margin: 5px 0; }
        .summary { background: #f0f0f0; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 5px solid #800000; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; }
        .highlight { background: #ffffcc; padding: 15px; border-radius: 5px; border-left: 5px solid #ffc107; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-card { background: white; padding: 15px; border-radius: 8px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-top: 3px solid #800000; }
        .stat-number { font-size: 2.5em; font-weight: bold; color: #800000; }
        .stat-label { color: #666; }
        .btn { display: inline-block; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin-right: 10px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 4px; border: 1px solid #dee2e6; }
        ul { margin: 5px 0 15px 20px; }
    </style>";
    echo "</head><body>";
    echo "<div class='container'>";
    
    echo "<h2>🔧 Setting Up Database System with All Real Teacher Assignments</h2>";

    // ==============================
    // Drop ALL old tables (clean slate)
    // ==============================
    echo "<p>🗑️ Cleaning up old tables...</p>";
    $pdo->exec("
        DROP TABLE IF EXISTS evaluations CASCADE;
        DROP TABLE IF EXISTS teacher_assignments CASCADE;
        DROP TABLE IF EXISTS login_attempts CASCADE;
        DROP TABLE IF EXISTS activity_logs CASCADE;
    ");
    echo "<p class='success'>✓ Old tables removed</p>";

    // ==============================
    // Teacher Assignments Table
    // ==============================
    echo "<p>📋 Creating teacher_assignments table...</p>";
    $pdo->exec("
        CREATE TABLE teacher_assignments (
            id SERIAL PRIMARY KEY,
            teacher_name VARCHAR(100) NOT NULL,
            section VARCHAR(50) NOT NULL,
            program VARCHAR(10) NOT NULL CHECK (program IN ('SHS', 'COLLEGE')),
            school_year VARCHAR(20) DEFAULT '2025-2026',
            semester VARCHAR(10) DEFAULT '1st' CHECK (semester IN ('1st', '2nd')),
            is_active BOOLEAN DEFAULT true,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (teacher_name, section, school_year, semester)
        );
        
        CREATE INDEX idx_teacher_name ON teacher_assignments(teacher_name);
        CREATE INDEX idx_section ON teacher_assignments(section);
        CREATE INDEX idx_program ON teacher_assignments(program);
        CREATE INDEX idx_active ON teacher_assignments(is_active);
    ");
    echo "<p class='success'>✓ Teacher assignments table created</p>";

    // ==============================
    // Evaluations Table
    // ==============================
    echo "<p>📊 Creating evaluations table (with separate comment fields)...</p>";
    $pdo->exec("
        CREATE TABLE evaluations (
            id SERIAL PRIMARY KEY,
            student_username VARCHAR(50) NOT NULL,
            student_name VARCHAR(100) NOT NULL,
            teacher_name VARCHAR(100) NOT NULL,
            section VARCHAR(50) NOT NULL,
            program VARCHAR(10) NOT NULL CHECK (program IN ('SHS', 'COLLEGE')),
            
            -- Section 1: Teaching Ability (6 questions)
            q1_1 SMALLINT CHECK (q1_1 BETWEEN 1 AND 5),
            q1_2 SMALLINT CHECK (q1_2 BETWEEN 1 AND 5),
            q1_3 SMALLINT CHECK (q1_3 BETWEEN 1 AND 5),
            q1_4 SMALLINT CHECK (q1_4 BETWEEN 1 AND 5),
            q1_5 SMALLINT CHECK (q1_5 BETWEEN 1 AND 5),
            q1_6 SMALLINT CHECK (q1_6 BETWEEN 1 AND 5),
            
            -- Section 2: Management Skills (4 questions)
            q2_1 SMALLINT CHECK (q2_1 BETWEEN 1 AND 5),
            q2_2 SMALLINT CHECK (q2_2 BETWEEN 1 AND 5),
            q2_3 SMALLINT CHECK (q2_3 BETWEEN 1 AND 5),
            q2_4 SMALLINT CHECK (q2_4 BETWEEN 1 AND 5),
            
            -- Section 3: Guidance Skills (4 questions)
            q3_1 SMALLINT CHECK (q3_1 BETWEEN 1 AND 5),
            q3_2 SMALLINT CHECK (q3_2 BETWEEN 1 AND 5),
            q3_3 SMALLINT CHECK (q3_3 BETWEEN 1 AND 5),
            q3_4 SMALLINT CHECK (q3_4 BETWEEN 1 AND 5),
            
            -- Section 4: Personal and Social Characteristics (6 questions)
            q4_1 SMALLINT CHECK (q4_1 BETWEEN 1 AND 5),
            q4_2 SMALLINT CHECK (q4_2 BETWEEN 1 AND 5),
            q4_3 SMALLINT CHECK (q4_3 BETWEEN 1 AND 5),
            q4_4 SMALLINT CHECK (q4_4 BETWEEN 1 AND 5),
            q4_5 SMALLINT CHECK (q4_5 BETWEEN 1 AND 5),
            q4_6 SMALLINT CHECK (q4_6 BETWEEN 1 AND 5),
            
            -- SEPARATED COMMENTS FIELDS
            positive_comments TEXT,
            negative_comments TEXT,
            
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            -- Prevent duplicate evaluations
            UNIQUE (student_username, teacher_name, section)
        );
        
        CREATE INDEX idx_student_username ON evaluations(student_username);
        CREATE INDEX idx_teacher_eval ON evaluations(teacher_name);
        CREATE INDEX idx_section_eval ON evaluations(section);
        CREATE INDEX idx_program_eval ON evaluations(program);
        CREATE INDEX idx_positive_comments ON evaluations USING gin(to_tsvector('english', positive_comments));
        CREATE INDEX idx_negative_comments ON evaluations USING gin(to_tsvector('english', negative_comments));
    ");
    echo "<p class='success'>✓ Evaluations table created with separate comment fields</p>";

    // ==============================
    // Activity Logs Table
    // ==============================
    echo "<p>📝 Creating activity_logs table...</p>";
    $pdo->exec("
        CREATE TABLE activity_logs (
            id SERIAL PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            user_type VARCHAR(20) NOT NULL CHECK (user_type IN ('student', 'teacher', 'admin')),
            action VARCHAR(100) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE INDEX idx_logs_username ON activity_logs(username);
        CREATE INDEX idx_logs_user_type ON activity_logs(user_type);
        CREATE INDEX idx_logs_created ON activity_logs(created_at);
    ");
    echo "<p class='success'>✓ Activity logs table created</p>";

    // ==============================
    // Login Attempts Table
    // ==============================
    echo "<p>🔒 Creating login_attempts table...</p>";
    $pdo->exec("
        CREATE TABLE login_attempts (
            id SERIAL PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            success BOOLEAN DEFAULT false
        );
        
        CREATE INDEX idx_login_attempts_username ON login_attempts(username);
        CREATE INDEX idx_login_attempts_ip ON login_attempts(ip_address);
        CREATE INDEX idx_login_attempts_time ON login_attempts(attempt_time);
    ");
    echo "<p class='success'>✓ Login attempts table created</p>";

    // ==============================
    // Insert ALL Real Teacher Assignments
    // ==============================
    echo "<hr>";
    $totalInserted = insertAllTeacherAssignments($pdo);

    // ==============================
    // Summary and Statistics
    // ==============================
    echo "<div class='summary'>";
    echo "<h3>✅ Database Setup Complete!</h3>";
    echo "<p class='success'><strong>Total teacher assignments inserted: {$totalInserted}</strong></p>";
    echo "</div>";
    
    // Get detailed statistics
    $stats = $pdo->query("SELECT program, COUNT(*) as count FROM teacher_assignments GROUP BY program ORDER BY program")->fetchAll();
    $uniqueTeachers = $pdo->query("SELECT COUNT(DISTINCT teacher_name) FROM teacher_assignments")->fetchColumn();
    $uniqueSections = $pdo->query("SELECT COUNT(DISTINCT section) FROM teacher_assignments")->fetchColumn();
    $collegeCount = $pdo->query("SELECT COUNT(*) FROM teacher_assignments WHERE program = 'COLLEGE'")->fetchColumn();
    $shsCount = $pdo->query("SELECT COUNT(*) FROM teacher_assignments WHERE program = 'SHS'")->fetchColumn();
    
    echo "<div class='stats-grid'>";
    echo "<div class='stat-card'><div class='stat-number'>{$totalInserted}</div><div class='stat-label'>Total Assignments</div></div>";
    echo "<div class='stat-card'><div class='stat-number'>{$uniqueTeachers}</div><div class='stat-label'>Unique Teachers</div></div>";
    echo "<div class='stat-card'><div class='stat-number'>{$uniqueSections}</div><div class='stat-label'>Sections Covered</div></div>";
    echo "<div class='stat-card'><div class='stat-number'>{$collegeCount}</div><div class='stat-label'>College Assignments</div></div>";
    echo "<div class='stat-card'><div class='stat-number'>{$shsCount}</div><div class='stat-label'>SHS Assignments</div></div>";
    echo "</div>";
    
    echo "<div class='highlight'>";
    echo "<h3>🏫 System Information</h3>";
    echo "<p><strong>Tables Created:</strong></p>";
    echo "<ul>";
    echo "<li>📋 <code>teacher_assignments</code> - All real teacher-section assignments</li>";
    echo "<li>📊 <code>evaluations</code> - Student evaluation responses <strong>(separate positive/negative comment fields)</strong></li>";
    echo "<li>📝 <code>activity_logs</code> - User activity tracking</li>";
    echo "<li>🔒 <code>login_attempts</code> - Login attempt monitoring</li>";
    echo "</ul>";
    
    echo "<p><strong>Data Sources:</strong></p>";
    echo "<ul>";
    echo "<li>📑 <strong>Students:</strong> Google Sheets (Student_ID, Last_Name, First_Name, Section, Program, Username, Password)</li>";
    echo "<li>👨‍🏫 <strong>Teachers:</strong> Database teacher_assignments table (populated with all real data)</li>";
    echo "<li>📊 <strong>Evaluations:</strong> Database evaluations table</li>";
    echo "</ul>";
    
    echo "<p><strong>Key Features:</strong></p>";
    echo "<ul>";
    echo "<li>✅ <strong>Separate Comment Fields:</strong> <code>positive_comments</code> and <code>negative_comments</code> for easier querying</li>";
    echo "<li>✅ <strong>Full-Text Search:</strong> Indexed comments for fast searching</li>";
    echo "<li>✅ <strong>Activity Tracking:</strong> Monitor user actions</li>";
    echo "<li>✅ <strong>Login Security:</strong> Track login attempts</li>";
    echo "</ul>";
    
    echo "<p><strong>Ready to use!</strong> Your BSCS3M1 section should now show teachers.</p>";
    echo "</div>";
    
    echo "<div style='margin: 30px 0;'>";
    echo "<a href='index.php' class='btn btn-primary'>🏠 Go to Login Page</a>";
    echo "<a href='student_dashboard.php' class='btn btn-success'>📱 Test Student Dashboard</a>";
    echo "<a href='admin.php' class='btn btn-secondary'>👑 Admin Dashboard</a>";
    echo "</div>";
    
    echo "</div>"; // Close container
    echo "</body></html>";

} catch (PDOException $e) {
    echo "<div class='container'>";
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; border-left: 4px solid #dc3545;'>";
    echo "<h3>❌ Database Setup Failed</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database connection and try again.</p>";
    echo "</div>";
    echo "</div>";
    exit(1);
}
?>
