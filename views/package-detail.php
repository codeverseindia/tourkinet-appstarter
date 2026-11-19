<?php
declare(strict_types=1);
$name = text_value($package['name'] ?? $package['title'] ?? 'Travel package');
$title = $name;
$description = text_value($package['meta_description'] ?? $package['short_description'] ?? $package['description'] ?? config('website.meta_description'));
$canonical = rtrim((string) config('app.url'), '/') . '/packages/' . rawurlencode((string) ($package['slug'] ?? ''));
?>
<section class="section"><article class="container prose">
  <?php render_json_ld([
      '@context' => 'https://schema.org',
      '@type' => 'BreadcrumbList',
      'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => rtrim((string) config('app.url'), '/') . '/'],
          ['@type' => 'ListItem', 'position' => 2, 'name' => 'Packages', 'item' => rtrim((string) config('app.url'), '/') . '/packages'],
          ['@type' => 'ListItem', 'position' => 3, 'name' => $name, 'item' => $canonical],
      ],
  ]); ?>
  <p class="breadcrumbs"><a href="/">Home</a> / <a href="/packages">Packages</a> / <?= e($name) ?></p>
  <p class="eyebrow">Travel package</p><h1><?= e($name) ?></h1>
  <?php if (($image = safe_image_url($package['image'] ?? null)) !== null): ?><img class="detail-image" src="<?= e($image) ?>" width="1200" height="750" fetchpriority="high" alt="<?= e($package['image_alt'] ?? $name) ?>"><?php endif; ?>
  <p><?= e($package['description'] ?? $package['short_description'] ?? '') ?></p>
  <p>Contact us for current availability and trip details.</p><a class="button" href="/contact">Ask about this trip</a>
</article></section>
