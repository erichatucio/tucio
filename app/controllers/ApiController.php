<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class ApiController extends Controller
{
    private $api;

    public function before_action()
    {
        $this->db = $this->call->database();
        $this->api = $this->call->library('api');
    }

    public function login()
    {
        $this->api->rate_limit('api-login-' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 10, 60);
        $body = $this->request->json();
        if (!is_array($body)) {
            $this->api->respond_error('A JSON request body is required.', 400);
        }

        $username = $body['username'] ?? null;
        $password = $body['password'] ?? null;
        if (!is_string($username) || !is_string($password) || trim($username) === '') {
            $this->api->respond_error('Username and password are required.', 422);
        }
        $username = trim($username);
        if (strlen($username) > 100) {
            $this->api->respond_error('Username must be 100 characters or fewer.', 422);
        }

        $configured_username = getenv('ADMIN_USERNAME') ?: '';
        $password_hash = getenv('ADMIN_PASSWORD_HASH') ?: '';
        $valid_admin = $configured_username !== ''
            && $password_hash !== ''
            && hash_equals($configured_username, $username)
            && password_verify($password, $password_hash);

        if ($valid_admin) {
            $stmt = $this->db->raw(
                'SELECT id, role, is_active FROM users WHERE username = ? OR email = ? LIMIT 1',
                [$username, $username]
            );
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && ($user['role'] !== 'admin' || (int) $user['is_active'] !== 1)) {
                $this->api->respond_error('This account is not authorized to administer products.', 403);
            }

            if (!$user) {
                $this->db->raw(
                    'INSERT INTO users (username, email, password, role, is_active) VALUES (?, ?, ?, ?, 1)',
                    [$username, $username, $password_hash, 'admin']
                );
                $user_id = $this->db->last_id();
            } else {
                $user_id = (int) $user['id'];
                $this->db->raw(
                    'UPDATE users SET password = ? WHERE id = ?',
                    [$password_hash, $user_id]
                );
            }

            $role = 'admin';
            $user_name = $username;
        } else {
            $stmt = $this->db->raw(
                'SELECT id, username, role, is_active, password FROM users WHERE username = ? OR email = ? LIMIT 1',
                [$username, $username]
            );
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || $user['role'] !== 'user' || (int) $user['is_active'] !== 1
                || !password_verify($password, $user['password'])) {
                $this->api->respond_error('The username or password is incorrect.', 401);
            }

            $user_id = (int) $user['id'];
            $user_name = $user['username'];
            $role = $user['role'];
        }

        $tokens = $this->api->issue_tokens([
            'id' => $user_id,
            'role' => $role,
            'scopes' => ['read', 'write', 'delete'],
        ]);

        $this->api->respond([
            'user' => ['id' => $user_id, 'username' => $user_name, 'role' => $role],
            'tokens' => $tokens,
        ]);
    }

    public function register()
    {
        $this->api->rate_limit('api-register-' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 10, 60);
        $body = $this->request->json();
        if (!is_array($body)) {
            $this->api->respond_error('A JSON request body is required.', 400);
        }

        $username = $body['username'] ?? null;
        $email = $body['email'] ?? null;
        $password = $body['password'] ?? null;
        if (!is_string($username) || !is_string($email) || !is_string($password)) {
            $this->api->respond_error('Enter a username, a valid email, and a password of at least 8 characters.', 422);
        }

        $username = trim($username);
        $email = trim($email);
        if ($username === '' || strlen($username) > 100
            || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255
            || strlen($password) < 8 || strlen($password) > 72) {
            $this->api->respond_error('Enter a username, a valid email, and a password between 8 and 72 characters.', 422);
        }

        $admin_username = getenv('ADMIN_USERNAME') ?: '';
        if ($admin_username !== ''
            && (strcasecmp($username, $admin_username) === 0 || strcasecmp($email, $admin_username) === 0)) {
            $this->api->respond_error('That username or email is reserved.', 409);
        }

        $existing = $this->db->raw(
            'SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1',
            [$username, $email]
        )->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            $this->api->respond_error('That username or email is already registered.', 409);
        }

        $this->db->raw(
            'INSERT INTO users (username, email, password, role, is_active) VALUES (?, ?, ?, ?, 1)',
            [$username, $email, password_hash($password, PASSWORD_DEFAULT), 'user']
        );

        $this->api->respond([
            'message' => 'Account created successfully. You can now log in.',
        ], 201);
    }

    public function refresh()
    {
        $body = $this->request->json();
        $refresh_token = is_array($body) ? ($body['refresh_token'] ?? null) : null;
        if (!is_string($refresh_token) || $refresh_token === '') {
            $this->api->respond_error('A refresh token is required.', 400);
        }

        $this->api->refresh_access_token($refresh_token);
    }

    public function logout()
    {
        $access = $this->api->require_jwt();
        $body = $this->request->json();
        $refresh_token = is_array($body) ? ($body['refresh_token'] ?? null) : null;
        $refresh = is_string($refresh_token)
            ? $this->api->validate_jwt($refresh_token, 'refresh')
            : null;

        if (!$refresh || (string) $refresh['sub'] !== (string) $access['sub']) {
            $this->api->respond_error('A matching refresh token is required.', 400);
        }

        $this->api->revoke_refresh_token($refresh_token);
        $this->api->respond(['message' => 'Signed out successfully.']);
    }

    public function me()
    {
        $user = $this->authenticated_user();
        $stmt = $this->db->raw(
            'SELECT id, username, email, role FROM users WHERE id = ? LIMIT 1',
            [$user['sub']]
        );
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->api->respond(['user' => $record]);
    }

    public function products()
    {
        $user = $this->authenticated_user();
        $this->require_scope($user, 'read');

        $products = $this->db->raw(
            'SELECT id, product_name, description, price, quantity, created_at FROM products ORDER BY id DESC'
        )->fetchAll(PDO::FETCH_ASSOC);

        $this->api->respond(['products' => $products]);
    }

    public function show_product($id)
    {
        $user = $this->authenticated_user();
        $this->require_scope($user, 'read');
        $product = $this->find_product($id);
        $this->api->respond(['product' => $product]);
    }

    public function create_product()
    {
        $user = $this->authenticated_user();
        $this->require_scope($user, 'write');
        $product = $this->validated_product($this->request->json());

        $this->db->raw(
            'INSERT INTO products (product_name, description, price, quantity) VALUES (?, ?, ?, ?)',
            [$product['product_name'], $product['description'], $product['price'], $product['quantity']]
        );

        $this->api->respond([
            'message' => 'Product created successfully.',
            'product' => $this->find_product($this->db->last_id()),
        ], 201);
    }

    public function update_product($id)
    {
        $user = $this->authenticated_user();
        $this->require_scope($user, 'write');
        $existing = $this->find_product($id);
        $body = $this->request->json();

        if (!is_array($body)) {
            $this->api->respond_error('A JSON request body is required.', 400);
        }

        if ($this->request->method(true) === 'PUT') {
            foreach (['product_name', 'description', 'price', 'quantity'] as $field) {
                if (!array_key_exists($field, $body)) {
                    $this->api->respond_error("The {$field} field is required for PUT.", 422);
                }
            }
        } else {
            $body = array_merge($existing, $body);
        }

        $product = $this->validated_product($body);
        $this->db->raw(
            'UPDATE products SET product_name = ?, description = ?, price = ?, quantity = ? WHERE id = ?',
            [$product['product_name'], $product['description'], $product['price'], $product['quantity'], (int) $id]
        );

        $this->api->respond([
            'message' => 'Product updated successfully.',
            'product' => $this->find_product($id),
        ]);
    }

    public function delete_product($id)
    {
        $user = $this->authenticated_user();
        $this->require_scope($user, 'delete');
        $this->find_product($id);
        $this->db->raw('DELETE FROM products WHERE id = ?', [(int) $id]);

        $this->api->respond(['message' => 'Product deleted successfully.']);
    }

    private function authenticated_user()
    {
        return $this->api->require_jwt();
    }

    private function require_scope($user, $scope)
    {
        if (!in_array($scope, $user['scopes'] ?? [], true)) {
            $this->api->respond_error('Forbidden.', 403);
        }
    }

    private function find_product($id)
    {
        $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            $this->api->respond_error('Product not found.', 404);
        }

        $stmt = $this->db->raw(
            'SELECT id, product_name, description, price, quantity, created_at FROM products WHERE id = ? LIMIT 1',
            [(int) $id]
        );
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $this->api->respond_error('Product not found.', 404);
        }

        return $product;
    }

    private function validated_product($body)
    {
        if (!is_array($body)) {
            $this->api->respond_error('A JSON request body is required.', 400);
        }

        $name = $body['product_name'] ?? null;
        $description = $body['description'] ?? null;
        $price = $body['price'] ?? null;
        $quantity = $body['quantity'] ?? null;

        if (!is_string($name) || trim($name) === '' || mb_strlen(trim($name)) > 100) {
            $this->api->respond_error('Product name is required and must be 100 characters or fewer.', 422);
        }
        if (!is_string($description)) {
            $this->api->respond_error('Product description must be text.', 422);
        }
        if (strlen($description) > 65535) {
            $this->api->respond_error('Description must be 65,535 bytes or fewer.', 422);
        }
        if (!is_string($price) && !is_int($price) && !is_float($price)) {
            $this->api->respond_error('Price must be a decimal value.', 422);
        }
        $price = (string) $price;
        if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $price)) {
            $this->api->respond_error('Price must be between 0 and 99,999,999.99 with up to two decimal places.', 422);
        }
        if (!is_int($quantity) && !(is_string($quantity) && preg_match('/^\d{1,10}$/D', $quantity))) {
            $this->api->respond_error('Quantity must be a whole number between 0 and 2,147,483,647.', 422);
        }
        if ((int) $quantity > 2147483647) {
            $this->api->respond_error('Quantity must be a whole number between 0 and 2,147,483,647.', 422);
        }

        return [
            'product_name' => trim($name),
            'description' => $description,
            'price' => number_format((float) $price, 2, '.', ''),
            'quantity' => (int) $quantity,
        ];
    }
}
