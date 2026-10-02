<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class ProductController extends Controller
{
    public function before_action()
    {
        $this->call->library('session');

        if ($this->session->userdata('authenticated') !== true) {
            redirect(site_url('login'));
        }

        $this->call->database();
        $this->call->model('ProductModel');
    }

    public function index()
    {
        $this->render('products/index', [
            'products' => $this->ProductModel->allNewestFirst(),
        ]);
    }

    public function create()
    {
        $this->render_form('Add product', 'products/create', [
            'product_name' => '',
            'description' => '',
            'price' => '',
            'quantity' => '',
        ]);
    }

    public function store()
    {
        [$product, $errors] = $this->validated_product();
        if ($errors) {
            $this->render_form('Add product', 'products/create', $product, $errors);
            return;
        }

        $this->ProductModel->insert($product);
        $this->session->set_flashdata('success', 'Product added successfully.');
        redirect(site_url('products'));
    }

    public function edit($id)
    {
        $product = $this->find_product($id);
        if ($product === null) {
            return;
        }

        $this->render_form('Edit product', 'products/edit', $product);
    }

    public function update($id)
    {
        $existing = $this->find_product($id);
        if ($existing === null) {
            return;
        }

        [$product, $errors] = $this->validated_product();
        $product['id'] = (int) $id;
        if ($errors) {
            $this->render_form('Edit product', 'products/edit', $product, $errors);
            return;
        }

        $this->ProductModel->update((int) $id, $product);
        $this->session->set_flashdata('success', 'Product updated successfully.');
        redirect(site_url('products'));
    }

    public function confirm_delete($id)
    {
        $product = $this->find_product($id);
        if ($product === null) {
            return;
        }

        $this->render('products/delete', ['product' => $product]);
    }

    public function delete($id)
    {
        $product = $this->find_product($id);
        if ($product === null) {
            return;
        }

        $this->ProductModel->delete((int) $id);
        $this->session->set_flashdata('success', 'Product deleted successfully.');
        redirect(site_url('products'));
    }

    private function find_product($id)
    {
        $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $product = $id === false ? null : $this->ProductModel->find($id);

        if (!$product) {
            http_response_code(404);
            $this->render('errors/not_found', ['message' => 'The requested product could not be found.']);
            return null;
        }

        return $product;
    }

    private function validated_product()
    {
        $fields = [];
        foreach (['product_name', 'description', 'price', 'quantity'] as $field) {
            $value = $this->call->request->post($field, '');
            $fields[$field] = is_string($value) ? trim($value) : '';
        }

        $errors = [];
        if ($fields['product_name'] === '' || mb_strlen($fields['product_name']) > 100) {
            $errors[] = 'Product name is required and must be 100 characters or fewer.';
        }
        if (strlen($fields['description']) > 65535) {
            $errors[] = 'Description must be 65,535 bytes or fewer.';
        }
        if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $fields['price'])) {
            $errors[] = 'Price must be between 0 and 99,999,999.99 with up to two decimal places.';
        }
        if (!preg_match('/^\d{1,10}$/D', $fields['quantity'])
            || (int) $fields['quantity'] > 2147483647) {
            $errors[] = 'Quantity must be a whole number between 0 and 2,147,483,647.';
        }

        if (!$errors) {
            $fields['price'] = number_format((float) $fields['price'], 2, '.', '');
            $fields['quantity'] = (int) $fields['quantity'];
        }

        return [$fields, $errors];
    }

    private function render_form($title, $content_view, $product, $errors = [])
    {
        $this->render($content_view, [
            'title' => $title,
            'product' => $product,
            'errors' => $errors,
        ]);
    }

    private function render($content_view, $data = [])
    {
        $data['title'] = $data['title'] ?? 'Products';
        $data['content_view'] = $content_view;
        $data['session'] = $this->session;
        $this->call->view('layout', $data);
    }
}
