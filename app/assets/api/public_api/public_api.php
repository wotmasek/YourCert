<?php

namespace Api\PublicAPI; 

require_once __DIR__ . '/../../validation_functions.php';

class PublicAPI {
    protected $conn;
    protected $messages;

    public function __construct(\PDO $conn) {
        $this->conn = $conn;
        $this->messages = json_decode(file_get_contents(__DIR__ . '/messages.json'), true);
    }

    public function getCourses($limit = 50, $offset = 0, $filters = []) {
        if (!validateInt($limit, 0) || !validateInt($offset, 0)) {
            return ['success' => false, 'error' => $this->messages['invalid_limit_offset']];
        }
        
        $sql = "SELECT id, title, description, course_author, created_at FROM courses";
        $where = [];
        $params = [];
        
        if (!empty($filters['title'])) {
            $where[] = "title LIKE :title";
            $params[':title'] = "%" . $filters['title'] . "%";
        }
        if (!empty($filters['description'])) {
            $where[] = "description LIKE :description";
            $params[':description'] = "%" . $filters['description'] . "%";
        }
        if (!empty($filters['course_author'])) {
            $where[] = "course_author LIKE :course_author";
            $params[':course_author'] = "%" . $filters['course_author'] . "%";
        }
        if (!empty($filters['created_at'])) {
            $where[] = "DATE(created_at) = :created_at";
            $params[':created_at'] = $filters['created_at'];
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $courses = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return ['success' => true, 'courses' => $courses];
    }      

    public function getCourse($course_id) {
        if (!validateInt($course_id, 1)) {
            return ['success' => false, 'error' => $this->messages['invalid_course_id']];
        }
        $sql = "SELECT * FROM courses WHERE id = :course_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':course_id', $course_id);
        $stmt->execute();
        $course = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($course) {
            return ['success' => true, 'course' => $course];
        }
        return ['success' => false, 'error' => $this->messages['course_not_found']];
    }

    public function getInstitution($institution_id = 1) {
        if (!validateInt($institution_id, 1)) {
            return ['success' => false, 'error' => $this->messages['invalid_institution_id']];
        }
        $sql = "SELECT * FROM institutions WHERE id = :institution_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':institution_id', $institution_id);
        $stmt->execute();
        $institution = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($institution) {
            return ['success' => true, 'institution' => $institution];
        }
        return ['success' => false, 'error' => $this->messages['institution_not_found']];
    }

    public function getInstitutionSEO(int $institution_id): array {
        if (!validateInt($institution_id, 1)) {
            return ['success' => false, 'error' => $this->messages['invalid_institution_id']];
        }

        $sql = "SELECT meta_title, meta_description, meta_keywords,
                       og_title, og_description, og_image_url, og_type,
                       canonical_url, robots_index, robots_follow, json_ld
                FROM institutions_seo
                WHERE institution_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $institution_id]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        return ['success' => true, 'data' => $data ?: []];
    }

    public function getPages(int $limit = 50, int $offset = 0, array $filters = []): array {
        if (!validateInt($limit, 0) || !validateInt($offset, 0)) {
            return ['success'=>false, 'error'=>$this->messages['invalid_limit_offset']];
        }
    
        $sql    = "SELECT id, name, title, created_at, updated_at FROM cms_pages";
        $where  = [];
        $params = [];
    
        if (!empty($filters['name'])) {
            $where[]           = "name = :name";
            $params[':name']   = $filters['name'];
        }
        if (!empty($filters['title'])) {
            $where[]            = "title LIKE :title";
            $params[':title']   = "%{$filters['title']}%";
        }
        if (!empty($filters['created_at'])) {
            $where[]              = "DATE(created_at) = :created_at";
            $params[':created_at']= $filters['created_at'];
        }
        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
    
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
    
        $pages = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return ['success'=>true, 'pages'=>$pages];
    }    

    public function getPage($identifier): array {
        if (is_numeric($identifier) && validateInt((int)$identifier, 1)) {
            $where     = 'id = :val';
            $paramName = ':val';
            $paramVal  = (int)$identifier;
        } elseif (is_string($identifier) && preg_match('/^[a-z0-9_-]+$/', $identifier)) {
            $where     = 'name = :val';
            $paramName = ':val';
            $paramVal  = $identifier;
        } else {
            return ['success' => false, 'error' => 'Invalid page identifier'];
        }
    
        $sql  = "SELECT id, name, title, content, created_at, updated_at
                 FROM cms_pages
                 WHERE $where
                 LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$paramName => $paramVal]);
        $page = $stmt->fetch(\PDO::FETCH_ASSOC);
    
        if (!$page) {
            return ['success' => false, 'error' => 'Page not found'];
        }
        return ['success' => true, 'data' => $page];
    }

    public function getPosts(int $limit = 50, int $offset = 0, array $filters = []): array {
        if (!validateInt($limit,0) || !validateInt($offset,0)) {
            return ['success'=>false,'error'=>$this->messages['invalid_limit_offset']];
        }

        $sql    = "SELECT id, title, slug, content, created_at, updated_at FROM cms_posts";
        $where  = [];
        $params = [];

        if (!empty($filters['title'])) {
            $where[]          = "title LIKE :title";
            $params[':title'] = "%{$filters['title']}%";
        }
        if (!empty($filters['slug'])) {
            $where[]        = "slug = :slug";
            $params[':slug'] = $filters['slug'];
        }
        if (!empty($filters['created_at'])) {
            $where[]            = "DATE(created_at) = :created_at";
            $params[':created_at'] = $filters['created_at'];
        }

        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $limit,  \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        $posts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if ($posts) {
            $ids = array_column($posts, 'id');
            $in  = implode(',', array_fill(0, count($ids), '?'));

            $imgSql  = "SELECT post_id, file_path, caption, position
                        FROM cms_post_images
                        WHERE post_id IN ($in)
                        ORDER BY position ASC";
            $imgStmt = $this->conn->prepare($imgSql);
            foreach ($ids as $i => $pid) {
                $imgStmt->bindValue($i+1, $pid, \PDO::PARAM_INT);
            }
            $imgStmt->execute();
            $allImgs = $imgStmt->fetchAll(\PDO::FETCH_ASSOC);

            $grouped = [];
            foreach ($allImgs as $img) {
                $grouped[$img['post_id']][] = [
                    'file_path' => $img['file_path'],
                    'caption'   => $img['caption'],
                    'position'  => $img['position'],
                ];
            }
            foreach ($posts as &$p) {
                $p['images'] = $grouped[$p['id']] ?? [];
            }
        }

        return ['success'=>true, 'data'=>$posts];
    }

    public function getPost(int $id): array {
        if (!validateInt($id,1)) {
            return ['success'=>false,'error'=>$this->messages['invalid_id']];
        }
        $stmt = $this->conn->prepare("SELECT * FROM cms_posts WHERE id = :id");
        $stmt->execute([':id'=>$id]);
        $post = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$post) {
            return ['success'=>false,'error'=>'Post not found'];
        }

        $imgStmt = $this->conn->prepare(
            "SELECT file_path, caption, position 
             FROM cms_post_images 
             WHERE post_id = :post_id 
             ORDER BY position ASC"
        );
        $imgStmt->execute([':post_id'=>$id]);
        $post['images'] = $imgStmt->fetchAll(\PDO::FETCH_ASSOC);

        return ['success'=>true, 'data'=>$post];
    }

    public function getCertificates($limit = 50, $offset = 0, $filters = []) {
        if (!validateInt($limit, 0) || !validateInt($offset, 0)) {
            return ['success' => false, 'error' => $this->messages['invalid_limit_offset']];
        }
        
        $sql = "SELECT id, course_id, title, description, certificate_image_path, valid_until, created_at FROM certificates";
        $where = [];
        $params = [];
        
        if (!empty($filters['certificate_title'])) {
            $where[] = "title LIKE :title";
            $params[':title'] = "%" . $filters['certificate_title'] . "%";
        }
        
        if (!empty($filters['course_title'])) {
            $where[] = "course_id IN (SELECT id FROM courses WHERE title LIKE :course_title)";
            $params[':course_title'] = "%" . $filters['course_title'] . "%";
        }
        
        if (!empty($filters['description'])) {
            $where[] = "description LIKE :description";
            $params[':description'] = "%" . $filters['description'] . "%";
        }
        
        if (!empty($filters['valid_until'])) {
            $where[] = "DATE(valid_until) = :valid_until";
            $params[':valid_until'] = $filters['valid_until'];
        }
        
        if (!empty($filters['created_at'])) {
            $where[] = "DATE(created_at) = :created_at";
            $params[':created_at'] = $filters['created_at'];
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $certificates = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return ['success' => true, 'certificates' => $certificates];
    }    

    public function getCertificate($certificate_id) {
        if (!validateInt($certificate_id, 1)) {
            return ['success' => false, 'error' => $this->messages['invalid_certificate_id']];
        }
        $sql = "SELECT * FROM certificates WHERE id = :certificate_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':certificate_id', $certificate_id);
        $stmt->execute();
        $certificate = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($certificate) {
            return ['success' => true, 'certificate' => $certificate];
        }
        return ['success' => false, 'error' => $this->messages['certificate_not_found']];
    }

    public function getUserCertificateByToken($token) {
        if (!is_string($token) || empty($token) || strlen($token) !== 32) {
            return ['success' => false, 'error' => 'Invalid token'];
        }
        
        $sql = "SELECT 
                    uc.id AS user_certificate_id,
                    uc.user_id,
                    uc.certificate_id,
                    uc.valid_until,
                    uc.awarded_at,
                    uc.personalized_certificate_image_path,
                    c.title AS certificate_title, 
                    c.description AS certificate_description, 
                    c.certificate_image_path AS certificate_image_path, 
                    c.valid_until AS certificate_valid_until, 
                    u.email, 
                    u.first_name, 
                    u.last_name,
                    u.birth_date
                FROM user_certificates uc 
                LEFT JOIN certificates c ON uc.certificate_id = c.id 
                LEFT JOIN users u ON uc.user_id = u.id
                WHERE uc.token = :token
                LIMIT 1";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':token', $token, \PDO::PARAM_STR);
        $stmt->execute();
        $certificate = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$certificate) {
            return ['success' => false, 'error' => 'Certificate not found'];
        }
        
        return ['success' => true, 'certificate' => $certificate];
    }    
}
?>
