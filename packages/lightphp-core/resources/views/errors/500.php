<section class="container py-5">
    <h1>Something went wrong</h1>
    <p>Please try again later.</p>
    <?php if (isset($details)): ?><p><?= e($details) ?></p><?php endif; ?>
</section>
