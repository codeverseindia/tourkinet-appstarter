<?php
declare(strict_types=1);
$company = (string) ($website['company_name'] ?? config('website.company_name'));
$metaTitle = (string) ($meta_title ?? $title ?? config('website.meta_title'));
$metaDescription = (string) ($description ?? config('website.meta_description'));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(page_title($metaTitle)) ?></title>
  <meta name="description" content="<?= e($metaDescription) ?>">
  <link rel="canonical" href="<?= e($canonical ?? config('app.url')) ?>">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= e(page_title($metaTitle)) ?>">
  <meta property="og:description" content="<?= e($metaDescription) ?>">
  <meta property="og:url" content="<?= e($canonical ?? config('app.url')) ?>">
  <?php
  $siteUrl = rtrim(text_value($website['url'] ?? config('app.url')), '/');
  $logoUrl = text_value($website['logo'] ?? '/assets/images/logo.svg');
  if (str_starts_with($logoUrl, '/')) {
      $logoUrl = $siteUrl . $logoUrl;
  }
  render_json_ld([
      '@context' => 'https://schema.org',
      '@type' => 'Organization',
      'name' => $company,
      'url' => $siteUrl,
      'logo' => $logoUrl,
  ]);
  ?>
  <link rel="icon" href="/assets/images/logo.svg" type="image/svg+xml">
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/theme.css">
</head>
<body>
<header class="site-header">
  <nav class="container nav" aria-label="Main navigation">
    <a class="brand" href="/" aria-label="<?= e($company) ?> home"><img src="<?= e($website['logo'] ?? '/assets/images/logo.svg') ?>" width="180" height="40" alt="<?= e($company) ?>"></a>
    <button class="menu-toggle" type="button" data-menu-toggle aria-controls="main-menu" aria-expanded="true">Menu</button>
    <div class="nav-links" id="main-menu">
      <a href="/destinations">Destinations</a>
      <a href="/packages">Packages</a>
      <a href="/blogs">Travel stories</a>
      <a href="/about">About</a>
      <a href="/contact">Contact</a>
    </div>
  </nav>
</header>
<main>
<script src="/assets/js/app.js" defer></script>
