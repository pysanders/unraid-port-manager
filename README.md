# Unraid Port Manager Plugin

A comprehensive port management plugin for Unraid that displays all ports in use across your system including Docker containers, VMs, and system services. Features search functionality and port suggestion capabilities.

## Features

- **Comprehensive Port Scanning**: Displays ports from Docker containers, VMs, and system services
- **Real-time Monitoring**: Live view of port status and usage
- **Search & Filter**: Search ports by service name, IP address, port number, or protocol
- **Port Suggestions**: Intelligent port suggestion for new applications
- **Category Filtering**: Filter by Docker, VM, or System ports
- **Statistics Dashboard**: Overview of port usage across categories
- **Responsive Design**: Works on desktop and mobile devices

## Installation

### Method 1: Community Applications (Recommended)
1. Install the Community Applications plugin if not already installed
2. Search for "Port Manager" in Community Applications
3. Click Install

### Method 2: Manual Installation
1. Go to **Plugins** in the Unraid webGui
2. Click **Install Plugin**
3. Enter the plugin URL: `https://raw.githubusercontent.com/nathansanders/unraid-port-manager/main/port-manager.plg`
4. Click **Install**

## Usage

1. After installation, go to **Settings > Port Manager**
2. The plugin will automatically scan and display all ports in use
3. Use the search box to find specific ports or services
4. Filter by category using the dropdown menu
5. Click "Suggest Port" to find an available port for new applications
6. Click "Refresh" to update the port information

## Port Categories

### Docker Ports
- Shows all port mappings from running Docker containers
- Displays both host and container ports
- Includes container names and protocols

### VM Ports
- Scans active virtual machines for open ports
- Shows network interface information
- Displays port status and descriptions

### System Ports
- Lists all system services listening on ports
- Shows process information
- Includes common service names (HTTP, SSH, etc.)

## Port Suggestion

The port suggestion feature helps you find available ports for new applications:
1. Click the "Suggest Port" button
2. Specify the port range (default: 8080-9999)
3. The system will suggest the first available port in that range

## Requirements

- Unraid 6.9.0 or later
- PHP with exec() function enabled
- Network tools (netstat, nmap) - usually pre-installed on Unraid

## Security Considerations

This plugin uses system commands to gather port information:
- `docker ps` - for Docker container ports
- `virsh` - for VM information
- `netstat` - for system ports
- `nmap` - for VM port scanning

All commands are executed with appropriate security measures and input validation.

## Troubleshooting

### No Docker Ports Showing
- Ensure Docker service is running
- Check that containers have port mappings configured

### VM Ports Not Detected
- Verify that VMs are running
- Ensure libvirt/virsh is properly configured

### System Ports Missing
- Check that netstat is available on your system
- Verify plugin has necessary permissions

### Permission Issues
If you encounter permission errors:
1. Check plugin file permissions: `chmod +x /usr/local/emhttp/plugins/port-manager/*`
2. Restart the web server: `/etc/rc.d/rc.nginx restart`

## Support

For issues, feature requests, or contributions:
- GitHub: https://github.com/nathansanders/unraid-port-manager
- Unraid Forums: Search for "Port Manager Plugin"

## License

This project is licensed under the GNU General Public License v2.0.

## Changelog

### Version 2025.07.27
- Initial release
- Docker container port scanning
- VM port detection
- System service port monitoring
- Search and filtering capabilities
- Port suggestion feature
- Responsive web interface

## Contributing

Contributions are welcome! Please feel free to submit pull requests or open issues on GitHub.

## Credits

Developed by Nathan Sanders for the Unraid community.