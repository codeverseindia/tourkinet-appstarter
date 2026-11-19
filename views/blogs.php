<?php
declare(strict_types=1);
$title = 'Travel stories';
$description = 'Ideas and stories to inspire your next journey.';
$items = api_items($blogs ?? []);
?>
<section class="py-5"><div class="container">
  <p class="eyebrow">Ideas for the road</p><h1 class="display-5 fw-semibold mb-4">Travel stories</h1>
  <?php if ($items === []): ?><p class="alert alert-info">Stories are coming soon. In the meantime, <a href="/packages">explore our journeys</a>.</p>
  <?php else: ?><div class="row g-4">
    <?php foreach ($items as $item): ?>
      <?php if (!is_array($item) || !is_published($item)) continue; $slug = valid_slug(text_value($item['slug'] ?? '')); if ($slug === null) continue; ?>
      <article class="col-12 col-md-6 col-lg-4"><div class="card h-100"><?php if (($image = safe_image_url($item['image'] ?? null)) !== null): ?><img class="card-img-top" src="<?= e($image) ?>" width="640" height="400" loading="lazy" alt="<?= e($item['image_alt'] ?? $item['title'] ?? 'Travel story') ?>"><?php endif; ?><div class="card-body"><h2 class="h5"><a href="/blogs/<?= e(rawurlencode($slug)) ?>"><?= e($item['title'] ?? $item['name'] ?? 'Travel story') ?></a></h2><p class="mb-0"><?= e($item['excerpt'] ?? $item['summary'] ?? '') ?></p></div></div></article>
    <?php endforeach; ?>
  </div><?php endif; ?>
</div></section>
