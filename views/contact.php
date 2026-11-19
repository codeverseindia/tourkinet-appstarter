<?php
declare(strict_types=1);
$title = 'Contact us';
$selectedPackageName = text_value($package['name'] ?? $package['title'] ?? '');
?>
<section class="py-5">
  <div class="container">
    <div class="row g-5">
      <div class="col-lg-5">
        <p class="eyebrow">Let's plan something</p>
        <h1 class="display-5 fw-semibold">Tell us about your trip.</h1>
        <p class="lead">Share a few details and our team will be in touch.</p>
        <?php if ($selectedPackageName !== ''): ?>
          <div class="card mt-4"><div class="card-body">
            <p class="eyebrow mb-2">Selected package</p>
            <h2 class="h5 mb-0"><?= e($selectedPackageName) ?></h2>
          </div></div>
        <?php endif; ?>
      </div>
      <div class="col-lg-7">
        <?php if (isset($_GET['sent'])): ?><p class="alert alert-success" role="status">Thanks for reaching out. We have received your inquiry.</p><?php endif; ?>
        <?php foreach (['form', '_csrf', 'contact', 'package'] as $errorKey): ?>
          <?php if (isset($errors[$errorKey])): ?><p class="alert alert-danger" role="alert"><?= e($errors[$errorKey]) ?></p><?php endif; ?>
        <?php endforeach; ?>
        <form action="/contact" method="post">
          <input type="hidden" name="_csrf" value="<?= e($csrf ?? '') ?>">
          <input type="hidden" name="package_slug" value="<?= e($package_slug ?? '') ?>">
          <div class="honeypot" aria-hidden="true"><label>Leave this field empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="first_name">First name</label>
              <input class="form-control" id="first_name" name="first_name" required minlength="2" maxlength="100" autocomplete="given-name" value="<?= e($old['first_name'] ?? '') ?>">
              <?php if (isset($errors['first_name'])): ?><div class="text-danger small mt-1"><?= e($errors['first_name']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="last_name">Last name</label>
              <input class="form-control" id="last_name" name="last_name" maxlength="100" autocomplete="family-name" value="<?= e($old['last_name'] ?? '') ?>">
              <?php if (isset($errors['last_name'])): ?><div class="text-danger small mt-1"><?= e($errors['last_name']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="email">Email</label>
              <input class="form-control" type="email" id="email" name="email" maxlength="254" autocomplete="email" value="<?= e($old['email'] ?? '') ?>">
              <?php if (isset($errors['email'])): ?><div class="text-danger small mt-1"><?= e($errors['email']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="phone">Phone</label>
              <input class="form-control" type="tel" id="phone" name="phone" maxlength="30" autocomplete="tel" value="<?= e($old['phone'] ?? '') ?>">
              <?php if (isset($errors['phone'])): ?><div class="text-danger small mt-1"><?= e($errors['phone']) ?></div><?php endif; ?>
            </div>
            <div class="col-12">
              <label class="form-label" for="destination">Preferred destination</label>
              <input class="form-control" id="destination" name="destination" maxlength="120" value="<?= e($old['destination'] ?? '') ?>">
              <?php if (isset($errors['destination'])): ?><div class="text-danger small mt-1"><?= e($errors['destination']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="travel_start_date">Travel start date</label>
              <input class="form-control" type="date" id="travel_start_date" name="travel_start_date" value="<?= e($old['travel_start_date'] ?? '') ?>">
              <?php if (isset($errors['travel_start_date'])): ?><div class="text-danger small mt-1"><?= e($errors['travel_start_date']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="travel_end_date">Travel end date</label>
              <input class="form-control" type="date" id="travel_end_date" name="travel_end_date" value="<?= e($old['travel_end_date'] ?? '') ?>">
              <?php if (isset($errors['travel_end_date'])): ?><div class="text-danger small mt-1"><?= e($errors['travel_end_date']) ?></div><?php endif; ?>
            </div>
            <div class="col-sm-4">
              <label class="form-label" for="adults">Adults</label>
              <input class="form-control" type="number" id="adults" name="adults" min="1" max="50" step="1" value="<?= e($old['adults'] ?? '1') ?>">
              <?php if (isset($errors['adults'])): ?><div class="text-danger small mt-1"><?= e($errors['adults']) ?></div><?php endif; ?>
            </div>
            <div class="col-sm-4">
              <label class="form-label" for="children">Children</label>
              <input class="form-control" type="number" id="children" name="children" min="0" max="50" step="1" value="<?= e($old['children'] ?? '0') ?>">
              <?php if (isset($errors['children'])): ?><div class="text-danger small mt-1"><?= e($errors['children']) ?></div><?php endif; ?>
            </div>
            <div class="col-sm-4">
              <label class="form-label" for="infants">Infants</label>
              <input class="form-control" type="number" id="infants" name="infants" min="0" max="50" step="1" value="<?= e($old['infants'] ?? '0') ?>">
              <?php if (isset($errors['infants'])): ?><div class="text-danger small mt-1"><?= e($errors['infants']) ?></div><?php endif; ?>
            </div>
            <div class="col-12">
              <label class="form-label" for="estimated_budget">Estimated budget</label>
              <input class="form-control" type="number" id="estimated_budget" name="estimated_budget" min="0" step="0.01" value="<?= e($old['estimated_budget'] ?? '') ?>">
              <?php if (isset($errors['estimated_budget'])): ?><div class="text-danger small mt-1"><?= e($errors['estimated_budget']) ?></div><?php endif; ?>
            </div>
            <div class="col-12">
              <label class="form-label" for="message">Message</label>
              <textarea class="form-control" id="message" name="message" maxlength="5000" rows="5"><?= e($old['message'] ?? '') ?></textarea>
              <?php if (isset($errors['message'])): ?><div class="text-danger small mt-1"><?= e($errors['message']) ?></div><?php endif; ?>
            </div>
            <?php if ((string) env('TURNSTILE_SITE_KEY', '') !== ''): ?>
              <div class="col-12"><div class="cf-turnstile" data-sitekey="<?= e(env('TURNSTILE_SITE_KEY')) ?>"></div><script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script></div>
            <?php endif; ?>
            <div class="col-12"><button class="btn btn-primary btn-lg" type="submit">Send inquiry</button></div>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
