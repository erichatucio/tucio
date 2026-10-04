<div class="page-heading">
    <div>
        <p class="eyebrow">INVENTORY / PRODUCT #<?= (int) $product['id'] ?></p>
        <h1>Edit product</h1>
        <p class="page-description">Update the catalog details and stock level.</p>
    </div>
    <a class="button secondary" href="<?= htmlspecialchars(site_url('products'), ENT_QUOTES, 'UTF-8') ?>">Back to products</a>
</div>
<section class="panel form-panel">
    <?php if (!empty($errors)): ?>
        <div class="notice error" role="alert">
            <strong>Please correct the following:</strong>
            <ul><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    <form class="product-form" method="post" action="<?= htmlspecialchars(site_url('products/edit/' . (int) $product['id']), ENT_QUOTES, 'UTF-8') ?>">
        <?= csrf_field() ?>
        <div class="field full-width">
            <label for="product_name">Product name</label>
            <input id="product_name" name="product_name" maxlength="100" required value="<?= htmlspecialchars($product['product_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </div>
        <div class="field full-width">
            <label for="description">Description</label>
            <textarea id="description" name="description" maxlength="65535"><?= htmlspecialchars($product['description'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
        </div>
        <div class="field">
            <label for="price">Price</label>
            <input id="price" name="price" type="number" min="0" max="99999999.99" step="0.01" required value="<?= htmlspecialchars((string) $product['price'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </div>
        <div class="field">
            <label for="quantity">Quantity in stock</label>
            <input id="quantity" name="quantity" type="number" min="0" max="2147483647" step="1" required value="<?= htmlspecialchars((string) $product['quantity'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </div>
        <div class="actions-row">
            <button class="button" type="submit">Save changes</button>
            <a class="button secondary" href="<?= htmlspecialchars(site_url('products'), ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
        </div>
    </form>
</section>
