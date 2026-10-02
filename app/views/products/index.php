<div class="heading">
    <div>
        <p style="margin:0 0 5px;color:#176b55;font-weight:700;letter-spacing:.08em;font-size:12px">INVENTORY</p>
        <h1>Products</h1>
        <p style="margin:6px 0 0">Manage your catalog and available stock.</p>
    </div>
    <a class="button" href="<?= htmlspecialchars(site_url('products/create'), ENT_QUOTES, 'UTF-8') ?>">Add product</a>
</div>
<section class="panel">
    <?php if (empty($products)): ?>
        <div class="empty">
            <h2>Your inventory is empty</h2>
            <p>Add a product to get started.</p>
            <a class="button" href="<?= htmlspecialchars(site_url('products/create'), ENT_QUOTES, 'UTF-8') ?>">Add your first product</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Product</th><th>Description</th><th>Price</th><th>Quantity</th><th>Created</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($product['product_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong></td>
                            <td><?= nl2br(htmlspecialchars($product['description'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) ?></td>
                            <td><?= htmlspecialchars(number_format((float) $product['price'], 2), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int) $product['quantity'] ?></td>
                            <td><?= htmlspecialchars($product['created_at'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                            <td class="actions">
                                <a class="button secondary small" href="<?= htmlspecialchars(site_url('products/edit/' . (int) $product['id']), ENT_QUOTES, 'UTF-8') ?>">Edit</a>
                                <a class="button secondary small" href="<?= htmlspecialchars(site_url('products/delete/' . (int) $product['id']), ENT_QUOTES, 'UTF-8') ?>">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
