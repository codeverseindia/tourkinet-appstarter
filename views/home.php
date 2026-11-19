<?php
declare(strict_types=1);
$title = 'Find your next journey';
$description = 'Discover thoughtful travel experiences and memorable destinations.';
?>
<section class="hero py-5">
  <div class="container py-lg-5">
    <div class="col-lg-9">
      <p class="eyebrow mb-3">Travel, thoughtfully planned</p>
      <h1 class="display-2 fw-bold">Go a little further. Feel a lot more.</h1>
      <p class="lead">Find inspiring places, local experiences and trips made around the way you love to travel.</p>
      <a class="btn btn-primary btn-lg mt-2" href="/packages">Explore our trips</a>
    </div>
  </div>
</section>
<section class="py-5">
  <div class="container">
    <div class="d-flex flex-column flex-sm-row align-items-sm-end justify-content-between gap-3 mb-4">
      <div><p class="eyebrow mb-2">Start exploring</p><h2 class="mb-0">Featured journeys</h2></div>
      <a href="/packages">View all packages</a>
    </div>
    <?php $items = api_items($packages ?? []); ?>
    <?php if ($items === []): ?>
      <p class="alert alert-info">Our journeys are being refreshed. Please check back soon or <a href="/contact">contact us</a> to plan a trip.</p>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach (array_slice($items, 0, 3) as $item): ?>
          <?php if (!is_array($item) || !is_published($item)) continue; $slug = valid_slug(text_value($item['slug'] ?? '')); if ($slug === null) continue; ?>
          <article class="col-12 col-md-6 col-lg-4"><div class="card h-100"><div class="card-body"><h3 class="h5"><a href="/packages/<?= e(rawurlencode($slug)) ?>"><?= e($item['name'] ?? $item['title'] ?? 'Explore this journey') ?></a></h3><p class="mb-0"><?= e($item['short_description'] ?? $item['description'] ?? '') ?></p></div></div></article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<section class="section-soft py-5"><div class="container"><div class="prose"><p class="eyebrow">Made for curious travellers</p><h2>More than a place on the map</h2><p>We bring together meaningful experiences, local knowledge and practical planning to make your next journey yours.</p><a class="btn btn-primary" href="/about">Meet us</a></div></div></section>
