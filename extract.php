<?php

function select($mysql, $query) {
	$response = array();
	$result = $mysql->query($query);
	if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
      $response[] = $row;
    }
	}

	return $response;
}

if (!isset($argv['1'])) {
	exit("extract.php output_file_path.json");
}

$outputFile = $argv['1'];
$servername = "localhost";
$username = "root";
$password = "root";

$mysql = new mysqli($servername, $username, $password);
if ($mysql->connect_error) {
  die("Connection failed: " . $mysql->connect_error);
}

$finalList = array();
$databases = select($mysql, "show databases");
foreach ($databases as $key => $database) {
	$database = $database['Database'];
	if ($database == 'information_schema' || $database == 'performance_scheme') { continue; }
	
	$mysql->select_db($database);
	$tables = select($mysql, 'show tables');
	
	$tblList = array();
	$totalColumns = $totalRows = $totalIndexes = 0;
	foreach ($tables as $key => $table) {
		$list = array();
		$table = current($table);
		$indexes = select($mysql, "show indexes from $table");
		$columns = select($mysql, "show columns from $table");
		$rows = select($mysql, "select count(*) as total from $table");

		$columnsCount = count($columns);
		$rowsCount = $rows['0']['total'];
		$indexesCount = count($indexes);
		$list['stats']['columns'] = $columnsCount;
		$list['stats']['rows'] = $rowsCount;
		$list['stats']['indexes'] = $indexesCount;
		$list['columns'] = $columns;
		$list['indexes'] = $indexes;

		$tblList[$table] = $list;

		$totalColumns += $columnsCount;
		$totalRows += $rowsCount;
		$totalIndexes += $indexesCount;
	}

	$finalList[$database] = array(
		'stats' => array(
			'tables' => count($tables),
			'columns' => $totalColumns,
			'rows' => $totalRows,
			'indexes' => $totalIndexes
		),
		'list' => $tblList 
	);
}

file_put_contents($outputFile, json_encode($finalList, JSON_PRETTY_PRINT));
