<div class="heading">
    <div><p style="margin:0 0 5px;color:#a33636;font-weight:700;letter-spacing:.08em;font-size:12px">INVENTORY</p><h1>Delete product</h1></div>
    <a class="button secondary" href="<?= htmlspecialchars(site_url('products'), ENT_QUOTES, 'UTF-8') ?>">Back to products</a>
</div>
<section class="panel form-panel">
    <h2>Are you sure you want to delete this product?</h2>
    <p>This action cannot be undone. The following item will be permanently removed:</p>
    <p><strong><?= htmlspecialchars($product['product_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong> · $<?= htmlspecialchars(number_format((float) $product['price'], 2), ENT_QUOTES, 'UTF-8') ?> · quantity <?= (int) $product['quantity'] ?></p>
    <form method="post" action="<?= htmlspecialchars(site_url('products/delete/' . (int) $product['id']), ENT_QUOTES, 'UTF-8') ?>">
        <?= csrf_field() ?>
        <div class="actions-row"><button class="button danger" type="submit">Yes, delete product</button><a class="button secondary" href="<?= htmlspecialchars(site_url('products'), ENT_QUOTES, 'UTF-8') ?>">Cancel</a></div>
    </form>
</section>
