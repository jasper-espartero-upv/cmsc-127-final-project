<?php
include 'DBConnector.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients Record</title>
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
            <a href="patients.php" class="tab active">Patients</a> 
            
            <a href="physicians.php" class="tab inactive">Physicians</a>
            
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
                <th>Age</th>
                <th>Sex</th>
                <th>Contact No.</th>
                <th>Emergency Contact</th>
                <th>Affiliation</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Fetch data from the patient table
            $sql = "SELECT * FROM patient";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    // Calculate Age from birthdate
                    $dob = new DateTime($row['birthdate']);
                    $now = new DateTime();
                    $age = $now->diff($dob)->y;

                    // Format full name
                    $fullName = htmlspecialchars($row['first_name'] . " " . $row['last_name']);
                    
                    // Format Emergency Contact (Name + Number)
                    $emergency = htmlspecialchars($row['emergency_contact_name']) . "<br><small style='font-size: 13px;'>" . htmlspecialchars($row['emergency_contact_number']) . "</small>";

                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['patient_ID']) . "</td>";
                    echo "<td>" . $fullName . "</td>";
                    echo "<td>" . $age . "</td>";
                    echo "<td>" . htmlspecialchars($row['sex']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['contact_number']) . "</td>";
                    echo "<td>" . $emergency . "</td>";
                    echo "<td>" . htmlspecialchars($row['affiliation']) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='7'>No patients found</td></tr>";
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
</div>

</body>
</html>
