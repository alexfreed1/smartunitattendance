<?php
require '../config.php';
header('Content-Type: application/json');

// Check if institution is selected
if (empty($_SESSION['institution_db'])) {
    echo json_encode(['success'=>false, 'message'=>'No institution selected']);
    exit;
}

if(empty($_SESSION['trainer'])){
    echo json_encode(['success'=>false, 'message'=>'Unauthorized']);
    exit;
}

$trainer_id = $_SESSION['trainer']['id'];

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
    $unit_id = isset($_POST['unit_id']) ? (int)$_POST['unit_id'] : 0;
    $week = isset($_POST['week']) ? (int)$_POST['week'] : 0;
    $lesson = isset($_POST['lesson']) ? $conn->real_escape_string($_POST['lesson']) : '';
    $statuses = isset($_POST['status']) ? $_POST['status'] : [];

    if(!$class_id || !$unit_id || !$week || !$lesson || empty($statuses)){
        echo json_encode(['success'=>false, 'message'=>'Missing required fields or no students marked.']);
        exit;
    }

    // Get Unit Code
    $uRes = $conn->query("SELECT code FROM units WHERE id=$unit_id");
    if(!$uRes || $uRes->num_rows == 0){
        echo json_encode(['success'=>false, 'message'=>'Invalid Unit']);
        exit;
    }
    $unit_code = $uRes->fetch_assoc()['code'];

    $conn->begin_transaction();
    try {
        // Prepare statements
        $stmtCheck = $conn->prepare("SELECT id FROM attendance WHERE student_id=? AND unit_id=? AND week=? AND lesson=?");
        $stmtInsert = $conn->prepare("INSERT INTO attendance (student_id, unit_id, trainer_id, week, lesson, status, attendance_date) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmtUpdate = $conn->prepare("UPDATE attendance SET status=?, attendance_date=NOW() WHERE id=?");

        foreach($statuses as $student_id => $statusVal){
            $student_id = (int)$student_id;
            // Convert to ENUM values
            $status = ($statusVal == 'present') ? 'present' : 'absent';

            // Check if record exists
            $stmtCheck->bind_param("iiis", $student_id, $unit_id, $week, $lesson);
            $stmtCheck->execute();
            $res = $stmtCheck->get_result();

            if($res->num_rows > 0){
                // Update existing record
                $row = $res->fetch_assoc();
                $att_id = $row['id'];
                $stmtUpdate->bind_param("si", $status, $att_id);
                $stmtUpdate->execute();
            } else {
                // Insert new record (student_id:int, unit_id:int, trainer_id:int, week:int, lesson:string, status:string)
                $stmtInsert->bind_param("iiiiss", $student_id, $unit_id, $trainer_id, $week, $lesson, $status);
                $stmtInsert->execute();
            }
        }

        $conn->commit();
        echo json_encode(['success'=>true, 'message'=>'Attendance submitted successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success'=>false, 'message'=>'Database error: '.$e->getMessage()]);
    }
} else {
    echo json_encode(['success'=>false, 'message'=>'Invalid request method']);
}
?>