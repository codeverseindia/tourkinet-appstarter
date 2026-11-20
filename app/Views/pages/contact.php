<?php
declare(strict_types=1);

$package = is_array($package ?? null) ? $package : [];
$packageHash = text_value($package['package_hash'] ?? '');
$packageCode = text_value($package['package_code'] ?? '');
$packageName = text_value($package['name'] ?? $package['title'] ?? '');
$packageSelected = $packageHash !== '' || $packageCode !== '' || $packageName !== '';
$old = is_array($old ?? null) ? $old : [];
$errors = is_array($errors ?? null) ? $errors : [];
$attribution = is_array($attribution ?? null) ? $attribution : [];
$turnstileSiteKey = is_string($turnstileSiteKey ?? null) ? $turnstileSiteKey : '';
?>
<section class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8 col-xl-7">
        <p class="eyebrow mb-2">Let's plan something</p>
        <h1 class="display-5 fw-semibold"><?= $packageSelected ? 'Ask about this package' : 'Get in touch' ?></h1>
        <p class="lead text-secondary mb-4"><?= $packageSelected ? 'Leave your contact details and our team will follow up.' : 'Tell us how to reach you and what you have in mind.' ?></p>
        <?php if (!empty($sent)): ?><p class="alert alert-success" role="status">Thanks for reaching out. We have received your inquiry.</p><?php endif; ?>
        <?php foreach (['form', '_csrf', 'contact_method', 'inquiry_type', 'package_name'] as $errorKey): ?>
          <?php if (isset($errors[$errorKey])): ?><p class="alert alert-danger" role="alert"><?= e($errors[$errorKey]) ?></p><?php endif; ?>
        <?php endforeach; ?>

        <form action="/contact" method="post" class="card shadow-sm">
          <div class="card-body p-4">
            <input type="hidden" name="_csrf" value="<?= e($csrf ?? '') ?>">
            <input type="hidden" name="inquiry_type" value="<?= $packageSelected ? 'package' : 'regular' ?>">
            <div class="honeypot" aria-hidden="true"><label>Leave this field empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
            <?php if ($packageSelected): ?>
              <input type="hidden" name="package_hash" value="<?= e($packageHash) ?>">
              <input type="hidden" name="package_code" value="<?= e($packageCode) ?>">
              <input type="hidden" name="package_name" value="<?= e($packageName) ?>">
              <p class="alert alert-light border">Package: <strong><?= e($packageName !== '' ? $packageName : ($packageCode !== '' ? $packageCode : $packageHash)) ?></strong></p>
            <?php endif; ?>

            <div class="mb-3">
              <label class="form-label" for="first_name">Your name</label>
              <input class="form-control" id="first_name" name="first_name" maxlength="100" autocomplete="name" value="<?= e($old['first_name'] ?? '') ?>">
              <?php if (isset($errors['first_name'])): ?><div class="invalid-feedback d-block"><?= e($errors['first_name']) ?></div><?php endif; ?>
            </div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="email">Email</label>
                <input class="form-control" type="email" id="email" name="email" maxlength="254" autocomplete="email" value="<?= e($old['email'] ?? '') ?>">
                <?php if (isset($errors['email'])): ?><div class="invalid-feedback d-block"><?= e($errors['email']) ?></div><?php endif; ?>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="phone">Phone</label>
                <input class="form-control" type="tel" id="phone" name="phone" maxlength="30" autocomplete="tel" value="<?= e($old['phone'] ?? '') ?>">
                <?php if (isset($errors['phone'])): ?><div class="invalid-feedback d-block"><?= e($errors['phone']) ?></div><?php endif; ?>
              </div>
              <div class="col-12 form-text">Please provide an email address or phone number so we can reply.</div>
              <div class="col-12">
                <label class="form-label" for="message">Message <span class="text-secondary">(optional)</span></label>
                <textarea class="form-control" id="message" name="message" maxlength="5000" rows="4"><?= e($old['message'] ?? '') ?></textarea>
                <?php if (isset($errors['message'])): ?><div class="invalid-feedback d-block"><?= e($errors['message']) ?></div><?php endif; ?>
              </div>
              <?php foreach (['utm_source', 'utm_medium', 'utm_campaign'] as $field): ?>
                <input type="hidden" name="<?= e($field) ?>" value="<?= e($old[$field] ?? $attribution[$field] ?? '') ?>">
              <?php endforeach; ?>
              <?php if ($turnstileSiteKey !== ''): ?>
                <div class="col-12"><div class="cf-turnstile" data-sitekey="<?= e($turnstileSiteKey) ?>"></div><script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script></div>
              <?php endif; ?>
              <div class="col-12"><button class="btn btn-primary" type="submit">Send inquiry</button></div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
