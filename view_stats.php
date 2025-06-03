<?php
session_start();

include 'config.php'; // Make sure $pdo is your PDO connection

// Defaults
$filter = "1=1";
$params = []; // For prepared statements
$sort = "total_time DESC"; 
$limit = 10; 
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1; 
$offset = ($page - 1) * $limit;

// Handle POST filters securely
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['time_range'])) {
        $allowedIntervals = ['1 DAY', '1 MONTH', '1 YEAR'];
        if (in_array($_POST['time_range'], $allowedIntervals)) {
            $filter .= " AND created_at >= DATE_SUB(NOW(), INTERVAL " . $_POST['time_range'] . ")";
        }
    }

    if (!empty($_POST['browser'])) {
        $filter .= " AND browser = :browser";
        $params[':browser'] = $_POST['browser'];
    }

    if (!empty($_POST['device'])) {
        $filter .= " AND device = :device";
        $params[':device'] = $_POST['device'];
    }
}

// Handle GET sort with whitelist
if (!empty($_GET['sort'])) {
    $allowedSort = ["total_time DESC", "total_time ASC"];
    if (in_array($_GET['sort'], $allowedSort)) {
        $sort = $_GET['sort'];
    }
}

// === Fetch Graph Data ===
$graphSQL = "
    SELECT browser AS label, 
           ROUND(SUM(time_spent)/60, 2) AS total_time
    FROM stemulator_activity
    WHERE $filter
    GROUP BY browser
    ORDER BY $sort
    LIMIT 10
";
$graphStmt = $pdo->prepare($graphSQL);
$graphStmt->execute($params);
$graphData = $graphStmt->fetchAll(PDO::FETCH_ASSOC);

$labels = json_encode(array_column($graphData, 'label'));
$users = json_encode(array_column($graphData, 'total_time'));

// === Stats Function ===
function getStats($interval, $pdo, $params) {
    $allowedIntervals = ['1 DAY', '1 MONTH', '1 YEAR'];
    if (!in_array($interval, $allowedIntervals)) {
        throw new InvalidArgumentException("Invalid interval");
    }
    $sql = "SELECT 
                ROUND(COALESCE(SUM(time_spent), 0)/60, 2) AS total_time,
                COUNT(DISTINCT name) AS total_users
            FROM stemulator_activity 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL $interval)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$dailyStats = getStats('1 DAY', $pdo, $params);
$monthlyStats = getStats('1 MONTH', $pdo, $params);
$yearlyStats = getStats('1 YEAR', $pdo, $params);

// === Table Data ===
$tableSQL = "
    SELECT name,
    ROUND(SUM(time_spent)/60, 2) AS total_time, 
           action, browser, device
    FROM stemulator_activity
    WHERE $filter
    GROUP BY name, action, browser, device
    ORDER BY $sort
    LIMIT $limit OFFSET $offset
";
$tableStmt = $pdo->prepare($tableSQL);
$tableStmt->execute($params);
$tableData = $tableStmt->fetchAll(PDO::FETCH_ASSOC);

// === Total Pages ===
$totalQuery = "SELECT COUNT(*) AS total FROM (
    SELECT 1 FROM stemulator_activity WHERE $filter GROUP BY name, action, browser, device
) AS temp";
$totalStmt = $pdo->prepare($totalQuery);
$totalStmt->execute($params);
$totalRows = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
$totalPages = ceil($totalRows / $limit);

// === Activity per Weekday ===
$weekSQL = "
    SELECT 
        DAYNAME(created_at) AS weekday, 
        ROUND(SUM(time_spent)/60, 2) AS total_time 
    FROM stemulator_activity 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
    GROUP BY weekday 
    ORDER BY FIELD(weekday, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')
";
$weekStmt = $pdo->query($weekSQL);
$weekdaysData = $weekStmt ? $weekStmt->fetchAll(PDO::FETCH_ASSOC) : [];

$weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$activityPerDay = array_fill_keys($weekdays, 0);
foreach ($weekdaysData as $row) {
    $activityPerDay[$row['weekday']] = $row['total_time'];
}
$weekLabels = json_encode(array_keys($activityPerDay));
$weekData = json_encode(array_values($activityPerDay));


// Now you can build your HTML + JS frontend using $tableData, $labels, $users, $dailyStats, etc.
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Activity Stats</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="tracking.css">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;500&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
     <style>
          body {
    font-family: 'Roboto', sans-serif;
    background: #1c2841 ;
    color: #f0f0f0;
    margin: 0;
    padding: 0;
    line-height: 1.6;
  }

  /* Gradient applied to header */
  header {
    background: linear-gradient(135deg, #001f3f, #000);
    padding: 20px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 20px;
    color: #fff;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
  }

  /* Keep header-content styles */
  .header-content {
    display: flex;
    align-items: center;
  }



  h1 {
    margin: 0;
  }

  /* Buttons with gradient effect */
  .nav-buttons {
    display: flex;
    gap: 20px;
  }

  .nav-button {
    padding: 12px 18px;
    background: linear-gradient(135deg, #001f3f, #0074d9);
    color: #fff;
    border: none;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.4s ease-in-out;
  }
h1
{
    
    color:#fff;
}
h2
{
    color:#fff;
}
  .nav-button:hover {
    background: linear-gradient(135deg, #0074d9, #001f3f);
    box-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
    animation: lightning 0.5s ease-out;
  }

  @keyframes lightning {
    0% { transform: scale(1); }
    50% { transform: scale(1.1) rotate(10deg); }
    100% { transform: scale(1) rotate(0deg); }
  }

  /* Main content */
  main {
    padding: 30px;
  }

  /* Section title colors */
  .section-title {
    text-align: center;
    margin-bottom: 25px;
    font-size: 28px;
    color: #00bfff;
    margin-bottom: 15px;
  }

  /* Gradient for stat-boxes and charts */
  .stat-box, .chart-container, table {
    background: linear-gradient(135deg, #001f3f, #000);
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
    margin-bottom: 20px;
    text-align: center;
  }

  .stat-box div {
    font-size: 16px;
    padding: 10px;
    background: #444;
    border-radius: 6px;
    margin-bottom: 10px;
  }

  /* Table with blue-black style */
  /* Footer remains simple */
  footer {
    text-align: center;
    padding: 15px;
    background: linear-gradient(135deg, #001f3f, #000);
    color: #fff;
  }

  /* Info text for descriptions */
  .info-text {
    font-size: 14px;
    color: #bbb;
    margin-top: 10px;
  }

  /* Lightning effect button */
  .lightning-button {
    position: relative;
    padding: 16px 30px;
    background: linear-gradient(135deg, #001f3f, #0074d9);
    color: white;
    border: none;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
    text-transform: uppercase;
    border-radius: 8px;
    transition: all 0.3s ease;
    outline: none;
  }

  .lightning-button::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 4px;
    height: 0;
    background-color: #fff;
    transform: translate(-50%, -50%);
    opacity: 0;
    transition: height 0.3s ease-in, opacity 0.2s ease;
  }

  .lightning-button:hover::before {
    height: 250%;
    opacity: 1;
  }

  .lightning-button:hover {
    background: #ff7f00;
    box-shadow: 0 0 25px rgba(255, 255, 255, 0.8);
    animation: lightning 0.7s ease-out;
  }
        /*table {
            width: 100%;
            border-collapse: collapse;
        }
        td {
            border: 1px solid #fff;
            padding: 8px;
            text-align: left;
        }*/
        th {
            background-color: #A6A5A8;
            color: white;
            padding: 8px;
            text-align: left;
        }
    
        chart2-wrapper{
            width: 40%;  /* Make the graph smaller */
            margin-left:50px;
            height: 300px;
            margin: auto;
            border:10px;
            border-radius:10px;
            border-color:black;
            background-color: transparent;
        }

        .charts-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
             background-color: transparent;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .chart-wrapper {
             background-color: transparent;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.05);
        }
        
        /* General Styling */
/*


    new

*/

/* Titles */
.title {
    text-align: center;
    margin-bottom: 25px;
    margin-top: 35px;
}

/* Stats Container */
.stats-container {
    display: flex;
    gap: 20px;
    justify-content: center;
    text-align: center;
    margin-bottom: 30px;
}

/* Stat Card */
.stat-card {
    background: #1e2a38;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(255, 255, 255, 0.1);
    width: 250px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 15px rgba(255, 255, 255, 0.2);
}

/* Icons */
.stat-card i {
    font-size: 30px;
    color: #4db5ff;
    margin-bottom: 10px;
}

/* Heading */
.stat-card h3 {
    font-size: 18px;
    margin-bottom: 5px;
}

/* Stat Number */
.stat-number {
    font-size: 28px;
    font-weight: bold;
    color: #66ff99;
}

/* Stat Detail */
.stat-detail {
    font-size: 14px;
    color: #a0aec0;
}

/* Filters Form */
.filter-form {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 15px;
    background: #1e2a38;
    padding: 15px;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(255, 255, 255, 0.1);
}

/* Filter Group */
.filter-group {
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* Select Box */
select {
    padding: 5px;
    border-radius: 5px;
    border: none;
    background: #2a3d55;
    color: white;
}

/* Buttons */
.filter-buttons {
    display: flex;
    gap: 10px;
}

.apply-btn, .reset-btn {
    padding: 8px 12px;
    border-radius: 5px;
    border: none;
    font-size: 14px;
    cursor: pointer;
}

.apply-btn {
    background: #1B355B;
    color: white;
}

.reset-btn {
    background: #055B59;
    color: white;
}

.apply-btn:hover {
    background: #39a0e5;
}

.reset-btn:hover {
    background: #e04b4b;
}

    button {
        
        background: #1e2a38;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        margin: 5px;
    }
    button:hover {
        background: #39a0e5;
    }
    .stat-box {
        background: #1e2a38;
        padding: 20px;
        border-radius: 10px;
        
        box-shadow: 0 4px 8px rgba(255, 255, 255, 0.1);
    }
    table {
        width: 95%;
        border-collapse: collapse;
        border-color:black;
        margin-left:50px;
    }
     td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #ccc;
         border-color:black;
    }
    tr:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }
    .pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin: 20px 0;
}
.pagination a, .pagination span {
    padding: 8px 16px;
}
    </style>
</head>
 <header>
    <div class="header-content">
      <h1>Survey Analytics Dashboard</h1>
    </div>
  </header>
<body>
    
<h1 class="title">Total Stats</h1>

<div class="stats-container">
    <div class="stat-card">
        <h3>Daily Users</h3>
        <p class="stat-number"><?= $dailyStats['total_users'] ?></p>
        <p class="stat-detail">Total Time: <?= $dailyStats['total_time'] ?> mins</p>
    </div>
    <div class="stat-card">
        <h3>Monthly Users</h3>
        <p class="stat-number"><?= $monthlyStats['total_users'] ?></p>
        <p class="stat-detail">Total Time: <?= $monthlyStats['total_time'] ?> mins</p>
    </div>
    <div class="stat-card">
        <h3>Yearly Users</h3>
        <p class="stat-number"><?= $yearlyStats['total_users'] ?></p>
        <p class="stat-detail">Total Time: <?= $yearlyStats['total_time'] ?> mins</p>
    </div>
</div>

<h1 class="title">All Users Activity</h1>

<!-- Filters -->
<form method="POST" class="filter-form">
        <div class="filter-group">
            <label for="time_range">Time Range:</label>
            <select name="time_range" id="time_range">
                <option value="1 DAY" <?= (isset($_POST['time_range']) && $_POST['time_range'] == '1 DAY') ? 'selected' : ''; ?>>Last Day</option>
                <option value="1 MONTH" <?= (isset($_POST['time_range']) && $_POST['time_range'] == '1 MONTH') ? 'selected' : ''; ?>>Last Month</option>
                <option value="1 YEAR" <?= (isset($_POST['time_range']) && $_POST['time_range'] == '1 YEAR') ? 'selected' : ''; ?>>Last Year</option>
            </select>
        </div>

        <div class="filter-group">
            <label for="browser">Browser:</label>
            <select name="browser" id="browser">
                <option value="Chrome" <?= (isset($_POST['browser']) && $_POST['browser'] == 'Chrome') ? 'selected' : ''; ?>>Chrome</option>
                <option value="Firefox" <?= (isset($_POST['browser']) && $_POST['browser'] == 'Firefox') ? 'selected' : ''; ?>>Firefox</option>
                <option value="Safari" <?= (isset($_POST['browser']) && $_POST['browser'] == 'Safari') ? 'selected' : ''; ?>>Safari</option>
                <option value="Edge" <?= (isset($_POST['browser']) && $_POST['browser'] == 'Edge') ? 'selected' : ''; ?>>Edge</option>
                <option value="Opera" <?= (isset($_POST['browser']) && $_POST['browser'] == 'Opera') ? 'selected' : ''; ?>>Opera</option>
            </select>
        </div>

        <div class="filter-group">
            <label for="device">Device Type:</label>
            <select name="device" id="device">
                <option value="Mobile" <?= (isset($_POST['device']) && $_POST['device'] == 'Mobile') ? 'selected' : ''; ?>>Mobile</option>
                <option value="Tablet" <?= (isset($_POST['device']) && $_POST['device'] == 'Tablet') ? 'selected' : ''; ?>>Tablet</option>
                <option value="Desktop" <?= (isset($_POST['device']) && $_POST['device'] == 'Desktop') ? 'selected' : ''; ?>>Desktop</option>
            </select>
        </div>
        
         <div class="filter-group">
        <label for="sort">Sort by:</label>
        <select id="sort" onchange="sortTable()">
            <option value="total_time DESC">Highest Time Spent</option>
            <option value="total_time ASC">Lowest Time Spent</option>
        </select>
        </div>
        

        <div class="filter-buttons">
            <button type="submit" class="apply-btn">Apply Filters</button>
            <button type="button" class="reset-btn" onclick="window.location.href = window.location.pathname;">All</button>
        </div>
     
</form>


<!-- Search -->
<div style="display: flex; justify-content: center; margin-top: 10px;">
    <input 
        type="text" 
        id="search" 
        placeholder="Search by name..." 
        onkeyup="filterTable()"
        style="padding: 8px 12px; width: 50%; max-width: 100%;"
    >
</div>

<!-- Table -->
<table border="1">
    <tr>
        <th>Name</th>
        <th>Time Spent (mins)</th>
        <th>Action</th>
        <th>Browser</th>
        <th>Device</th>
       
    </tr>
  <?php foreach ($tableData as $row): ?>
    <tr>
        <td><?= htmlspecialchars($row['name']) ?></td>
        <td><?= htmlspecialchars($row['total_time']) ?></td>
        <td><?= htmlspecialchars($row['action']) ?></td>
        <td><?= htmlspecialchars($row['browser']) ?></td>
        <td><?= htmlspecialchars($row['device']) ?></td>
       
    </tr>
<?php endforeach; ?>

</table>

<!-- Pagination -->
<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>&sort=<?= $sort ?>">Previous</a>
    <?php endif; ?>

    <span>Page <?= $page ?> of <?= $totalPages ?></span>

    <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page + 1 ?>&sort=<?= $sort ?>">Next</a>
    <?php endif; ?>
</div>

<!-- Print & Export Buttons -->
<div class="button-container" style="display: flex; justify-content: center; gap: 15px; margin: 20px 0;">
    <button onclick="window.print()" style="padding: 8px 16px; background: #1B355B; color: white; border: none; border-radius: 4px; cursor: pointer;">Print Data</button>
    <button onclick="exportCSV()" style="padding: 8px 16px; background: #1e2a38; color: white; border: none; border-radius: 4px; cursor: pointer;">Export CSV</button>
    <button onclick="exportPDF()" style="padding: 8px 16px; background: #055B59; color: white; border: none; border-radius: 4px; cursor: pointer;">Export PDF</button>
</div>


   <div class="charts-container">
    <div class="chart-wrapper">
        <h2 class="section-title">Activity Graph</h2>
        <canvas id="userActivityChart"></canvas>
    </div>
    
    <div class="chart-wrapper">
        <h1 class="section-title">User Activity (Monday - Sunday)</h1>
        <canvas id="weeklyActivityChart"></canvas>
    </div>
</div>

<!-- Same JavaScript as above -->
<script>
// First Chart (Bar Graph)
const ctx = document.getElementById('userActivityChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= $labels ?>,
        datasets: [{
            label: 'Time Spent (mins)',
            data: <?= $users ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.5)'
        }]
    },
    options: {
        responsive: true,
        scales: {
            x: {
                ticks: {
                    color: 'white' // X-axis text color
                },
                grid: {
                    color: 'rgba(255, 255, 255, 0.1)' // Optional: light grid lines
                }
            },
            y: {
                beginAtZero: true,
                ticks: {
                    color: 'white' // Y-axis text color
                },
                grid: {
                    color: 'rgba(255, 255, 255, 0.1)' // Optional: light grid lines
                }
            }
        },
        plugins: {
            legend: {
                labels: {
                    color: 'white' // Legend text color
                }
            }
        }
    }
});

// Second Chart (Line Graph)
const ctxWeek = document.getElementById('weeklyActivityChart').getContext('2d');
new Chart(ctxWeek, {
    type: 'line',
    data: {
        labels: <?= $weekLabels ?>,
        datasets: [{
            label: 'Time Spent (mins)',
            data: <?= $weekData ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 2,
            pointBackgroundColor: 'rgba(54, 162, 235, 1)',
            pointBorderColor: '#fff',
            pointRadius: 5
        }]
    },
    options: {
        responsive: true,
        scales: {
            x: {
                ticks: {
                    color: 'white' // X-axis text color
                },
                grid: {
                    color: 'rgba(255, 255, 255, 0.1)'
                }
            },
            y: {
                beginAtZero: true,
                ticks: {
                    color: 'white' // Y-axis text color
                },
                grid: {
                    color: 'rgba(255, 255, 255, 0.1)'
                }
            }
        },
        plugins: {
            legend: {
                labels: {
                    color: 'white' // Legend text color
                }
            }
        }
    }
});
</script>



<script>
document.addEventListener("DOMContentLoaded", function () {
    let currentPage = 1;

    function fetchLogs(page = 1) {
        fetch(report-view.php?ajax=1&page=${page})
            .then(response => response.json())
            .then(data => {
                updateTable(data.logs);
                updatePagination(page, data.total);
            })
            .catch(error => console.error("Error fetching logs:", error));
    }

    function updateTable(logs) {
        let tableBody = document.querySelector("tbody");
        tableBody.innerHTML = ""; // Clear existing rows

        logs.forEach(log => {
            let row = <tr>
                <td>${log.user_id}</td>
                <td>${log.action}</td>
                <td>${log.timestamp}</td>
                <td>${log.time_spent}</td>
            </tr>;
            tableBody.innerHTML += row;
        });
    }

    function updatePagination(page, total) {
        let totalPages = Math.ceil(total / 10);
        document.getElementById("currentPage").innerText = page;

        document.getElementById("prevPage").disabled = (page === 1);
        document.getElementById("nextPage").disabled = (page >= totalPages);
    }

    document.getElementById("prevPage").addEventListener("click", function () {
        if (currentPage > 1) {
            currentPage--;
            fetchLogs(currentPage);
        }
    });

    document.getElementById("nextPage").addEventListener("click", function () {
        currentPage++;
        fetchLogs(currentPage);
    });

    // Load first page
    fetchLogs();
});

</script>

 <footer>
    <p>&copy; 2025 STEMulator Analytics Dashboard</p>
  </footer>
</body>
</html>
