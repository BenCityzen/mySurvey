<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate age
    $dob = new DateTime($_POST['dob']);
    $today = new DateTime();
    $age = $today->diff($dob)->y;
    
    if ($age < 5 || $age > 120) {
        die("Age must be between 5 and 120 years.");
    }
    
    // Process food preferences
    $foods = $_POST['food'] ?? [];
    $pizza = in_array('pizza', $foods) ? 1 : 0;
    $pasta = in_array('pasta', $foods) ? 1 : 0;
    $pap_wors = in_array('pap_wors', $foods) ? 1 : 0;
    $other_food = in_array('other', $foods) ? $_POST['other_food'] : null;
    
    // Insert into database
    try {
        $stmt = $conn->prepare("INSERT INTO surveys (
            full_name, email, dob, contact_number, 
            pizza, pasta, pap_wors, other_food,
            movies_rating, radio_rating, eat_out_rating, tv_rating
        ) VALUES (
            :full_name, :email, :dob, :contact_number,
            :pizza, :pasta, :pap_wors, :other_food,
            :movies_rating, :radio_rating, :eat_out_rating, :tv_rating
        )");
        
        $stmt->execute([
            ':full_name' => $_POST['full_name'],
            ':email' => $_POST['email'],
            ':dob' => $_POST['dob'],
            ':contact_number' => $_POST['contact_number'],
            ':pizza' => $pizza,
            ':pasta' => $pasta,
            ':pap_wors' => $pap_wors,
            ':other_food' => $other_food,
            ':movies_rating' => $_POST['movies_rating'],
            ':radio_rating' => $_POST['radio_rating'],
            ':eat_out_rating' => $_POST['eat_out_rating'],
            ':tv_rating' => $_POST['tv_rating']
        ]);
        
        header("Location: results.php");
        exit();
    } catch(PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>