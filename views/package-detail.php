<?php
declare(strict_types=1);
$name = text_value($package['name'] ?? $package['title'] ?? 'Travel package');
$title = $name;
$description = text_value($package['meta_description'] ?? $package['short_description'] ?? $package['description'] ?? config('website.meta_description'));
$canonical = rtrim((string) config('app.url'), '/') . '/packages/' . rawurlencode((string) ($package['slug'] ?? ''));
?>
<section class="py-5"><article class="container prose">
  <?php render_json_ld([
      '@context' => 'https://schema.org',
      '@type' => 'BreadcrumbList',
      'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => rtrim((string) config('app.url'), '/') . '/'],
          ['@type' => 'ListItem', 'position' => 2, 'name' => 'Packages', 'item' => rtrim((string) config('app.url'), '/') . '/packages'],
          ['@type' => 'ListItem', 'position' => 3, 'name' => $name, 'item' => $canonical],
      ],
  ]); ?>
  <p class="breadcrumbs small"><a href="/">Home</a> / <a href="/packages">Packages</a> / <?= e($name) ?></p>
  <p class="eyebrow">Travel package</p><h1 class="display-5 fw-semibold"><?= e($name) ?></h1>
  <?php if (($image = safe_image_url($package['image'] ?? null)) !== null): ?><img class="detail-image img-fluid rounded my-3" src="<?= e($image) ?>" width="1200" height="750" fetchpriority="high" alt="<?= e($package['image_alt'] ?? $name) ?>"><?php endif; ?>
  <p><?= e($package['description'] ?? $package['short_description'] ?? '') ?></p>
  <p>Contact us for current availability and trip details.</p><a class="btn btn-primary" href="/contact">Ask about this trip</a>
</article></section>
