<?php
include 'config.php';

// Get all survey data
$surveys = $pdo->query("SELECT * FROM surveys")->fetchAll(PDO::FETCH_ASSOC);
// Calculate statistics
$totalSurveys = count($surveys);
$averageAge = 0;
$minAge = 120;
$maxAge = 0;
$pizzaCount = 0;
$pastaCount = 0;
$papWorsCount = 0;
$moviesAvg = 0;
$radioAvg = 0;
$eatOutAvg = 0;
$tvAvg = 0;

$today = new DateTime();

foreach ($surveys as $survey) {
    $dob = new DateTime($survey['dob']);
    $age = $today->diff($dob)->y;
    
    $averageAge += $age;
    $minAge = min($minAge, $age);
    $maxAge = max($maxAge, $age);
    
    $pizzaCount += $survey['pizza'];
    $pastaCount += $survey['pasta'];
    $papWorsCount += $survey['pap_wors'];
    
    $moviesAvg += $survey['movies_rating'];
    $radioAvg += $survey['radio_rating'];
    $eatOutAvg += $survey['eat_out_rating'];
    $tvAvg += $survey['tv_rating'];
}

if ($totalSurveys > 0) {
    $averageAge = round($averageAge / $totalSurveys, 1);
    $pizzaPercent = round(($pizzaCount / $totalSurveys) * 100, 1);
    $pastaPercent = round(($pastaCount / $totalSurveys) * 100, 1);
    $papWorsPercent = round(($papWorsCount / $totalSurveys) * 100, 1);
    $moviesAvg = round($moviesAvg / $totalSurveys, 1);
    $radioAvg = round($radioAvg / $totalSurveys, 1);
    $eatOutAvg = round($eatOutAvg / $totalSurveys, 1);
    $tvAvg = round($tvAvg / $totalSurveys, 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey Results</title>
    <link rel="stylesheet" href="style.css">
    <?php include 'analytics.php'; ?>
</head>
<body>
    <div class="container">
        <h1>Survey Results</h1>
        
        <?php if ($totalSurveys == 0): ?>
            <p class="no-data">No Surveys Available</p>
        <?php else: ?>
            <table class="results-table">
                <tr>
                    <th>Survey Results</th>
                    <th>Value</th>
                </tr>
                <tr>
                    <td>Total number of surveys:</td>
                    <td><?= $totalSurveys ?></td>
                </tr>
                <tr>
                    <td>Average Age:</td>
                    <td><?= $averageAge ?></td>
                </tr>
                <tr>
                    <td>Oldest person who participated in survey:</td>
                    <td><?= $maxAge ?></td>
                </tr>
                <tr>
                    <td>Youngest person who participated in survey:</td>
                    <td><?= $minAge ?></td>
                </tr>
                <tr>
                    <td>Percentage of people who like Pizza:</td>
                    <td><?= $pizzaPercent ?>%</td>
                </tr>
                <tr>
                    <td>Percentage of people who like Pasta:</td>
                    <td><?= $pastaPercent ?>%</td>
                </tr>
                <tr>
                    <td>Percentage of people who like Pap and Wors:</td>
                    <td><?= $papWorsPercent ?>%</td>
                </tr>
                <tr>
                    <td>People who like to watch movies (average rating):</td>
                    <td><?= $moviesAvg ?></td>
                </tr>
                <tr>
                    <td>People who like to listen to radio (average rating):</td>
                    <td><?= $radioAvg ?></td>
                </tr>
                <tr>
                    <td>People who like to eat out (average rating):</td>
                    <td><?= $eatOutAvg ?></td>
                </tr>
                <tr>
                    <td>People who like to watch TV (average rating):</td>
                    <td><?= $tvAvg ?></td>
                </tr>
            </table>
        <?php endif; ?>
        
        <a href="survey.php" class="back-btn">Back to Survey</a>
    </div>
</body>
</html>
