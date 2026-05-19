<?php
include 'DBConnector.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Visits</title>
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
        
        /* Top Header (Search & Home) */
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        /* Search Bar */
        .search-container {
            display: flex;
            align-items: center;
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

        /* Home Button */
        .btn-home {
            background-color: black;
            color: white;
            padding: 12px 50px;
            border-radius: 25px;
            text-decoration: none;
            font-size: 22px;
            font-weight: bold;
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
    <div class="top-header">
        <div class="search-container">
            <label for="search">Search: </label>
            <input type="text" id="search" class="search-input">
            <span class="filter-icon">filter</span>
        </div>
        
        <a href="index.php" class="btn-home">Home</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Visit ID</th>
                <th>Patient</th>
                <th>Physician</th>
                <th>Visit Date</th>
                <th>Symptoms</th>
                <th>Prescription</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Fetch data from patient_visits and join with patient and physician tables to get names
            // ORDER BY v.visit_ID ASC to display IDs from 1 to 5
            $sql = "
                SELECT 
                    v.visit_ID,
                    CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
                    CONCAT(doc.first_name, ' ', doc.last_name) AS physician_name,
                    v.visit_date,
                    v.symptoms_description,
                    v.prescription_details
                FROM patient_visits v
                JOIN patient p ON v.patient_ID = p.patient_ID
                JOIN physician doc ON v.physician_ID = doc.physician_ID
                ORDER BY v.visit_ID ASC
            ";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['visit_ID']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['patient_name']) . "</td>";
                    echo "<td>Dr. " . htmlspecialchars($row['physician_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['visit_date']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['symptoms_description']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['prescription_details']) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6'>No visits found</td></tr>";
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