<?php
/*
 * Port Manager for Unraid
 * Copyright 2025, Nathan Sanders
 * 
 * Port scanning functionality for Docker, VMs, and system services
 */

class PortScanner {
    
    public function getAllPorts() {
        $ports = array();
        $usedPorts = array(); // Track used ports to avoid duplicates
        
        // Get Docker ports first (highest priority)
        $ports['docker'] = $this->getDockerPorts();
        foreach ($ports['docker'] as $port) {
            $portKey = $port['host_ip'] . ':' . $port['host_port'] . '/' . $port['protocol'];
            $usedPorts[$portKey] = true;
        }
        
        // Get VM ports second
        $ports['vms'] = $this->getVMPorts();
        foreach ($ports['vms'] as $port) {
            $portKey = $port['host_ip'] . ':' . $port['host_port'] . '/' . $port['protocol'];
            $usedPorts[$portKey] = true;
        }
        
        // Get system ports last and filter out duplicates
        $systemPorts = $this->getSystemPorts();
        $ports['system'] = array();
        foreach ($systemPorts as $port) {
            $portKey = $port['host_ip'] . ':' . $port['host_port'] . '/' . $port['protocol'];
            // Only add if not already used by Docker or VM
            if (!isset($usedPorts[$portKey])) {
                $ports['system'][] = $port;
            }
        }
        
        return $ports;
    }
    
    public function getDockerPorts() {
        $dockerPorts = array();
        $seenPorts = array();
        
        exec("docker ps --format 'table {{.Names}}\t{{.Ports}}' --no-trunc", $output);
        
        foreach ($output as $line) {
            if (strpos($line, 'NAMES') === 0) continue;
            
            $parts = preg_split('/\s+/', $line, 2);
            if (count($parts) < 2) continue;
            
            $containerName = trim($parts[0]);
            $portsString = trim($parts[1]);
            
            if (empty($portsString) || $portsString === '-') continue;
            
            $portMappings = explode(', ', $portsString);
            
            foreach ($portMappings as $mapping) {
                if (preg_match('/(\d+\.\d+\.\d+\.\d+):(\d+)->(\d+)\/(\w+)/', $mapping, $matches)) {
                    $portKey = $matches[1] . ':' . $matches[2] . '/' . $matches[4];
                    if (!isset($seenPorts[$portKey])) {
                        $dockerPorts[] = array(
                            'service' => $containerName,
                            'type' => 'docker',
                            'host_ip' => $matches[1],
                            'host_port' => $matches[2],
                            'container_port' => $matches[3],
                            'protocol' => $matches[4],
                            'status' => 'active'
                        );
                        $seenPorts[$portKey] = true;
                    }
                } elseif (preg_match('/(\d+)->(\d+)\/(\w+)/', $mapping, $matches)) {
                    $portKey = '0.0.0.0:' . $matches[1] . '/' . $matches[3];
                    if (!isset($seenPorts[$portKey])) {
                        $dockerPorts[] = array(
                            'service' => $containerName,
                            'type' => 'docker',
                            'host_ip' => '0.0.0.0',
                            'host_port' => $matches[1],
                            'container_port' => $matches[2],
                            'protocol' => $matches[3],
                            'status' => 'active'
                        );
                        $seenPorts[$portKey] = true;
                    }
                }
            }
        }
        
        return $dockerPorts;
    }
    
    public function getVMPorts() {
        $vmPorts = array();
        
        exec("virsh list --all", $output);
        
        foreach ($output as $line) {
            if (preg_match('/^\s*\d+\s+(\S+)\s+running/', $line, $matches)) {
                $vmName = $matches[1];
                
                exec("virsh dumpxml '$vmName' 2>/dev/null", $xmlOutput);
                $xml = implode("\n", $xmlOutput);
                
                if (preg_match_all('/<interface type=[\'"]network[\'"]>.*?<\/interface>/s', $xml, $interfaces)) {
                    foreach ($interfaces[0] as $interface) {
                        if (preg_match('/<target dev=[\'"](\w+)[\'"]/', $interface, $devMatch)) {
                            $this->scanVMInterface($vmName, $devMatch[1], $vmPorts);
                        }
                    }
                }
            }
        }
        
        return $vmPorts;
    }
    
    private function scanVMInterface($vmName, $interface, &$vmPorts) {
        exec("nmap -sT localhost 2>/dev/null | grep -E '^[0-9]+/(tcp|udp)'", $output);
        
        foreach ($output as $line) {
            if (preg_match('/^(\d+)\/(tcp|udp)\s+(\w+)\s*(.*)/', $line, $matches)) {
                $vmPorts[] = array(
                    'service' => $vmName,
                    'type' => 'vm',
                    'host_ip' => 'localhost',
                    'host_port' => $matches[1],
                    'container_port' => $matches[1],
                    'protocol' => $matches[2],
                    'status' => $matches[3],
                    'description' => trim($matches[4])
                );
            }
        }
    }
    
    public function getSystemPorts() {
        $systemPorts = array();
        
        exec("netstat -tlnp 2>/dev/null", $tcpOutput);
        exec("netstat -ulnp 2>/dev/null", $udpOutput);
        
        $allOutput = array_merge($tcpOutput, $udpOutput);
        
        foreach ($allOutput as $line) {
            if (preg_match('/^(tcp|udp)\s+\d+\s+\d+\s+([^:]+):(\d+)\s+[^\s]+\s+[^\s]+\s*(.*)/', $line, $matches)) {
                $protocol = $matches[1];
                $ip = $matches[2];
                $port = $matches[3];
                $process = trim($matches[4]);
                
                if ($ip === '127.0.0.1' || $ip === '::1') {
                    $scope = 'localhost';
                } elseif ($ip === '0.0.0.0' || $ip === '::') {
                    $scope = 'all interfaces';
                } else {
                    $scope = $ip;
                }
                
                $serviceName = $this->getServiceName($port, $protocol);
                
                $systemPorts[] = array(
                    'service' => $serviceName,
                    'type' => 'system',
                    'host_ip' => $ip,
                    'host_port' => $port,
                    'container_port' => $port,
                    'protocol' => $protocol,
                    'status' => 'listening',
                    'process' => $process,
                    'scope' => $scope
                );
            }
        }
        
        return $systemPorts;
    }
    
    private function getServiceName($port, $protocol) {
        $commonPorts = array(
            '22' => 'SSH',
            '23' => 'Telnet',
            '25' => 'SMTP',
            '53' => 'DNS',
            '80' => 'HTTP',
            '110' => 'POP3',
            '143' => 'IMAP',
            '443' => 'HTTPS',
            '993' => 'IMAPS',
            '995' => 'POP3S',
            '3389' => 'RDP',
            '5900' => 'VNC',
            '6379' => 'Redis',
            '3306' => 'MySQL',
            '5432' => 'PostgreSQL',
            '27017' => 'MongoDB'
        );
        
        if (isset($commonPorts[$port])) {
            return $commonPorts[$port];
        }
        
        exec("getent services $port/$protocol 2>/dev/null", $output);
        if (!empty($output)) {
            $parts = explode(' ', $output[0]);
            return ucfirst($parts[0]);
        }
        
        return "Port $port";
    }
    
    public function suggestPort($startPort = 5000, $endPort = 9999) {
        $usedPorts = array();
        $allPorts = $this->getAllPorts();
        
        foreach ($allPorts as $category) {
            foreach ($category as $portInfo) {
                $usedPorts[] = intval($portInfo['host_port']);
            }
        }
        
        $usedPorts = array_unique($usedPorts);
        
        // Create array of available ports
        $availablePorts = array();
        for ($port = $startPort; $port <= $endPort; $port++) {
            if (!in_array($port, $usedPorts)) {
                $availablePorts[] = $port;
            }
        }
        
        // Return random available port if any exist
        if (!empty($availablePorts)) {
            return $availablePorts[array_rand($availablePorts)];
        }
        
        return null;
    }
    
    public function searchPorts($query, $category = 'all') {
        $allPorts = $this->getAllPorts();
        $results = array();
        
        $searchCategories = ($category === 'all') ? array_keys($allPorts) : array($category);
        
        foreach ($searchCategories as $cat) {
            if (!isset($allPorts[$cat])) continue;
            
            foreach ($allPorts[$cat] as $portInfo) {
                $searchText = strtolower(implode(' ', $portInfo));
                if (strpos($searchText, strtolower($query)) !== false) {
                    $results[] = $portInfo;
                }
            }
        }
        
        return $results;
    }
}
?>