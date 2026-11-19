<?php
declare(strict_types=1);
$title = 'Travel packages';
$description = 'Explore thoughtfully planned tours and travel packages.';
$items = api_items($packages ?? []);
?>
<section class="section"><div class="container">
  <p class="eyebrow">Find your next trip</p><h1>Travel packages</h1>
  <?php if ($items === []): ?><p>Packages are temporarily unavailable. Please <a href="/contact">contact us</a> for help planning your trip.</p>
  <?php else: ?><div class="grid">
    <?php foreach ($items as $item): ?>
      <?php if (!is_array($item) || !is_published($item)) continue; $slug = valid_slug(text_value($item['slug'] ?? '')); if ($slug === null) continue; ?>
      <article class="card">
        <?php if (($image = safe_image_url($item['image'] ?? null)) !== null): ?><img src="<?= e($image) ?>" width="640" height="400" loading="lazy" alt="<?= e($item['image_alt'] ?? $item['name'] ?? 'Travel destination') ?>"><?php endif; ?>
        <div class="card-body"><h2><a href="/packages/<?= e(rawurlencode($slug)) ?>"><?= e($item['name'] ?? $item['title'] ?? 'Travel package') ?></a></h2><p><?= e($item['short_description'] ?? $item['description'] ?? '') ?></p></div>
      </article>
    <?php endforeach; ?>
  </div><?php endif; ?>
</div></section>
