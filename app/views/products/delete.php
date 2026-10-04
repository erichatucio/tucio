<div class="page-heading">
    <div>
        <p class="eyebrow">INVENTORY / REMOVE ITEM</p>
        <h1>Delete product</h1>
        <p class="page-description">Review the item before removing it from your catalog.</p>
    </div>
    <a class="button secondary" href="<?= htmlspecialchars(site_url('products'), ENT_QUOTES, 'UTF-8') ?>">Back to products</a>
</div>
<section class="panel delete-panel">
    <span class="warning-mark" aria-hidden="true">!</span>
    <h2>Delete this product?</h2>
    <p class="delete-copy">This action cannot be undone. The following item will be permanently removed:</p>
    <p class="delete-product">
        <strong><?= htmlspecialchars($product['product_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>
        · $<?= htmlspecialchars(number_format((float) $product['price'], 2), ENT_QUOTES, 'UTF-8') ?>
        · <?= number_format((int) $product['quantity']) ?> in stock
    </p>
    <form method="post" action="<?= htmlspecialchars(site_url('products/delete/' . (int) $product['id']), ENT_QUOTES, 'UTF-8') ?>">
        <?= csrf_field() ?>
        <div class="actions-row">
            <button class="button danger" type="submit">Yes, delete product</button>
            <a class="button secondary" href="<?= htmlspecialchars(site_url('products'), ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
        </div>
    </form>
</section>
