<?php
/*
 * Port Manager API
 * Copyright 2025, Nathan Sanders
 * 
 * API endpoint for port manager functionality
 */

require_once 'include/port_scanner.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$scanner = new PortScanner();

switch ($action) {
    case 'getAllPorts':
        echo json_encode($scanner->getAllPorts());
        break;
        
    case 'getDockerPorts':
        echo json_encode($scanner->getDockerPorts());
        break;
        
    case 'getVMPorts':
        echo json_encode($scanner->getVMPorts());
        break;
        
    case 'getSystemPorts':
        echo json_encode($scanner->getSystemPorts());
        break;
        
    case 'suggestPort':
        $startPort = intval($_GET['start'] ?? 8080);
        $endPort = intval($_GET['end'] ?? 9999);
        echo json_encode(['suggested_port' => $scanner->suggestPort($startPort, $endPort)]);
        break;
        
    case 'searchPorts':
        $query = $_GET['query'] ?? '';
        $category = $_GET['category'] ?? 'all';
        echo json_encode($scanner->searchPorts($query, $category));
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?>