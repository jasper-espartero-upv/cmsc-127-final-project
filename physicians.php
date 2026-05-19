<?php
include 'DBConnector.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Physicians Record</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: white;
            margin: 0;
            padding: 40px; 
        }
        .container {
            width: 100%; 
            box-sizing: border-box;
        }
        /* Top Navigation */
        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
        }
        .tabs {
            display: flex;
            gap: 15px;
        }
        .tab {
            padding: 10px 25px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: bold;
            color: white;
            font-size: 16px; 
        }

        /* Updated Active State for Physicians */
        .tab.active { background-color: black; }
        .tab.inactive { background-color: #999; }
        
        .btn-home {
            background-color: black;
            color: white;
            padding: 12px 50px;
            border-radius: 25px;
            text-decoration: none;
            font-size: 22px;
            font-weight: bold;
        }
        
        /* Search Bar */
        .search-container {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            gap: 12px;
            font-size: 18px; 
        }
        .search-input {
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            width: 250px; 
            font-size: 16px;
            box-shadow: inset 1px 1px 3px rgba(0,0,0,0.1);
        }
        .filter-icon {
            font-size: 24px;
            cursor: pointer;
        }

        /* Table */
        table {
            width: 100%; 
            border-collapse: collapse;
            margin-bottom: 40px;
            text-align: center;
            font-size: 15px; 
        }
        th, td {
            border: 2px solid black;
            padding: 20px 10px; 
        }
        th {
            font-weight: bold;
            background-color: #fff;
            font-size: 16px;
        }

        /* Bottom Action Buttons */
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 30px;
        }
        .btn-action {
            background-color: black;
            color: white;
            border: none;
            padding: 12px 60px; 
            border-radius: 25px;
            font-size: 20px;
            cursor: pointer;
        }

    </style>
</head>
<body>

<div class="container">
    <div class="top-nav">
        <div class="tabs">
            <a href="patients.php" class="tab inactive">Patients</a>
            
            <a href="physicians.php" class="tab active">Physicians</a>
            
            <a href="staff.php" class="tab inactive">Staff</a>
        </div>
        <a href="#" class="btn-home">Home</a>
    </div>

    <div class="search-container">
        <label for="search">Search: </label>
        <input type="text" id="search" class="search-input">
        <span class="filter-icon">filter</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Specialization(s)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Fetch data from physician table and join with specialization
            // GROUP_CONCAT combines multiple specializations into one comma-separated string
            $sql = "
                SELECT 
                    p.physician_ID, 
                    p.first_name, 
                    p.last_name, 
                    GROUP_CONCAT(ps.specialization SEPARATOR ', ') AS specializations 
                FROM physician p 
                LEFT JOIN physician_specialization ps ON p.physician_ID = ps.physician_ID 
                GROUP BY p.physician_ID
            ";
            
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    // Format full name
                    $fullName = htmlspecialchars($row['first_name'] . " " . $row['last_name']);
                    
                    // Handle physicians with no assigned specialization
                    $specializations = $row['specializations'] ? htmlspecialchars($row['specializations']) : "<em>None assigned</em>";

                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['physician_ID']) . "</td>";
                    echo "<td>Dr. " . $fullName . "</td>";
                    echo "<td>" . $specializations . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='3'>No physicians found</td></tr>";
            }
            
            $conn->close();
            ?>
        </tbody>
    </table>

    <div class="action-buttons">
        <button class="btn-action">Add</button>
        <button class="btn-action">Select</button>
        <button class="btn-action">Edit</button>
        <button class="btn-action">Delete</button>
    </div>

</div>

</body>
</html>