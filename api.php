<?php
// api.php - Main API endpoints for artworks, submissions, and admin

require_once 'config.php';
require_once 'database.php';
require_once 'auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['action'] ?? '';

switch ($method . ' ' . $path) {
    // Public artwork endpoints
    case 'GET artworks':
        handleGetArtworks();
        break;
    
    case 'GET artwork':
        handleGetArtwork();
        break;
    
    case 'GET search':
        handleSearchArtworks();
        break;
    
    case 'GET categories':
        handleGetCategories();
        break;
    
    // Authenticated submission endpoints
    case 'POST submissions':
        handleCreateSubmission();
        break;
    
    case 'GET submissions':
        handleGetSubmissions();
        break;
    
    // Admin endpoints
    case 'GET admin/stats':
        handleGetStats();
        break;
    
    case 'GET admin/users':
        handleGetAllUsers();
        break;
    
    case 'PUT admin/user-role':
        handleUpdateUserRole();
        break;
    
    case 'PUT admin/submission':
        handleUpdateSubmissionStatus();
        break;
    
    case 'POST admin/category':
        handleCreateCategory();
        break;
    
    case 'DELETE admin/category':
        handleDeleteCategory();
        break;
    
    default:
        jsonResponse(['error' => 'Endpoint not found'], 404);
}

// Public endpoints
function handleGetArtworks() {
    $page = intval($_GET['page'] ?? 1);
    $limit = intval($_GET['limit'] ?? 9);
    $offset = ($page - 1) * $limit;
    
    $artworks = Database::getAllArtworks('approved');
    $total = count($artworks);
    
    $paginatedArtworks = array_slice($artworks, $offset, $limit);
    
    jsonResponse([
        'artworks' => $paginatedArtworks,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'total_items' => $total,
            'per_page' => $limit
        ]
    ]);
}

function handleGetArtwork() {
    $id = intval($_GET['id'] ?? 0);
    
    if (!$id) {
        jsonResponse(['error' => 'Artwork ID is required'], 400);
    }
    
    $artwork = Database::getArtworkById($id);
    
    if (!$artwork) {
        jsonResponse(['error' => 'Artwork not found'], 404);
    }
    
    // Get similar artworks (same type)
    $similarArtworks = array_filter(
        Database::getAllArtworks('approved'),
        function($item) use ($artwork) {
            return $item['id'] != $artwork['id'] && $item['type'] === $artwork['type'];
        }
    );
    
    // Limit to 3 similar artworks
    $similarArtworks = array_slice(array_values($similarArtworks), 0, 3);
    
    jsonResponse([
        'artwork' => $artwork,
        'similar' => $similarArtworks
    ]);
}

function handleSearchArtworks() {
    $query = $_GET['q'] ?? '';
    $type = $_GET['type'] ?? null;
    $period = $_GET['period'] ?? null;
    
    $results = Database::searchArtworks($query, $type, $period);
    
    jsonResponse(['artworks' => $results]);
}

function handleGetCategories() {
    $categories = Database::getAllCategories();
    jsonResponse(['categories' => $categories]);
}

// Authenticated endpoints
function handleCreateSubmission() {
    $userId = requireAuth();
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $errors = [];
    if (empty($input['title'])) $errors[] = 'Title is required';
    if (empty($input['type'])) $errors[] = 'Art type is required';
    if (empty($input['description'])) $errors[] = 'Description is required';
    
    if (!empty($errors)) {
        jsonResponse(['error' => implode(', ', $errors)], 400);
    }
    
    $result = Database::createSubmission($input, $userId);
    
    if (isset($result['error'])) {
        jsonResponse(['error' => $result['error']], 400);
    }
    
    jsonResponse([
        'message' => 'Submission created successfully',
        'submission' => $result
    ], 201);
}

function handleGetSubmissions() {
    $userId = requireAuth();
    $status = $_GET['status'] ?? null;
    
    // Regular users can only see their own submissions
    if ($_SESSION['role'] !== 'admin') {
        $submissions = Database::getAllSubmissions($status);
        $submissions = array_filter($submissions, function($submission) use ($userId) {
            return $submission['submitted_by'] == $userId;
        });
        $submissions = array_values($submissions);
    } else {
        $submissions = Database::getAllSubmissions($status);
    }
    
    jsonResponse(['submissions' => $submissions]);
}

// Admin endpoints
function handleGetStats() {
    requireAdmin();
    $stats = Database::getStats();
    jsonResponse(['stats' => $stats]);
}

function handleGetAllUsers() {
    requireAdmin();
    $users = Database::getAllUsers();
    jsonResponse(['users' => $users]);
}

function handleUpdateUserRole() {
    requireAdmin();
    $input = json_decode(file_get_contents('php://input'), true);
    
    $userId = intval($input['user_id'] ?? 0);
    $role = $input['role'] ?? '';
    
    if (!$userId || !in_array($role, ['general', 'admin', 'researcher'])) {
        jsonResponse(['error' => 'Invalid user ID or role'], 400);
    }
    
    $result = Database::updateUserRole($userId, $role);
    
    if (!$result) {
        jsonResponse(['error' => 'Failed to update user role'], 400);
    }
    
    jsonResponse([
        'message' => 'User role updated successfully',
        'user' => $result
    ]);
}

function handleUpdateSubmissionStatus() {
    requireAdmin();
    $input = json_decode(file_get_contents('php://input'), true);
    
    $submissionId = intval($input['submission_id'] ?? 0);
    $status = $input['status'] ?? '';
    
    if (!$submissionId || !in_array($status, ['pending', 'approved', 'rejected'])) {
        jsonResponse(['error' => 'Invalid submission ID or status'], 400);
    }
    
    $result = Database::updateSubmissionStatus($submissionId, $status);
    
    if (!$result) {
        jsonResponse(['error' => 'Failed to update submission status'], 400);
    }
    
    jsonResponse([
        'message' => 'Submission status updated successfully',
        'submission' => $result
    ]);
}

function handleCreateCategory() {
    requireAdmin();
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['name'])) {
        jsonResponse(['error' => 'Category name is required'], 400);
    }
    
    $result = Database::createCategory($input);
    
    if (isset($result['error'])) {
        jsonResponse(['error' => $result['error']], 400);
    }
    
    jsonResponse([
        'message' => 'Category created successfully',
        'category' => $result
    ], 201);
}

function handleDeleteCategory() {
    requireAdmin();
    $id = intval($_GET['id'] ?? 0);
    
    if (!$id) {
        jsonResponse(['error' => 'Category ID is required'], 400);
    }
    
    if (Database::deleteCategory($id)) {
        jsonResponse(['message' => 'Category deleted successfully']);
    } else {
        jsonResponse(['error' => 'Failed to delete category'], 400);
    }
}
?>