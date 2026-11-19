<?php
declare(strict_types=1);
$headline = text_value($blog['title'] ?? $blog['name'] ?? 'Travel story');
$title = $headline;
$description = text_value($blog['meta_description'] ?? $blog['excerpt'] ?? $blog['summary'] ?? config('website.meta_description'));
$canonical = rtrim((string) config('app.url'), '/') . '/blogs/' . rawurlencode((string) ($blog['slug'] ?? ''));
?>
<section class="py-5"><article class="container prose">
  <?php render_json_ld([
      '@context' => 'https://schema.org',
      '@type' => 'BreadcrumbList',
      'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => rtrim((string) config('app.url'), '/') . '/'],
          ['@type' => 'ListItem', 'position' => 2, 'name' => 'Travel stories', 'item' => rtrim((string) config('app.url'), '/') . '/blogs'],
          ['@type' => 'ListItem', 'position' => 3, 'name' => $headline, 'item' => $canonical],
      ],
  ]); ?>
  <p class="breadcrumbs small"><a href="/">Home</a> / <a href="/blogs">Stories</a> / <?= e($headline) ?></p>
  <h1 class="display-5 fw-semibold"><?= e($headline) ?></h1>
  <?php if (($image = safe_image_url($blog['image'] ?? null)) !== null): ?><img class="detail-image img-fluid rounded my-3" src="<?= e($image) ?>" width="1200" height="750" fetchpriority="high" alt="<?= e($blog['image_alt'] ?? $headline) ?>"><?php endif; ?>
  <p><?= e($blog['excerpt'] ?? $blog['summary'] ?? '') ?></p>
  <div><?= nl2br(e(strip_tags(text_value($blog['content'] ?? $blog['body'] ?? '')))) ?></div>
</article></section>
