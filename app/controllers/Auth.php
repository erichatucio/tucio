<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Auth extends Controller
{
    public function before_action()
    {
        $this->call->library('session');
    }

    public function login()
    {
        if ($this->session->userdata('authenticated') === true) {
            redirect(site_url('products'));
        }

        $this->render_login();
    }

    public function api_login_landing()
    {
        redirect(site_url('login'));
    }

    public function authenticate()
    {
        $username = $this->request->post('username');
        $password = $this->request->post('password');
        $configured_username = getenv('ADMIN_USERNAME') ?: '';
        $password_hash = getenv('ADMIN_PASSWORD_HASH') ?: '';

        $configured = $configured_username !== '' && $password_hash !== '';
        $valid_username = is_string($username)
            && $configured_username !== ''
            && hash_equals($configured_username, $username);
        $valid_password = is_string($password)
            && $password_hash !== ''
            && password_verify($password, $password_hash);

        if (!$configured) {
            $this->render_login('Administrator credentials are not configured. Set ADMIN_USERNAME and ADMIN_PASSWORD_HASH.');
            return;
        }

        if (!$valid_username || !$valid_password) {
            $this->render_login('The username or password is incorrect.');
            return;
        }

        $this->session->regenerate_on_login(true);
        $this->session->set_userdata([
            'authenticated' => true,
            'username' => $configured_username,
        ]);

        redirect(site_url('products'));
    }

    public function logout()
    {
        $this->session->sess_destroy();
        redirect(site_url('login'));
    }

    private function render_login($error = null)
    {
        $this->call->view('layout', [
            'title' => 'Sign in',
            'content_view' => 'auth/login',
            'error' => $error,
            'session' => $this->session,
        ]);
    }
}
