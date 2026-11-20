<?php
declare(strict_types=1);

$packageView = dirname(__DIR__, 3) . '/vendor/codeverseindia/tourkinet-travel-app/resources/views/pages/package-detail.php';
ob_start();
try {
    require $packageView;
} finally {
    $packageHtml = (string) ob_get_clean();
}

$faqs = is_array($package['destination_faqs'] ?? null) ? $package['destination_faqs'] : [];
$faqGroups = [];
foreach ($faqs as $faq) {
    if (!is_array($faq)) {
        continue;
    }
    $question = text_value($faq['question'] ?? '');
    $answer = text_value($faq['answer'] ?? '');
    if ($question === '' || $answer === '') {
        continue;
    }

    $destination = is_array($faq['destination'] ?? null) ? $faq['destination'] : [];
    $destinationName = text_value($destination['name'] ?? $destination['slug'] ?? '');
    $groupName = $destinationName !== '' ? $destinationName : 'General travel questions';
    $location = is_array($faq['location'] ?? null) ? $faq['location'] : [];
    $faqGroups[$groupName] ??= ['name' => $groupName, 'faqs' => []];
    $faqGroups[$groupName]['faqs'][] = [
        'question' => $question,
        'answer' => $answer,
        'location' => text_value($location['name'] ?? $location['slug'] ?? ''),
    ];
}

$faqMarkup = '';
if ($faqGroups !== []) {
    ob_start();
    ?>
    <section class="mt-5 package-detail-group package-detail-group--faqs" id="package-group-faqs" aria-labelledby="package-faq-heading">
      <h2 class="h3 mb-3 package-detail-group__heading" id="package-faq-heading">Frequently asked questions</h2>
      <div class="package-detail-group__body">
      <?php foreach (array_values($faqGroups) as $groupIndex => $group): ?>
        <?php $accordionId = 'package-faqs-' . $groupIndex; ?>
        <section class="mb-4" aria-labelledby="<?= e($accordionId) ?>-heading">
          <h3 class="h5 mb-2" id="<?= e($accordionId) ?>-heading"><?= e($group['name']) ?></h3>
          <div class="accordion" id="<?= e($accordionId) ?>">
            <?php foreach ($group['faqs'] as $faqIndex => $faq): ?>
              <?php $faqId = $accordionId . '-' . $faqIndex; ?>
              <div class="accordion-item">
                <h4 class="accordion-header" id="<?= e($faqId) ?>-heading">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?= e($faqId) ?>" aria-expanded="false" aria-controls="<?= e($faqId) ?>">
                    <?= e($faq['question']) ?>
                  </button>
                </h4>
                <div class="accordion-collapse collapse" id="<?= e($faqId) ?>" aria-labelledby="<?= e($faqId) ?>-heading" data-bs-parent="#<?= e($accordionId) ?>">
                  <div class="accordion-body">
                    <?php if ($faq['location'] !== ''): ?><p class="small text-secondary"><?= e($faq['location']) ?></p><?php endif; ?>
                    <p class="mb-0"><?= e($faq['answer']) ?></p>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endforeach; ?>
      </div>
    </section>
    <?php
    $faqMarkup = (string) ob_get_clean();
}

$packageHash = text_value($summary['package_hash'] ?? '');
$packageName = text_value($summary['title'] ?? $summary['name'] ?? $name);
$csrf = is_string($csrf ?? null) && $csrf !== '' ? $csrf : csrf_token();
$turnstileSiteKey = (string) config('tourkinet.turnstile.site_key', '');
ob_start();
?>
<section class="package-inquiry" id="package-inquiry" aria-labelledby="package-inquiry-heading">
  <h2 class="h5 mb-2" id="package-inquiry-heading">Ask about this package</h2>
  <p class="package-inquiry__intro">Share your contact details and our team will follow up.</p>
  <form action="/contact" method="post" class="package-inquiry__form">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="inquiry_type" value="package">
    <input type="hidden" name="package_hash" value="<?= e($packageHash) ?>">
    <input type="hidden" name="package_code" value="<?= e($packageCode) ?>">
    <input type="hidden" name="package_name" value="<?= e($packageName) ?>">
    <div class="honeypot" aria-hidden="true"><label>Leave this field empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
    <div class="mb-2">
      <label class="form-label" for="package-inquiry-name">Your name</label>
      <input class="form-control" id="package-inquiry-name" name="first_name" maxlength="100" autocomplete="name">
    </div>
    <div class="mb-2">
      <label class="form-label" for="package-inquiry-email">Email</label>
      <input class="form-control" type="email" id="package-inquiry-email" name="email" maxlength="254" autocomplete="email">
    </div>
    <div class="mb-2">
      <label class="form-label" for="package-inquiry-phone">Phone</label>
      <input class="form-control" type="tel" id="package-inquiry-phone" name="phone" maxlength="30" autocomplete="tel">
    </div>
    <p class="form-text mb-2">Please provide an email address or phone number so we can reply.</p>
    <div class="mb-3">
      <label class="form-label" for="package-inquiry-message">Message <span class="text-secondary">(optional)</span></label>
      <textarea class="form-control" id="package-inquiry-message" name="message" maxlength="5000" rows="3"></textarea>
    </div>
    <?php if ($turnstileSiteKey !== ''): ?>
      <div class="mb-3"><div class="cf-turnstile" data-sitekey="<?= e($turnstileSiteKey) ?>"></div><script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script></div>
    <?php endif; ?>
    <button class="btn btn-primary w-100" type="submit">Send package inquiry</button>
  </form>
</section>
<?php
$packageInquiryMarkup = (string) ob_get_clean();

$contactUrl = '/contact?' . http_build_query(['package' => $slug]);
$packageHtml = str_replace(
    '<a class="btn btn-primary w-100" href="' . e($contactUrl) . '">Ask about this trip</a>',
    $packageInquiryMarkup,
    $packageHtml,
);

$extraMarkup = $faqMarkup;
if ($extraMarkup !== '') {
    $insertAt = strrpos($packageHtml, '</article></section>');
    if ($insertAt === false) {
        throw new RuntimeException('Unable to add package inquiry and FAQ sections to the package detail view.');
    }
    $packageHtml = substr_replace($packageHtml, $extraMarkup, $insertAt, 0);
}

$sectionTargets = [
    ['label' => 'Itinerary', 'title' => 'Itinerary', 'id' => 'package-itinerary', 'group' => 'itinerary', 'heading' => '<h2 class="h3 mb-3">Itinerary</h2>'],
    ['label' => 'Gallery', 'title' => 'Gallery', 'id' => 'package-gallery', 'group' => 'gallery', 'heading' => '<h2 class="h3 mb-3">Gallery</h2>'],
    ['label' => 'What’s included', 'title' => 'What’s included', 'id' => 'package-included', 'group' => 'included', 'heading' => '<h2 class="h3 mb-3">What’s included</h2>'],
    ['label' => 'What’s excluded', 'title' => 'What’s excluded', 'id' => 'package-excluded', 'group' => 'excluded', 'heading' => '<h2 class="h3 mb-3">What’s excluded</h2>'],
    ['label' => 'Terms', 'title' => 'Terms and conditions', 'id' => 'package-terms', 'group' => 'terms', 'heading' => '<h2 class="h3 mb-3">Terms and conditions</h2>'],
];
$navigationItems = [];
foreach ($sectionTargets as $target) {
    if (!str_contains($packageHtml, $target['heading'])) {
        continue;
    }

    $replacement = '<h2 class="h3 mb-3" id="' . e($target['id']) . '">' . e($target['title']) . '</h2>';
    $packageHtml = str_replace($target['heading'], $replacement, $packageHtml);

    $headingMarkup = '<h2 class="h3 mb-3" id="' . e($target['id']) . '">' . e($target['title']) . '</h2>';
    $headingPosition = strpos($packageHtml, $headingMarkup);
    if ($headingPosition === false) {
        continue;
    }
    $navigationItems[] = ['label' => $target['label'], 'id' => $target['id']];

    $sectionStart = strrpos(substr($packageHtml, 0, $headingPosition), '<section class="mt-5"');
    if ($sectionStart === false) {
        continue;
    }
    preg_match_all('/<section\b[^>]*>|<\/section>/i', substr($packageHtml, $sectionStart), $sectionTags, PREG_OFFSET_CAPTURE);
    $sectionDepth = 0;
    $sectionEnd = null;
    foreach ($sectionTags[0] as [$tag, $offset]) {
        $sectionDepth += str_starts_with(strtolower($tag), '</') ? -1 : 1;
        if ($sectionDepth === 0) {
            $sectionEnd = $sectionStart + $offset + strlen($tag);
            break;
        }
    }
    if ($sectionEnd === null) {
        continue;
    }

    $sectionMarkup = substr($packageHtml, $sectionStart, $sectionEnd - $sectionStart);
    $groupHeading = '<h2 class="h3 mb-3 package-detail-group__heading" id="' . e($target['id']) . '">' . e($target['title']) . '</h2>';
    $sectionMarkup = preg_replace(
        '/^<section class="mt-5">/',
        '<section class="mt-5 package-detail-group package-detail-group--' . e($target['group']) . '" id="package-group-' . e($target['group']) . '" aria-labelledby="' . e($target['id']) . '">',
        $sectionMarkup,
        1,
    ) ?? $sectionMarkup;
    $sectionMarkup = str_replace($headingMarkup, $groupHeading, $sectionMarkup);
    $sectionMarkup = preg_replace(
        '/<\/h2>(.*)<\/section>$/s',
        '</h2><div class="package-detail-group__body">$1</div></section>',
        $sectionMarkup,
        1,
    ) ?? $sectionMarkup;
    $packageHtml = substr_replace($packageHtml, $sectionMarkup, $sectionStart, $sectionEnd - $sectionStart);
}
if ($faqMarkup !== '') {
    $navigationItems[] = ['label' => 'FAQs', 'id' => 'package-faq-heading'];
}

if (count($navigationItems) > 1) {
    ob_start();
    ?>
    <nav class="package-detail-nav" aria-label="On this page">
      <span class="package-detail-nav__label">On this page</span>
      <?php foreach ($navigationItems as $item): ?>
        <a href="#<?= e($item['id']) ?>"><?= e($item['label']) ?></a>
      <?php endforeach; ?>
    </nav>
    <?php
    $navigationMarkup = (string) ob_get_clean();
    $insertAt = strpos($packageHtml, '<section class="mt-5');
    if ($insertAt !== false) {
        $packageHtml = substr_replace($packageHtml, $navigationMarkup, $insertAt, 0);
    }
}

echo $packageHtml;
