<?php
// config.php - Database configuration and constants

define('DATA_DIR', __DIR__ . '/data/');
define('USERS_FILE', DATA_DIR . 'users.json');
define('ARTWORKS_FILE', DATA_DIR . 'artworks.json');
define('SUBMISSIONS_FILE', DATA_DIR . 'submissions.json');
define('CATEGORIES_FILE', DATA_DIR . 'categories.json');

// Create data directory if it doesn't exist
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}

// Initialize JSON files if they don't exist
function initializeDataFiles() {
    $defaultData = [
        USERS_FILE => [
            [
                'id' => 1,
                'username' => 'admin',
                'email' => 'admin@indigenousart.com',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'role' => 'admin',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2,
                'username' => 'user1',
                'email' => 'user1@example.com',
                'password' => password_hash('user123', PASSWORD_DEFAULT),
                'role' => 'general',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ],
        ARTWORKS_FILE => [
            [
                'id' => 1,
                'title' => 'Ancient Cave Painting',
                'type' => 'Rock Art',
                'artist' => 'Unknown',
                'period' => 'c. 5000 BCE',
                'description' => 'Ancient cave paintings depicting hunting scenes and daily life of indigenous people.',
                'location' => 'Northern Territory, Australia',
                'image_url' => 'https://picsum.photos/seed/1/400/300',
                'condition_note' => 'Well preserved with minor weathering.',
                'status' => 'approved',
                'submitted_by' => 2,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2,
                'title' => 'Traditional Pottery Vessel',
                'type' => 'Pottery',
                'artist' => 'Maria Santos',
                'period' => 'c. 1800 CE',
                'description' => 'Ceremonial pottery vessel with traditional geometric patterns.',
                'location' => 'Southwestern United States',
                'image_url' => 'https://picsum.photos/seed/2/400/300',
                'condition_note' => 'Excellent condition with original pigments intact.',
                'status' => 'approved',
                'submitted_by' => 2,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ],
        SUBMISSIONS_FILE => [
            [
                'id' => 1,
                'title' => 'New Rock Art Discovery',
                'type' => 'Rock Art',
                'artist' => 'Unknown',
                'period' => 'c. 3000 BCE',
                'description' => 'Recently discovered rock art showing animal figures.',
                'location' => 'Central Australia',
                'image_url' => 'https://picsum.photos/seed/3/400/300',
                'condition_note' => 'Good condition, needs documentation.',
                'status' => 'pending',
                'submitted_by' => 2,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ],
        CATEGORIES_FILE => [
            [
                'id' => 1,
                'name' => 'Rock Art',
                'description' => 'Ancient paintings and engravings on rock surfaces'
            ],
            [
                'id' => 2,
                'name' => 'Pottery',
                'description' => 'Traditional ceramic vessels and decorative items'
            ],
            [
                'id' => 3,
                'name' => 'Textile',
                'description' => 'Woven materials, clothing, and fabric art'
            ]
        ]
    ];

    foreach ($defaultData as $file => $data) {
        if (!file_exists($file)) {
            file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
        }
    }
}

// Initialize data files
initializeDataFiles();

// Helper functions
function corsHeaders() {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        exit(0);
    }
}

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function getJsonData($file) {
    if (!file_exists($file)) {
        return [];
    }
    $data = file_get_contents($file);
    return json_decode($data, true) ?: [];
}

function saveJsonData($file, $data) {
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT)) !== false;
}

function getNextId($data) {
    if (empty($data)) return 1;
    $ids = array_column($data, 'id');
    return max($ids) + 1;
}
?>