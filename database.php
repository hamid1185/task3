<?php
// database.php - JSON Database operations class

require_once 'config.php';

class Database {
    
    // User operations
    public static function createUser($data) {
        $users = getJsonData(USERS_FILE);
        
        // Check if username or email exists
        foreach ($users as $user) {
            if ($user['username'] === $data['username'] || $user['email'] === $data['email']) {
                return ['error' => 'Username or email already exists'];
            }
        }
        
        $newUser = [
            'id' => getNextId($users),
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => $data['role'] ?? 'general',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $users[] = $newUser;
        
        if (saveJsonData(USERS_FILE, $users)) {
            unset($newUser['password']);
            return $newUser;
        }
        
        return ['error' => 'Failed to create user'];
    }
    
    public static function getUserByCredentials($username, $password) {
        $users = getJsonData(USERS_FILE);
        
        foreach ($users as $user) {
            if (($user['username'] === $username || $user['email'] === $username) && 
                password_verify($password, $user['password'])) {
                unset($user['password']);
                return $user;
            }
        }
        
        return null;
    }
    
    public static function getUserById($id) {
        $users = getJsonData(USERS_FILE);
        
        foreach ($users as $user) {
            if ($user['id'] == $id) {
                unset($user['password']);
                return $user;
            }
        }
        
        return null;
    }
    
    public static function getAllUsers() {
        $users = getJsonData(USERS_FILE);
        foreach ($users as &$user) {
            unset($user['password']);
        }
        return $users;
    }
    
    public static function updateUserRole($id, $role) {
        $users = getJsonData(USERS_FILE);
        
        foreach ($users as &$user) {
            if ($user['id'] == $id) {
                $user['role'] = $role;
                if (saveJsonData(USERS_FILE, $users)) {
                    unset($user['password']);
                    return $user;
                }
                break;
            }
        }
        
        return null;
    }
    
    // Artwork operations
    public static function getAllArtworks($status = 'approved') {
        $artworks = getJsonData(ARTWORKS_FILE);
        
        if ($status) {
            $artworks = array_filter($artworks, function($artwork) use ($status) {
                return $artwork['status'] === $status;
            });
        }
        
        return array_values($artworks);
    }
    
    public static function getArtworkById($id) {
        $artworks = getJsonData(ARTWORKS_FILE);
        
        foreach ($artworks as $artwork) {
            if ($artwork['id'] == $id) {
                return $artwork;
            }
        }
        
        return null;
    }
    
    public static function searchArtworks($query, $type = null, $period = null) {
        $artworks = self::getAllArtworks('approved');
        
        if ($query) {
            $artworks = array_filter($artworks, function($artwork) use ($query) {
                return stripos($artwork['title'], $query) !== false ||
                       stripos($artwork['description'], $query) !== false ||
                       stripos($artwork['artist'], $query) !== false;
            });
        }
        
        if ($type) {
            $artworks = array_filter($artworks, function($artwork) use ($type) {
                return $artwork['type'] === $type;
            });
        }
        
        if ($period) {
            $artworks = array_filter($artworks, function($artwork) use ($period) {
                return stripos($artwork['period'], $period) !== false;
            });
        }
        
        return array_values($artworks);
    }
    
    // Submission operations
    public static function createSubmission($data, $userId) {
        $submissions = getJsonData(SUBMISSIONS_FILE);
        
        $newSubmission = [
            'id' => getNextId($submissions),
            'title' => $data['title'],
            'type' => $data['type'],
            'artist' => $data['artist'] ?? 'Unknown',
            'period' => $data['period'] ?? '',
            'description' => $data['description'],
            'location' => $data['location'] ?? '',
            'image_url' => $data['image_url'] ?? '',
            'condition_note' => $data['condition_note'] ?? '',
            'status' => 'pending',
            'submitted_by' => $userId,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $submissions[] = $newSubmission;
        
        if (saveJsonData(SUBMISSIONS_FILE, $submissions)) {
            return $newSubmission;
        }
        
        return ['error' => 'Failed to create submission'];
    }
    
    public static function getAllSubmissions($status = null) {
        $submissions = getJsonData(SUBMISSIONS_FILE);
        
        if ($status) {
            $submissions = array_filter($submissions, function($submission) use ($status) {
                return $submission['status'] === $status;
            });
        }
        
        return array_values($submissions);
    }
    
    public static function updateSubmissionStatus($id, $status) {
        $submissions = getJsonData(SUBMISSIONS_FILE);
        
        foreach ($submissions as &$submission) {
            if ($submission['id'] == $id) {
                $submission['status'] = $status;
                
                if ($status === 'approved') {
                    // Move to artworks
                    $artworks = getJsonData(ARTWORKS_FILE);
                    $newArtwork = $submission;
                    $newArtwork['id'] = getNextId($artworks);
                    $artworks[] = $newArtwork;
                    saveJsonData(ARTWORKS_FILE, $artworks);
                }
                
                saveJsonData(SUBMISSIONS_FILE, $submissions);
                return $submission;
            }
        }
        
        return null;
    }
    
    // Category operations
    public static function getAllCategories() {
        return getJsonData(CATEGORIES_FILE);
    }
    
    public static function createCategory($data) {
        $categories = getJsonData(CATEGORIES_FILE);
        
        $newCategory = [
            'id' => getNextId($categories),
            'name' => $data['name'],
            'description' => $data['description'] ?? ''
        ];
        
        $categories[] = $newCategory;
        
        if (saveJsonData(CATEGORIES_FILE, $categories)) {
            return $newCategory;
        }
        
        return ['error' => 'Failed to create category'];
    }
    
    public static function deleteCategory($id) {
        $categories = getJsonData(CATEGORIES_FILE);
        
        foreach ($categories as $key => $category) {
            if ($category['id'] == $id) {
                unset($categories[$key]);
                return saveJsonData(CATEGORIES_FILE, array_values($categories));
            }
        }
        
        return false;
    }
    
    // Statistics for admin
    public static function getStats() {
        return [
            'pending_submissions' => count(self::getAllSubmissions('pending')),
            'total_users' => count(self::getAllUsers()),
            'total_artworks' => count(self::getAllArtworks('approved'))
        ];
    }
}
?>