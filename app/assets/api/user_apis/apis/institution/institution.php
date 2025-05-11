<?php
namespace Api\UserAPI;

use Api\UserAPI; 

require_once __DIR__ . '/../../user_api.php';

class InstitutionMenagment extends UserAPI {
    public function __construct($user_id, \PDO $conn) {
        parent::__construct($user_id, $conn);
    }

    public function updateInstitution($institution_id, array $fields) {
        if (!$this->checkRateLimitAction('updateInstitution')) {
            return ['success' => false, 'error' => $this->messages['rate_limit_exceeded_updateInstitution']];
        }
        $permCheck = $this->checkUserPermission(__FUNCTION__);
        if ($permCheck !== true) {
            return $permCheck;
        }
        if (!validateInt($institution_id, 1)) {
            return ['success' => false, 'error' => $this->messages['invalid_institution_id']];
        }
    
        $allowed = [
            'name', 'description', 'contact_email', 'phone_number',
            'website_url', 'profile_image_url', 'banner_url', 'logo_url',
            'favicon_url'  
        ];
        $setParts = [];
        $params = [':institution_id' => $institution_id];
    
        foreach ($fields as $key => $value) {
            if (!in_array($key, $allowed)) {
                continue;
            }
    
            if ($key === 'name' && (!is_string($value) || !validateText($value, 255))) {
                return ['success' => false, 'error' => $this->messages['invalid_name']];
            }
            if ($key === 'description' && (!is_string($value) || !validateText($value, 1000))) {
                return ['success' => false, 'error' => $this->messages['invalid_description']];
            }
            if ($key === 'contact_email' && !validateEmail($value)) {
                return ['success' => false, 'error' => $this->messages['invalid_contact_email']];
            }
            if ($key === 'phone_number' && !validatePhoneNumber($value, 7, 15)) {
                return ['success' => false, 'error' => $this->messages['invalid_phone_number']];
            }
            if (in_array($key, ['profile_image_url','banner_url','logo_url','favicon_url'])) {
                if (!is_string($value) || mb_strlen($value) > 255) {
                    return ['success' => false, 'error' => $this->messages["invalid_{$key}"] ?? 'Invalid value'];
                }
            }
    
            $setParts[]      = "`$key` = :$key";
            $params[":$key"] = $value;
        }
    
        if (empty($setParts)) {
            return ['success' => false, 'error' => $this->messages['no_valid_fields_provided']];
        }
    
        $sql  = "UPDATE institutions SET " . implode(', ', $setParts) . " WHERE id = :institution_id";
        $stmt = $this->conn->prepare($sql);
        if ($stmt->execute($params)) {
            $updatedFields = implode(', ', array_keys($fields));
            $this->logAction('updateInstitution', 'institution', $institution_id, null, "Updated fields: $updatedFields");
            return ['success' => true, 'message' => $this->messages['institution_updated'] ?? 'Institution updated'];
        }
    
        return ['success' => false, 'error' => $stmt->errorInfo()];
    }
    
    public function updateInstitutionField($institution_id, $field, $value) {
        if (!$this->checkRateLimitAction('updateInstitutionField')) {
            return ['success' => false, 'error' => $this->messages['rate_limit_exceeded_updateInstitutionField']];
        }
        $permCheck = $this->checkUserPermission(__FUNCTION__);
        if ($permCheck !== true) {
            return $permCheck;
        }
        if (!validateInt($institution_id, 1)) {
            return ['success' => false, 'error' => $this->messages['invalid_institution_id']];
        }
        if ($field === 'favicon_url') {
            if (!is_string($value) || !validateUrl($value) || mb_strlen($value) > 255) {
                return ['success' => false, 'error' => $this->messages['invalid_favicon_url']];
            }
        }
        $result = $this->updateInstitution($institution_id, [$field => $value]);
        if ($result['success']) {
            $this->logAction('updateInstitutionField', 'institution', $institution_id, null, "Field $field updated to " . $value);
        }
        return $result;
    }
    

    public function updateInstitutionSEO(int $institution_id, array $fields): array {
        if (!$this->checkRateLimitAction('updateInstitutionSEO')) {
            return ['success' => false, 'error' => $this->messages['rate_limit_exceeded_updateInstitutionSEO']];
        }
        $permission = $this->checkUserPermission(__FUNCTION__);
        if ($permission !== true) {
            return $permission;
        }
        if (!validateInt($institution_id, 1)) {
            return ['success' => false, 'error' => $this->messages['invalid_institution_id']];
        }

        $allowed = [
            'meta_title', 'meta_description', 'meta_keywords',
            'og_title', 'og_description', 'og_image_url', 'og_type',
            'canonical_url', 'robots_index', 'robots_follow', 'json_ld'
        ];

        $setClauses = [];
        $params = [':institution_id' => $institution_id];

        foreach ($fields as $key => $value) {
            if (!in_array($key, $allowed, true)) {
                continue;
            }
            if (in_array($key, ['og_image_url', 'canonical_url'], true) && !filter_var($value, FILTER_VALIDATE_URL)) {
                return ['success' => false, 'error' => "$key must be a valid URL"];
            }
            if (in_array($key, ['robots_index', 'robots_follow'], true) && !in_array($value, [0,1], true)) {
                return ['success' => false, 'error' => "$key must be 0 or 1"];
            }
            $setClauses[] = "`$key` = :$key";
            $params[":$key"] = $value;
        }

        if (empty($setClauses)) {
            return ['success' => false, 'error' => $this->messages['no_valid_fields_provided']];
        }

        $columns = implode(',', array_keys($fields));
        $placeholders = implode(',', array_map(fn($k) => ":$k", array_keys($fields)));
        $updates = implode(', ', $setClauses);

        $sql = "INSERT INTO institutions_seo (institution_id, $columns)
                VALUES (:institution_id, $placeholders)
                ON DUPLICATE KEY UPDATE $updates";

        $stmt = $this->conn->prepare($sql);
        if ($stmt->execute($params)) {
            $this->logAction('updateInstitutionSEO', 'institution', $institution_id, null,
                'Updated SEO fields: ' . implode(', ', array_keys($fields))
            );
            return ['success' => true, 'message' => $this->messages['institution_seo_updated'] ?? 'SEO updated'];
        }

        return ['success' => false, 'error' => $stmt->errorInfo()];
    }

    public function updateInstitutionSEOField(int $institution_id, string $field, $value): array {
        if (!$this->checkRateLimitAction('updateInstitutionSEOField')) {
            return ['success' => false, 'error' => $this->messages['rate_limit_exceeded_updateInstitutionSEOField']];
        }
        $permission = $this->checkUserPermission(__FUNCTION__);
        if ($permission !== true) {
            return $permission;
        }
        if (!validateInt($institution_id, 1)) {
            return ['success' => false, 'error' => $this->messages['invalid_institution_id']];
        }

        return $this->updateInstitutionSEO($institution_id, [$field => $value]);
    }
    
    public function createPage(array $fields): array {
        if (!$this->checkRateLimitAction('createPage')) {
            return ['success'=>false,'error'=>$this->messages['rate_limit_exceeded_createPage']];
        }
        if (($perm=$this->checkUserPermission(__FUNCTION__))!==true) {
            return $perm;
        }
        if (empty($fields['title']) || empty($fields['content'])) {
            return ['success'=>false,'error'=>'Title and content required'];
        }
        $sql = "INSERT INTO cms_pages (title,content) VALUES (:title,:content)";
        $stmt= $this->conn->prepare($sql);
        if ($stmt->execute([
            ':title'=>$fields['title'],
            ':content'=>$fields['content']
        ])) {
            $id = $this->conn->lastInsertId();
            $this->logAction('createPage','cms_page',$id,null,"Created page {$fields['title']}");
            return ['success'=>true,'data'=>['id'=>$id]];
        }
        return ['success'=>false,'error'=>$stmt->errorInfo()];
    }

    public function updatePage(int $id, array $fields): array { 
        if (!$this->checkRateLimitAction('updatePage')) {
            return ['success'=>false,'error'=>$this->messages['rate_limit_exceeded_updatePage']];
        }
        if (($perm=$this->checkUserPermission(__FUNCTION__))!==true) {
            return $perm;
        }
        if (!validateInt($id,1)) {
            return ['success'=>false,'error'=>$this->messages['invalid_id']];
        }
        $allowed = ['title','content'];
        $set = []; $params=[':id'=>$id];
        foreach ($fields as $k=>$v) {
            if (in_array($k,$allowed,true)) {
                $set[]="`$k`=:$k"; $params[":$k"]=$v;
            }
        }
        if (!$set) {
            return ['success'=>false,'error'=>'No valid fields'];
        }
        $sql="UPDATE cms_pages SET ".implode(',',$set)." WHERE id=:id";
        $stmt=$this->conn->prepare($sql);
        if ($stmt->execute($params)) {
            $this->logAction('updatePage','cms_page',$id,null,"Updated page fields: ".implode(',',array_keys($fields)));
            return ['success'=>true];
        }
        return ['success'=>false,'error'=>$stmt->errorInfo()];
    }

    public function deletePage(int $id): array {
        if (!$this->checkRateLimitAction('deletePage')) {
            return ['success'=>false,'error'=>$this->messages['rate_limit_exceeded_deletePage']];
        }
        if (($perm=$this->checkUserPermission(__FUNCTION__))!==true) {
            return $perm;
        }
        if (!validateInt($id,1)) {
            return ['success'=>false,'error'=>$this->messages['invalid_id']];
        }
        $stmt = $this->conn->prepare("DELETE FROM cms_pages WHERE id=:id");
        if ($stmt->execute([':id'=>$id])) {
            $this->logAction('deletePage','cms_page',$id,null,"Deleted page");
            return ['success'=>true];
        }
        return ['success'=>false,'error'=>$stmt->errorInfo()];
    }

    public function createPost(array $fields): array {
        if (!$this->checkRateLimitAction('createPost')) {
            return ['success'=>false, 'error'=>$this->messages['rate_limit_exceeded_createPost']];
        }
        if (($perm = $this->checkUserPermission(__FUNCTION__)) !== true) {
            return $perm;
        }
        foreach (['title','slug','content'] as $req) {
            if (empty($fields[$req])) {
                return ['success'=>false, 'error'=>"$req required"];
            }
        }

        try {
            $this->conn->beginTransaction();

            $sql  = "INSERT INTO cms_posts (title,slug,content)
                     VALUES (:title,:slug,:content)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':title'   => $fields['title'],
                ':slug'    => $fields['slug'],
                ':content' => $fields['content'],
            ]);
            $postId = (int)$this->conn->lastInsertId();

            if (!empty($fields['images']) && is_array($fields['images'])) {
                $imgSql  = "INSERT INTO cms_post_images
                              (post_id,file_path,caption,position)
                            VALUES (:post_id,:file_path,:caption,:position)";
                $imgStmt = $this->conn->prepare($imgSql);

                foreach ($fields['images'] as $img) {
                    if (empty($img['file_path'])) {
                        throw new \Exception("Each image requires a file_path");
                    }
                    $imgStmt->execute([
                        ':post_id'   => $postId,
                        ':file_path' => $img['file_path'],
                        ':caption'   => $img['caption']  ?? null,
                        ':position'  => $img['position'] ?? 0,
                    ]);
                }
            }

            $this->conn->commit();
            $this->logAction('createPost','cms_post',$postId,null,"Created post {$fields['title']}");
            return ['success'=>true,'data'=>['id'=>$postId]];

        } catch (\Exception $e) {
            $this->conn->rollBack();
            return ['success'=>false,'error'=>$e->getMessage()];
        }
    }

    public function updatePost(int $id, array $fields): array {
        if (!$this->checkRateLimitAction('updatePost')) {
            return ['success'=>false, 'error'=>$this->messages['rate_limit_exceeded_updatePost']];
        }
        if (($perm = $this->checkUserPermission(__FUNCTION__)) !== true) {
            return $perm;
        }
        if (!validateInt($id,1)) {
            return ['success'=>false, 'error'=>$this->messages['invalid_id']];
        }
    
        try {
            $original = $this->conn->prepare("SELECT title, slug, content FROM cms_posts WHERE id = :id");
            $original->execute([':id' => $id]);
            $oldData = $original->fetch(\PDO::FETCH_ASSOC);
    
            $this->conn->beginTransaction();
    
            $allowed = ['title','slug','content'];
            $set = []; $params = [':id'=>$id];
            foreach ($fields as $k => $v) {
                if (in_array($k, $allowed, true)) {
                    $set[] = "`$k` = :$k";
                    $params[":$k"] = $v;
                }
            }
            if ($set) {
                $sql = "UPDATE cms_posts SET " . implode(',', $set) . " WHERE id = :id";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute($params);
            }
    
            if (!empty($fields['remove_images']) && is_array($fields['remove_images'])) {
                $in  = implode(',', array_fill(0, count($fields['remove_images']), '?'));
                $stmt = $this->conn->prepare("DELETE FROM cms_post_images WHERE id IN ($in)");
                $stmt->execute($fields['remove_images']);
            }
    
            if (array_key_exists('images', $fields)) {
                $del = $this->conn->prepare("DELETE FROM cms_post_images WHERE post_id = :post_id");
                $del->execute([':post_id' => $id]);
    
                if (is_array($fields['images'])) {
                    $imgSql  = "INSERT INTO cms_post_images
                                  (post_id,file_path,caption,position)
                                VALUES (:post_id,:file_path,:caption,:position)";
                    $imgStmt = $this->conn->prepare($imgSql);
    
                    foreach ($fields['images'] as $img) {
                        if (empty($img['file_path'])) {
                            throw new \Exception("Each image requires a file_path");
                        }
                        $imgStmt->execute([
                            ':post_id'   => $id,
                            ':file_path' => $img['file_path'],
                            ':caption'   => $img['caption']  ?? null,
                            ':position'  => $img['position'] ?? 0,
                        ]);
                    }
                }
            }
    
            $this->conn->commit();
    
            $changes = [];
            foreach ($allowed as $field) {
                if (array_key_exists($field, $fields) && isset($oldData[$field]) && $fields[$field] !== $oldData[$field]) {
                    $changes[] = "$field: \"{$oldData[$field]}\" → \"{$fields[$field]}\"";
                }
            }
    
            $logMessage = $changes ? "Modified fields:\n" . implode("\n", $changes) : "No content fields changed.";
            if (!empty($fields['remove_images'])) {
                $logMessage .= "\nRemoved images: " . implode(',', $fields['remove_images']);
            }
            if (array_key_exists('images', $fields)) {
                $logMessage .= "\nUpdated images.";
            }
    
            $this->logAction('updatePost','cms_post',$id,null, $logMessage);
    
            return ['success'=>true];
    
        } catch (\Exception $e) {
            $this->conn->rollBack();
            return ['success'=>false,'error'=>$e->getMessage()];
        }
    }    

    public function deletePost(int $id): array {
        if (!$this->checkRateLimitAction('deletePost')) {
            return ['success'=>false,'error'=>$this->messages['rate_limit_exceeded_deletePost']];
        }
        if (($perm=$this->checkUserPermission(__FUNCTION__))!==true) {
            return $perm;
        }
        if (!validateInt($id,1)) {
            return ['success'=>false,'error'=>$this->messages['invalid_id']];
        }
        $stmt=$this->conn->prepare("DELETE FROM cms_posts WHERE id=:id");
        if ($stmt->execute([':id'=>$id])) {
            $this->logAction('deletePost','cms_post',$id,null,"Deleted post");
            return ['success'=>true];
        }
        return ['success'=>false,'error'=>$stmt->errorInfo()];
    }
}