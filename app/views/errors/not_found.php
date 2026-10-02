<section class="panel">
    <h1>Product not found</h1>
    <p><?= htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
    <a class="button" href="<?= htmlspecialchars(site_url('products'), ENT_QUOTES, 'UTF-8') ?>">Return to products</a>
</section>
