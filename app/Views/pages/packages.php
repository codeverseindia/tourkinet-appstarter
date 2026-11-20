<?php
declare(strict_types=1);

$title = 'Travel packages';
$description = 'Explore thoughtfully planned tours and travel packages.';
$items = api_items($packages ?? []);
$queryFilters = is_array($filters ?? null) ? $filters : [];
$meta = is_array($packages['meta'] ?? null) ? $packages['meta'] : [];
$apiFilters = is_array($packages['filters'] ?? null) ? $packages['filters'] : [];
$filterOptions = static function (mixed $values): array {
    if (!is_array($values)) {
        return [];
    }

    $options = [];
    foreach ($values as $option) {
        if (is_string($option) || is_int($option)) {
            $value = text_value($option);
            $label = $value;
        } elseif (is_array($option)) {
            $value = text_value($option['slug'] ?? $option['value'] ?? '');
            $label = text_value($option['name'] ?? $option['label'] ?? $value);
        } else {
            continue;
        }
        if ($value !== '' && $label !== '') {
            $options[] = ['value' => $value, 'label' => $label];
        }
    }

    return $options;
};
$destinationOptions = $filterOptions($apiFilters['destinations'] ?? []);
$themeOptions = $filterOptions($apiFilters['themes'] ?? $apiFilters['theme'] ?? []);
$typeOptions = $filterOptions($apiFilters['types'] ?? $apiFilters['tour_types'] ?? $apiFilters['package_types'] ?? []);
$resultCount = max(0, (int) ($meta['total'] ?? count($items)));
$activeFilters = array_filter(
    $queryFilters,
    static fn(mixed $value, string|int $key): bool => $key !== 'page'
        && (is_string($value) || is_int($value))
        && trim((string) $value) !== '',
    ARRAY_FILTER_USE_BOTH,
);
$advancedFilterKeys = ['duration_min', 'duration_max', 'is_featured'];
$activeAdvancedFilters = array_intersect_key($activeFilters, array_flip($advancedFilterKeys));
?>
<section class="package-browser py-4">
  <div class="container">
    <header class="package-browser__header">
      <div>
        <p class="eyebrow">Find your next trip</p>
        <h1 class="h2 fw-semibold mb-1">Travel packages</h1>
        <p class="package-browser__intro mb-0">Find the right journey by destination, style, or trip length.</p>
      </div>
      <p class="package-browser__count mb-0" aria-live="polite">
        <strong><?= e(number_format($resultCount)) ?></strong>
        <?= $resultCount === 1 ? 'package' : 'packages' ?>
      </p>
    </header>

    <form action="/packages" method="get" class="package-filters card mb-4" aria-label="Filter travel packages">
      <div class="card-body">
        <div class="package-filters__heading">
          <h2 class="h6 mb-0">Filter packages</h2>
          <?php if ($activeFilters !== []): ?>
            <span class="package-filters__active"><?= e(count($activeFilters)) ?> filters applied</span>
          <?php endif; ?>
        </div>
        <div class="row g-2 align-items-end">
          <div class="col-sm-6 col-xl-3">
            <label class="form-label" for="destination">Destination</label>
            <select class="form-select" id="destination" name="destination">
              <option value="">All destinations</option>
              <?php foreach ($destinationOptions as $option): ?>
                <option value="<?= e($option['value']) ?>" <?= ($queryFilters['destination'] ?? '') === $option['value'] ? 'selected' : '' ?>><?= e($option['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-6 col-xl-3">
            <label class="form-label" for="theme">Theme</label>
            <?php if ($themeOptions !== []): ?>
              <select class="form-select" id="theme" name="theme">
                <option value="">All themes</option>
                <?php foreach ($themeOptions as $option): ?>
                  <option value="<?= e($option['value']) ?>" <?= ($queryFilters['theme'] ?? '') === $option['value'] ? 'selected' : '' ?>><?= e($option['label']) ?></option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <input class="form-control" id="theme" name="theme" maxlength="120" placeholder="e.g. adventure" value="<?= e($queryFilters['theme'] ?? '') ?>">
            <?php endif; ?>
          </div>
          <div class="col-sm-6 col-xl-3">
            <label class="form-label" for="type">Tour type</label>
            <?php if ($typeOptions !== []): ?>
              <select class="form-select" id="type" name="type">
                <option value="">All tour types</option>
                <?php foreach ($typeOptions as $option): ?>
                  <option value="<?= e($option['value']) ?>" <?= ($queryFilters['type'] ?? '') === $option['value'] ? 'selected' : '' ?>><?= e($option['label']) ?></option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <input class="form-control" id="type" name="type" maxlength="120" placeholder="e.g. family" value="<?= e($queryFilters['type'] ?? '') ?>">
            <?php endif; ?>
          </div>
          <div class="col-sm-6 col-xl-3 package-filters__actions">
            <button class="btn btn-primary" type="submit">Apply filters</button>
            <?php if ($activeFilters !== []): ?>
              <a class="package-filters__clear" href="/packages">Clear</a>
            <?php endif; ?>
          </div>
        </div>

        <details class="package-filters__advanced" <?= $activeAdvancedFilters !== [] ? 'open' : '' ?>>
          <summary>
            <span>Advanced filters</span>
            <span class="package-filters__advanced-hint">Trip length and featured status</span>
            <?php if ($activeAdvancedFilters !== []): ?>
              <span class="package-filters__advanced-count"><?= e(count($activeAdvancedFilters)) ?> active</span>
            <?php endif; ?>
          </summary>
          <div class="row g-2 align-items-end pt-3">
            <div class="col-sm-6 col-lg-3">
              <label class="form-label" for="duration_min">Minimum days</label>
              <input class="form-control" type="number" id="duration_min" name="duration_min" min="1" max="9999" value="<?= e($queryFilters['duration_min'] ?? '') ?>">
            </div>
            <div class="col-sm-6 col-lg-3">
              <label class="form-label" for="duration_max">Maximum days</label>
              <input class="form-control" type="number" id="duration_max" name="duration_max" min="1" max="9999" value="<?= e($queryFilters['duration_max'] ?? '') ?>">
            </div>
            <div class="col-sm-6 col-lg-3">
              <label class="form-label" for="is_featured">Featured</label>
              <select class="form-select" id="is_featured" name="is_featured">
                <option value="">All packages</option>
                <option value="true" <?= in_array(($queryFilters['is_featured'] ?? ''), ['true', '1'], true) ? 'selected' : '' ?>>Featured only</option>
                <option value="false" <?= in_array(($queryFilters['is_featured'] ?? ''), ['false', '0'], true) ? 'selected' : '' ?>>Not featured</option>
              </select>
            </div>
          </div>
        </details>
      </div>
    </form>

    <?php if ($items === []): ?>
      <div class="package-browser__empty">
        <h2 class="h4">No packages found</h2>
        <p class="text-secondary">Try changing your filters or contact us for help planning a trip.</p>
        <a class="btn btn-outline-primary" href="<?= $activeFilters !== [] ? '/packages' : '/contact' ?>">
          <?= $activeFilters !== [] ? 'Clear filters' : 'Contact our team' ?>
        </a>
      </div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($items as $item): ?>
          <?php
          if (!is_array($item)) {
              continue;
          }
          $slug = valid_slug(text_value($item['slug'] ?? ''));
          if ($slug === null) {
              continue;
          }
          $name = text_value($item['title'] ?? $item['name'] ?? 'Travel package');
          $overview = text_value($item['overview'] ?? $item['short_description'] ?? '');
          $thumbnail = is_array($item['thumbnail'] ?? null)
              ? ($item['thumbnail']['url'] ?? null)
              : ($item['image'] ?? null);
          $duration = is_array($item['duration'] ?? null) ? $item['duration'] : [];
          $rawDestinations = is_array($item['destinations'] ?? null) ? $item['destinations'] : [];
          if (isset($rawDestinations['name']) || isset($rawDestinations['slug'])) {
              $rawDestinations = [$rawDestinations];
          }
          $destinationNames = [];
          $seenDestinations = [];
          foreach ($rawDestinations as $place) {
              if (is_array($place) && is_array($place['destination'] ?? null)) {
                  $place = $place['destination'];
              }
              $destinationName = is_array($place)
                  ? text_value($place['name'] ?? $place['title'] ?? $place['slug'] ?? '')
                  : (is_string($place) || is_int($place) ? text_value($place) : '');
              $destinationKey = strtolower(trim($destinationName));
              if ($destinationName !== '' && !isset($seenDestinations[$destinationKey])) {
                  $destinationNames[] = $destinationName;
                  $seenDestinations[$destinationKey] = true;
              }
          }
          $packageUrl = '/packages/' . rawurlencode($slug);
          $imageUrl = safe_image_url($thumbnail);
          ?>
          <article class="col-12 col-md-6 col-xl-4">
            <div class="card package-card h-100">
              <a class="package-card__media" href="<?= e($packageUrl) ?>" aria-label="View <?= e($name) ?>">
                <?php if ($imageUrl !== null): ?>
                  <img class="package-card__image" src="<?= e($imageUrl) ?>" width="640" height="400" loading="lazy" alt="<?= e($name) ?>">
                <?php else: ?>
                  <span class="package-card__placeholder" aria-hidden="true">Travel experience</span>
                <?php endif; ?>
                <?php if (!empty($item['is_featured'])): ?><span class="package-card__featured">Featured</span><?php endif; ?>
              </a>
              <div class="card-body package-card__body">
                <h2 class="package-card__title"><a href="<?= e($packageUrl) ?>"><?= e($name) ?></a></h2>
                <?php if ($overview !== ''): ?><p class="package-card__overview"><?= e($overview) ?></p><?php endif; ?>
                <?php if ($destinationNames !== []): ?>
                  <div class="package-card__destinations" aria-label="Destinations">
                    <?php foreach ($destinationNames as $destinationName): ?>
                      <span class="package-card__destination"><?= e($destinationName) ?></span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
                <div class="package-card__footer">
                  <span class="package-card__duration">
                    <?php if (!empty($duration['days'])): ?><?= e($duration['days']) ?> days<?php endif; ?>
                    <?php if (!empty($duration['nights'])): ?> · <?= e($duration['nights']) ?> nights<?php endif; ?>
                  </span>
                  <?php if (isset($item['starting_price'])): ?>
                    <span class="package-card__price">
                      <span class="visually-hidden">Starting from </span><?= e($item['currency'] ?? 'INR') ?> <?= e($item['starting_price']) ?>
                    </span>
                  <?php endif; ?>
                </div>
                <a class="btn btn-outline-primary package-card__link" href="<?= e($packageUrl) ?>">View package <span aria-hidden="true">→</span></a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <?php
      $lastPage = max(1, (int) ($meta['last_page'] ?? $queryFilters['page'] ?? 1));
      $currentPage = min($lastPage, max(1, (int) ($meta['current_page'] ?? $queryFilters['page'] ?? 1)));
      $firstPage = max(1, $currentPage - 2);
      $finalPage = min($lastPage, $currentPage + 2);
      ?>
      <?php if ($lastPage > 1): ?>
        <nav class="mt-5" aria-label="Package pages">
          <ul class="pagination justify-content-center">
            <?php for ($page = $firstPage; $page <= $finalPage; $page++): ?>
              <li class="page-item <?= $page === $currentPage ? 'active' : '' ?>">
                <a class="page-link" href="/packages?<?= e(http_build_query(array_merge($queryFilters, ['page' => $page]))) ?>" <?= $page === $currentPage ? 'aria-current="page"' : '' ?>><?= e($page) ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
