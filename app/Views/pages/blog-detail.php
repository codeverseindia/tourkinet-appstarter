<?php
declare(strict_types=1);

$story = is_array($blog ?? null) ? $blog : [];
$headline = text_value($story['title'] ?? $story['name'] ?? 'Travel story');
$slug = valid_slug(text_value($story['slug'] ?? '')) ?? '';
$images = is_array($story['images'] ?? null) ? $story['images'] : [];
$bannerImage = is_array($images['banner'] ?? null) ? $images['banner'] : [];
$thumbnail = is_array($images['thumbnail'] ?? null) ? $images['thumbnail'] : [];
$heroImage = safe_image_url($bannerImage['url'] ?? $story['image'] ?? $thumbnail['url'] ?? null);
$publishedAt = text_value($story['published_at'] ?? '');
$publishedTimestamp = $publishedAt !== '' ? strtotime($publishedAt) : false;
$author = is_array($story['author'] ?? null) ? $story['author'] : [];
$authorEmail = text_value($author['email'] ?? '');
$canonicalUrl = safe_canonical_url($canonical ?? null)
    ?? rtrim((string) config('app.url'), '/') . '/blogs/' . rawurlencode($slug);
$rawContent = text_value($story['content'] ?? $story['body'] ?? '');
$document = new \DOMDocument('1.0', 'UTF-8');
$previousLibxmlErrorMode = libxml_use_internal_errors(true);
$loaded = $document->loadHTML(
    '<!doctype html><html><head><meta charset="UTF-8"></head><body><div id="story-content">' . $rawContent . '</div></body></html>',
    LIBXML_NONET | LIBXML_HTML_NODEFDTD,
);
libxml_clear_errors();
libxml_use_internal_errors($previousLibxmlErrorMode);
if (!$loaded) {
    throw new RuntimeException('Unable to parse the travel story content.');
}
$contentRoot = $document->getElementById('story-content');
if (!$contentRoot instanceof \DOMElement) {
    throw new RuntimeException('Unable to read the travel story content.');
}

$safeHref = static function (string $href): ?string {
    $href = trim($href);
    if ($href === '' || preg_match('/[\x00-\x20\x7f\\\\]/', $href) === 1) {
        return null;
    }
    if (safe_canonical_url($href) !== null) {
        return $href;
    }
    if (preg_match('/^mailto:([^?]+)$/iD', $href, $match) === 1
        && filter_var($match[1], FILTER_VALIDATE_EMAIL) !== false) {
        return 'mailto:' . $match[1];
    }
    if (preg_match('/^tel:\+?[0-9][0-9(). -]{3,30}$/iD', $href) === 1) {
        return $href;
    }
    if (preg_match('/^#[A-Za-z][A-Za-z0-9_-]{0,80}$/D', $href) === 1) {
        return $href;
    }

    $parts = parse_url($href);
    if (!is_array($parts) || !str_starts_with($href, '/') || str_starts_with($href, '//')
        || isset($parts['scheme']) || isset($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
        return null;
    }
    $path = rawurldecode((string) ($parts['path'] ?? ''));
    return preg_match('#(?:^|/)\.\.(?:/|$)#', $path) === 1 ? null : $href;
};

$allowedTags = array_fill_keys([
    'a', 'blockquote', 'br', 'caption', 'code', 'col', 'colgroup', 'del', 'div', 'em', 'figcaption', 'figure',
    'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'i', 'img', 'li', 'mark', 'ol', 'p', 'pre', 's', 'span', 'strong',
    'sub', 'sup', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'u', 'ul',
], true);
$discardedTags = ['audio', 'base', 'button', 'embed', 'form', 'iframe', 'input', 'link', 'math', 'meta', 'noscript', 'object', 'script', 'select', 'source', 'style', 'svg', 'template', 'textarea', 'video'];
$renderNode = null;
$renderNode = static function (\DOMNode $node) use (&$renderNode, $allowedTags, $discardedTags, $safeHref): string {
    if ($node instanceof \DOMText) {
        return e($node->nodeValue ?? '');
    }
    if (!$node instanceof \DOMElement) {
        return '';
    }

    $tag = strtolower($node->tagName);
    if (in_array($tag, $discardedTags, true)) {
        return '';
    }
    if (!isset($allowedTags[$tag])) {
        $children = '';
        foreach ($node->childNodes as $child) {
            $children .= $renderNode($child);
        }
        return $children;
    }

    if ($tag === 'img') {
        $source = safe_image_url($node->getAttribute('src'));
        if ($source === null) {
            return '';
        }
        $attributes = ' class="blog-content__image" src="' . e($source) . '" alt="' . e($node->getAttribute('alt')) . '" loading="lazy" decoding="async"';
        foreach (['width', 'height'] as $dimension) {
            $value = $node->getAttribute($dimension);
            if (preg_match('/^[1-9][0-9]{0,3}$/D', $value) === 1 && (int) $value <= 4096) {
                $attributes .= ' ' . $dimension . '="' . e($value) . '"';
            }
        }
        return '<img' . $attributes . '>';
    }

    $attributes = '';
    if ($tag === 'a' && ($href = $safeHref($node->getAttribute('href'))) !== null) {
        $attributes .= ' href="' . e($href) . '"';
    } elseif (in_array($tag, ['td', 'th'], true)) {
        foreach (['colspan', 'rowspan'] as $span) {
            $value = $node->getAttribute($span);
            if (preg_match('/^[1-9][0-9]{0,2}$/D', $value) === 1 && (int) $value <= 100) {
                $attributes .= ' ' . $span . '="' . e($value) . '"';
            }
        }
        if ($tag === 'th' && in_array($node->getAttribute('scope'), ['row', 'col', 'rowgroup', 'colgroup'], true)) {
            $attributes .= ' scope="' . e($node->getAttribute('scope')) . '"';
        }
    } elseif ($tag === 'ol' && preg_match('/^[1-9][0-9]{0,3}$/D', $node->getAttribute('start')) === 1) {
        $attributes .= ' start="' . e($node->getAttribute('start')) . '"';
    }

    if (in_array($tag, ['blockquote', 'pre', 'table'], true)) {
        $attributes .= ' class="blog-content__' . $tag . '"';
    } elseif (in_array($tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true)) {
        $attributes .= ' class="blog-reading__section-title"';
    }

    $children = '';
    foreach ($node->childNodes as $child) {
        $children .= $renderNode($child);
    }

    $outputTag = $tag === 'h1' ? 'h2' : $tag;
    if (in_array($tag, ['br', 'col', 'hr'], true)) {
        return '<' . $outputTag . $attributes . '>';
    }
    if ($tag === 'table') {
        return '<div class="blog-content__table-wrap"><table' . $attributes . '>' . $children . '</table></div>';
    }
    return '<' . $outputTag . $attributes . '>' . $children . '</' . $outputTag . '>';
};

$articleHtml = '';
foreach ($contentRoot->childNodes as $child) {
    $articleHtml .= $renderNode($child);
}
?>
<section class="py-5">
  <article class="container prose blog-reading">
    <?php
    $siteUrl = rtrim((string) config('app.url'), '/');
    render_json_ld([
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $siteUrl . '/'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Travel stories', 'item' => $siteUrl . '/blogs'],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $headline, 'item' => $canonicalUrl],
        ],
    ]);
    ?>
    <nav class="breadcrumbs" aria-label="Breadcrumb">
      <a href="/">Home</a><span aria-hidden="true">/</span><a href="/blogs">Stories</a><span aria-hidden="true">/</span><span aria-current="page"><?= e($headline) ?></span>
    </nav>

    <header class="blog-reading__header">
      <?php if (is_array($story['category'] ?? null) && text_value($story['category']['name'] ?? '') !== ''): ?>
        <p class="blog-reading__category"><?= e($story['category']['name']) ?></p>
      <?php endif; ?>
      <h1 class="blog-reading__title"><?= e($headline) ?></h1>
      <?php $summary = text_value($story['short_intro'] ?? $story['excerpt'] ?? $story['summary'] ?? ''); ?>
      <?php if ($summary !== ''): ?><p class="blog-reading__summary"><?= e($summary) ?></p><?php endif; ?>
      <?php if ($publishedTimestamp !== false || text_value($author['name'] ?? '') !== ''): ?>
        <div class="blog-reading__meta">
          <?php if ($publishedTimestamp !== false): ?><time datetime="<?= e(date(DATE_ATOM, $publishedTimestamp)) ?>">Published <?= e(date('F j, Y', $publishedTimestamp)) ?></time><?php endif; ?>
          <?php if (text_value($author['name'] ?? '') !== ''): ?>
            <span>By <?= e($author['name']) ?><?php if (filter_var($authorEmail, FILTER_VALIDATE_EMAIL)): ?> · <a href="mailto:<?= e($authorEmail) ?>"><?= e($authorEmail) ?></a><?php endif; ?></span>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      <?php if (!empty($author['bio'])): ?><p class="blog-reading__bio"><?= e($author['bio']) ?></p><?php endif; ?>
      <?php if (!empty($story['tags']) && is_array($story['tags'])): ?>
        <div class="blog-reading__tags" aria-label="Story topics">
          <?php foreach ($story['tags'] as $tag): ?>
            <?php if (is_array($tag) && text_value($tag['name'] ?? '') !== ''): ?><span class="badge text-bg-light"><?= e($tag['name']) ?></span><?php endif; ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </header>

    <?php if ($heroImage !== null): ?>
      <figure class="blog-reading__hero">
        <img class="detail-image" src="<?= e($heroImage) ?>" width="1200" height="750" fetchpriority="high" alt="<?= e($bannerImage['caption'] ?? $headline) ?>">
        <?php if (!empty($bannerImage['caption'])): ?><figcaption><?= e($bannerImage['caption']) ?></figcaption><?php endif; ?>
      </figure>
    <?php endif; ?>
    <?php if (trim($articleHtml) !== ''): ?>
      <div class="blog-reading__body"><?= $articleHtml ?></div>
    <?php else: ?>
      <p class="alert alert-light border">Story content is not available yet.</p>
    <?php endif; ?>

    <footer class="blog-reading__footer">
      <a href="/blogs">Back to all stories</a>
      <a href="/packages">Explore travel packages</a>
    </footer>
  </article>
</section>
