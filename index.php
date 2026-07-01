<?php
include 'db.php';

$limit = 5;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $limit;

$totalResult = $conn->query("SELECT COUNT(*) AS total FROM users");
$totalRows = $totalResult->fetch_assoc()['total'];

$totalPages = ceil($totalRows / $limit);

$sql = "SELECT * FROM users
        ORDER BY id DESC
        LIMIT $offset, $limit";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>User Listing</title>

<style>

body{
    font-family:Arial;
    background:#f5f5f5;
}

.container{
    width:900px;
    margin:40px auto;
}

table{
    width:100%;
    border-collapse:collapse;
    background:white;
}

table th,
table td{
    padding:12px;
    border:1px solid #ddd;
    text-align:left;
}

table th{
    background:#007bff;
    color:white;
}

.pagination{
    margin-top:20px;
}

.pagination a{
    display:inline-block;
    padding:8px 14px;
    margin:2px;
    text-decoration:none;
    border:1px solid #007bff;
    color:#007bff;
}

.pagination a.active{
    background:#007bff;
    color:white;
}

.pagination a:hover{
    background:#0056b3;
    color:white;
}

</style>

</head>

<body>

<div class="container">

<h2>User Listing Test Welcome</h2>

<table>

<tr>
<th>ID</th>
<th>Name</th>
<th>Email</th>
<th>Phone</th>
<th>City</th>
</tr>

<?php while($row = $result->fetch_assoc()) { ?>

<tr>
<td><?= $row['id']; ?></td>
<td><?= htmlspecialchars($row['name']); ?></td>
<td><?= htmlspecialchars($row['email']); ?></td>
<td><?= htmlspecialchars($row['phone']); ?></td>
<td><?= htmlspecialchars($row['city']); ?></td>
</tr>

<?php } ?>

</table>

<div class="pagination">

<?php

for($i=1;$i<=$totalPages;$i++)
{
    if($page==$i)
    {
        echo "<a class='active' href='?page=$i'>$i</a>";
    }
    else
    {
        echo "<a href='?page=$i'>$i</a>";
    }
}

?>

</div>

</div>

</body>
</html>