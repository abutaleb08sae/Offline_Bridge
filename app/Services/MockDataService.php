<?php

namespace App\Services;

class MockDataService
{
    /**
     * Get system status items for Master PC.
     */
    public static function getSystemStatus(): array
    {
        return [
            [
                'title' => 'LAN Status',
                'value' => 'ONLINE',
                'badge' => 'CONNECTED',
                'type' => 'success',
                'desc' => '192.168.1.0/24 Subnet',
                'icon' => 'lan'
            ],
            [
                'title' => 'Internet Status',
                'value' => 'OFFLINE',
                'badge' => 'AIR-GAPPED',
                'type' => 'neutral',
                'desc' => 'Isolated Local Network',
                'icon' => 'globe'
            ],
            [
                'title' => 'Server Status',
                'value' => 'RUNNING',
                'badge' => 'HEALTHY',
                'type' => 'success',
                'desc' => 'Master Controller Active',
                'icon' => 'server'
            ],
            [
                'title' => 'Database Status',
                'value' => 'CONNECTED',
                'badge' => 'SYNCED',
                'type' => 'success',
                'desc' => 'Local SQLite / MySQL',
                'icon' => 'database'
            ],
        ];
    }

    /**
     * Get summary metrics.
     */
    public static function getStats(): array
    {
        return [
            'connected_clients' => [
                'current' => 18,
                'total' => 30,
                'percentage' => 60,
                'label' => 'Active LAN Workstations'
            ],
            'registered_users' => [
                'total' => 142,
                'students' => 135,
                'instructors' => 7,
                'label' => 'CSE Department Users'
            ],
            'supported_apps' => [
                'total' => 3,
                'active' => 1,
                'label' => 'Local Software Packages'
            ],
            'files_stored' => [
                'count' => 284,
                'size' => '1.4 GB',
                'label' => 'Lab Submissions & Materials'
            ]
        ];
    }

    /**
     * Get managed applications overview.
     */
    public static function getApplications(): array
    {
        return [
            [
                'id' => 'cisco-pt',
                'name' => 'Cisco Packet Tracer',
                'version' => 'v8.2.1',
                'category' => 'Networking & Simulation',
                'status' => 'Partial',
                'status_label' => 'PARTIALLY SUPPORTED',
                'installed_clients' => 15,
                'note' => 'Internet-dependent: initial login & online assessment sync required offline bypass.'
            ],
            [
                'id' => 'wireshark',
                'name' => 'Wireshark Packet Sniffer',
                'version' => 'v4.0.6',
                'category' => 'Network Protocol Analysis',
                'status' => 'Testing',
                'status_label' => 'TESTING',
                'installed_clients' => 8,
                'note' => 'Local packet capture enabled on student LAN interfaces.'
            ],
            [
                'id' => 'judge',
                'name' => 'Local Programming Judge',
                'version' => 'v1.0.0-alpha',
                'category' => 'Competitive Programming',
                'status' => 'Planned',
                'status_label' => 'PLANNED',
                'installed_clients' => 0,
                'note' => 'Offline C++/Python code compilation & automated test-case runner.'
            ]
        ];
    }

    /**
     * Get detailed Offline Compatibility Test Report for Cisco Packet Tracer.
     */
    public static function getPacketTracerReport(): array
    {
        return [
            [
                'feature' => 'Topology Creation & Editing',
                'internet_required' => false,
                'lan_required' => false,
                'result' => 'PASS',
                'notes' => 'Drag-and-drop routers, switches, PCs work 100% offline without WAN.'
            ],
            [
                'feature' => 'IP & Device Configuration',
                'internet_required' => false,
                'lan_required' => false,
                'result' => 'PASS',
                'notes' => 'CLI and GUI static IP, subnetting, DHCP configuration function locally.'
            ],
            [
                'feature' => 'Simulation & Packet Inspection',
                'internet_required' => false,
                'lan_required' => false,
                'result' => 'PASS',
                'notes' => 'ICMP/PDU step-by-step visual animation runs completely on host RAM.'
            ],
            [
                'feature' => 'Save & Load (.pkt / .pka) Files',
                'internet_required' => false,
                'lan_required' => false,
                'result' => 'PASS',
                'notes' => 'Local disk read/write for lab files is fully supported.'
            ],
            [
                'feature' => 'Multiuser Peer-to-Peer over LAN',
                'internet_required' => false,
                'lan_required' => true,
                'result' => 'PASS',
                'notes' => 'Direct TCP socket connection between student PCs over local subnet.'
            ],
            [
                'feature' => 'Online Account Authentication',
                'internet_required' => true,
                'lan_required' => false,
                'result' => 'FAIL',
                'notes' => 'Requires Cisco NetAcad cloud server login; requires guest mode / local patch.'
            ],
            [
                'feature' => 'Online Assessment & Grade Sync',
                'internet_required' => true,
                'lan_required' => false,
                'result' => 'FAIL',
                'notes' => 'Cloud grading servers are inaccessible; grades saved locally in Master DB instead.'
            ]
        ];
    }

    /**
     * Get connected LAN clients.
     */
    public static function getConnectedClients(): array
    {
        return [
            [
                'hostname' => 'LAB-PC-01',
                'ip' => '192.168.1.101',
                'mac' => 'A4:BB:6D:11:22:01',
                'user' => 'Rahim Ahmed (2022-CSE-012)',
                'app' => 'Cisco Packet Tracer v8.2',
                'status' => 'online',
                'installed_count' => 2,
                'last_seen' => 'Just now'
            ],
            [
                'hostname' => 'LAB-PC-02',
                'ip' => '192.168.1.102',
                'mac' => 'A4:BB:6D:11:22:02',
                'user' => 'Fatima Yeasmin (2022-CSE-015)',
                'app' => 'Cisco Packet Tracer v8.2',
                'status' => 'online',
                'installed_count' => 2,
                'last_seen' => '2 mins ago'
            ],
            [
                'hostname' => 'LAB-PC-03',
                'ip' => '192.168.1.103',
                'mac' => 'A4:BB:6D:11:22:03',
                'user' => 'Tanvir Hossain (2022-CSE-028)',
                'app' => 'None (Idle)',
                'status' => 'idle',
                'installed_count' => 1,
                'last_seen' => '12 mins ago'
            ],
            [
                'hostname' => 'LAB-PC-04',
                'ip' => '192.168.1.104',
                'mac' => 'A4:BB:6D:11:22:04',
                'user' => 'Anika Tabassum (2022-CSE-033)',
                'app' => 'Wireshark v4.0.6',
                'status' => 'online',
                'installed_count' => 3,
                'last_seen' => 'Just now'
            ],
            [
                'hostname' => 'LAB-PC-05',
                'ip' => '192.168.1.105',
                'mac' => 'A4:BB:6D:11:22:05',
                'user' => 'Naimur Rahman (2022-CSE-041)',
                'app' => 'Offline',
                'status' => 'offline',
                'installed_count' => 2,
                'last_seen' => '45 mins ago'
            ],
            [
                'hostname' => 'LAB-PC-06',
                'ip' => '192.168.1.106',
                'mac' => 'A4:BB:6D:11:22:06',
                'user' => 'Sadiya Islam (2022-CSE-050)',
                'app' => 'Cisco Packet Tracer v8.2',
                'status' => 'online',
                'installed_count' => 2,
                'last_seen' => '1 min ago'
            ]
        ];
    }

    /**
     * Get Users list.
     */
    public static function getUsersList(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Admin Master',
                'email' => 'admin@offlinebridge.local',
                'role' => 'ADMIN',
                'status' => 'ACTIVE',
                'last_login' => 'Today at 08:30 AM'
            ],
            [
                'id' => 2,
                'name' => 'Dr. Kamrul Hasan',
                'email' => 'kamrul.hasan@cse.univ.edu',
                'role' => 'INSTRUCTOR',
                'status' => 'ACTIVE',
                'last_login' => 'Yesterday at 04:15 PM'
            ],
            [
                'id' => 3,
                'name' => 'Rahim Ahmed',
                'email' => 'rahim.012@student.cse.edu',
                'role' => 'STUDENT',
                'status' => 'ACTIVE',
                'last_login' => 'Just now'
            ],
            [
                'id' => 4,
                'name' => 'Fatima Yeasmin',
                'email' => 'fatima.015@student.cse.edu',
                'role' => 'STUDENT',
                'status' => 'ACTIVE',
                'last_login' => '2 mins ago'
            ],
            [
                'id' => 5,
                'name' => 'Tanvir Hossain',
                'email' => 'tanvir.028@student.cse.edu',
                'role' => 'STUDENT',
                'status' => 'INACTIVE',
                'last_login' => '3 days ago'
            ]
        ];
    }

    /**
     * Get Files list.
     */
    public static function getFilesList(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'networking_lab_experiment_01.pkt',
                'category' => 'Assignments',
                'type' => 'PKT',
                'size' => '2.4 MB',
                'uploaded_by' => 'Dr. Kamrul Hasan',
                'date' => '2026-10-05 09:30 AM'
            ],
            [
                'id' => 2,
                'name' => 'cisco_packet_tracer_setup_guide.pdf',
                'category' => 'Templates',
                'type' => 'PDF',
                'size' => '4.8 MB',
                'uploaded_by' => 'Admin Master',
                'date' => '2026-10-04 02:15 PM'
            ],
            [
                'id' => 3,
                'name' => 'rahim_ahmed_lab1_submission.pkt',
                'category' => 'Student Uploads',
                'type' => 'PKT',
                'size' => '1.8 MB',
                'uploaded_by' => 'Rahim Ahmed',
                'date' => 'Today at 09:12 AM'
            ],
            [
                'id' => 4,
                'name' => 'fatima_yeasmin_lab1_submission.pkt',
                'category' => 'Student Uploads',
                'type' => 'PKT',
                'size' => '1.9 MB',
                'uploaded_by' => 'Fatima Yeasmin',
                'date' => 'Today at 09:20 AM'
            ]
        ];
    }

    /**
     * Get Recent Activities Stream.
     */
    public static function getActivities(): array
    {
        return [
            [
                'type' => 'user_connect',
                'category' => 'Users',
                'message' => 'Student Rahim Ahmed (2022-CSE-012) connected from LAB-PC-01',
                'time' => '2 minutes ago',
                'icon' => 'user'
            ],
            [
                'type' => 'app_start',
                'category' => 'Applications',
                'message' => 'Fatima Yeasmin launched Cisco Packet Tracer v8.2 on LAB-PC-02',
                'time' => '5 minutes ago',
                'icon' => 'app'
            ],
            [
                'type' => 'file_upload',
                'category' => 'Files',
                'message' => 'Tanvir Hossain uploaded lab_session_03.pkt (1.4 MB)',
                'time' => '14 minutes ago',
                'icon' => 'file'
            ],
            [
                'type' => 'system_backup',
                'category' => 'System',
                'message' => 'Master PC scheduled local database backup completed successfully',
                'time' => '1 hour ago',
                'icon' => 'backup'
            ],
            [
                'type' => 'user_disconnect',
                'category' => 'Users',
                'message' => 'Client workstation LAB-PC-05 disconnected from LAN',
                'time' => '2 hours ago',
                'icon' => 'disconnect'
            ]
        ];
    }

    /**
     * Get System Logs.
     */
    public static function getLogsList(): array
    {
        return [
            [
                'timestamp' => '2026-10-07 03:20:12',
                'level' => 'INFO',
                'source' => 'LAN Listener',
                'message' => 'Heartbeat ping received from LAB-PC-01 (192.168.1.101).'
            ],
            [
                'timestamp' => '2026-10-07 03:18:45',
                'level' => 'INFO',
                'source' => 'App Dispatcher',
                'message' => 'Cisco Packet Tracer v8.2 process started on LAB-PC-02.'
            ],
            [
                'timestamp' => '2026-10-07 03:10:00',
                'level' => 'WARN',
                'source' => 'Network Monitor',
                'message' => 'External WAN gateway 192.168.1.1 unreachable (Air-gapped mode active).'
            ],
            [
                'timestamp' => '2026-10-07 02:45:19',
                'level' => 'ERROR',
                'source' => 'Auth Service',
                'message' => 'Failed online auth attempt for user 2022-CSE-041 (Internet OFFLINE).'
            ],
            [
                'timestamp' => '2026-10-07 02:00:00',
                'level' => 'INFO',
                'source' => 'Backup Agent',
                'message' => 'Created database snapshot: backup_20261007_0200.sql (14.2 MB).'
            ]
        ];
    }

    /**
     * Get Backups List.
     */
    public static function getBackupsList(): array
    {
        return [
            [
                'id' => 101,
                'filename' => 'offlinebridge_backup_20261007_0200.sql.gz',
                'date' => '2026-10-07 02:00 AM',
                'size' => '14.2 MB',
                'status' => 'COMPLETED'
            ],
            [
                'id' => 100,
                'filename' => 'offlinebridge_backup_20261006_0200.sql.gz',
                'date' => '2026-10-06 02:00 AM',
                'size' => '13.8 MB',
                'status' => 'COMPLETED'
            ],
            [
                'id' => 99,
                'filename' => 'offlinebridge_backup_20261005_0200.sql.gz',
                'date' => '2026-10-05 02:00 AM',
                'size' => '13.1 MB',
                'status' => 'COMPLETED'
            ]
        ];
    }

    /**
     * Get Settings Config.
     */
    public static function getSettingsData(): array
    {
        return [
            'network' => [
                'master_ip' => '192.168.1.10',
                'http_port' => '8000',
                'subnet_mask' => '255.255.255.0',
                'gateway' => '192.168.1.1'
            ],
            'security' => [
                'session_timeout' => '120',
                'password_min_length' => '8',
                'max_file_size' => '100',
                'allowed_extensions' => '.pkt, .pdf, .cpp, .zip, .png'
            ],
            'general' => [
                'lab_name' => 'CSE Computer Network Lab 01',
                'master_host' => 'OFFLINE-MASTER-SERVER',
                'auto_backup' => 'daily'
            ]
        ];
    }

    public static function getChartData(): array
    {
        return [
            'labels' => ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00'],
            'values' => [4, 12, 22, 28, 25, 18, 10]
        ];
    }

    public static function getNetworkDetails(): array
    {
        return [
            'master_ip' => '192.168.1.10',
            'hostname' => 'OFFLINE-MASTER-SERVER',
            'subnet_mask' => '255.255.255.0',
            'server_port' => '8000',
            'gateway' => '192.168.1.1',
            'mac_address' => '00:1A:2B:3C:4D:5E'
        ];
    }
}
