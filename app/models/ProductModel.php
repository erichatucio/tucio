<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class ProductModel extends Model
{
    protected $table = 'products';
    protected $fillable = ['product_name', 'description', 'price', 'quantity'];

    public function allNewestFirst()
    {
        return $this->db->table($this->table)
            ->order_by('id', 'DESC')
            ->get_all();
    }
}
