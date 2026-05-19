<?php
include 'DBConnector.php';     //imports/includes all the statements of the file specified

$sql = "SELECT * FROM employee"; //the query string is assigned into a variable named $sql
$result = $conn->query($sql);    //executes the query and puts the resulting data into a variable called $result

if ($result->num_rows > 0) {     //the function num_rows() checks if there are more than zero rows returned
    // output data of each row
    while($row = $result->fetch_assoc()) { //the function fetch_assoc() puts all the results into an associative array that we can loop through
        echo "<pre/>";              //white spaces and format of the next data to be echo/printed will be preserved
        print_r($row);              //prints the content of a variable in an array form

        echo "EmpID: " . $row["EmpID"].
            "<br>".                 //<br/> is an HTML element that represents NEWLINE
            " - Name: " . $row["EmpName"].
            " - Age: " . $row["Age"].
            " - Salary: " . $row["Salary"].
            " - HireDate: " . $row["HireDate"].
            "<br/><br/>";
    }
} else {
    echo "0 results";
}

$conn->close(); //closes the Database connection
?>