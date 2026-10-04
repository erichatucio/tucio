<?php
$product_count = count($products);
$total_units = 0;
$inventory_value = 0.0;

foreach ($products as $product) {
    $quantity = (int) $product['quantity'];
    $total_units += $quantity;
    $inventory_value += (float) $product['price'] * $quantity;
}
?>
<div class="page-heading">
    <div>
        <p class="eyebrow">INVENTORY OVERVIEW</p>
        <h1>Products</h1>
        <p class="page-description">Keep your catalog and available stock in one place.</p>
    </div>
    <a class="button" href="<?= htmlspecialchars(site_url('products/create'), ENT_QUOTES, 'UTF-8') ?>">
        <span aria-hidden="true">+</span> Add product
    </a>
</div>

<section class="metrics" aria-label="Inventory summary">
    <article class="metric-card">
        <span class="metric-icon" aria-hidden="true">▦</span>
        <span><span class="metric-label">Products</span><strong class="metric-value"><?= number_format($product_count) ?></strong></span>
    </article>
    <article class="metric-card">
        <span class="metric-icon" aria-hidden="true">↗</span>
        <span><span class="metric-label">Total units</span><strong class="metric-value"><?= number_format($total_units) ?></strong></span>
    </article>
    <article class="metric-card">
        <span class="metric-icon" aria-hidden="true">$</span>
        <span><span class="metric-label">Stock value</span><strong class="metric-value">$<?= number_format($inventory_value, 2) ?></strong></span>
    </article>
</section>

<section class="panel" aria-labelledby="catalog-heading">
    <div class="panel-heading">
        <div>
            <h2 id="catalog-heading">Product catalog</h2>
            <p>Your latest inventory updates</p>
        </div>
        <span class="panel-count"><?= number_format($product_count) ?> <?= $product_count === 1 ? 'item' : 'items' ?></span>
    </div>
    <?php if (empty($products)): ?>
        <div class="empty">
            <span class="empty-mark" aria-hidden="true">+</span>
            <h2>Your inventory is empty</h2>
            <p>Add your first product to start building your catalog.</p>
            <a class="button" href="<?= htmlspecialchars(site_url('products/create'), ENT_QUOTES, 'UTF-8') ?>">Add your first product</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Product</th><th>Description</th><th>Price</th><th>In stock</th><th>Created</th><th><span class="visually-hidden">Actions</span></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <div class="product-cell">
                                    <span class="product-mark" aria-hidden="true">#<?= (int) $product['id'] ?></span>
                                    <span>
                                        <strong class="product-name"><?= htmlspecialchars($product['product_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>
                                        <small class="product-id">Product #<?= (int) $product['id'] ?></small>
                                    </span>
                                </div>
                            </td>
                            <td class="description-cell"><?= nl2br(htmlspecialchars($product['description'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) ?></td>
                            <td class="price-cell">$<?= htmlspecialchars(number_format((float) $product['price'], 2), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="stock-badge<?= (int) $product['quantity'] === 0 ? ' empty-stock' : '' ?>"><?= number_format((int) $product['quantity']) ?></span></td>
                            <td><?= htmlspecialchars($product['created_at'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                            <td class="actions">
                                <div class="actions-group">
                                    <a class="button secondary small" href="<?= htmlspecialchars(site_url('products/edit/' . (int) $product['id']), ENT_QUOTES, 'UTF-8') ?>">Edit</a>
                                    <a class="button secondary small" href="<?= htmlspecialchars(site_url('products/delete/' . (int) $product['id']), ENT_QUOTES, 'UTF-8') ?>">Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
