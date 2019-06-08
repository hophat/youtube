<?php 
include "api_youtube.php";
	// echo json_encode($_GET);
error_reporting('all');
	$link = $_GET['link'];
	$youtube =  new Youtube();
	$res = $youtube->get($link);
	$link = array("list"=>$res);
	echo json_encode($link);  
?>