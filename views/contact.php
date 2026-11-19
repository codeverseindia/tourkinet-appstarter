<?php
declare(strict_types=1);
$title = 'Contact us';
?>
<section class="section"><div class="container">
  <div class="prose"><p class="eyebrow">Let's plan something</p><h1>Tell us about your trip.</h1><p>Share a few details and our team will be in touch.</p></div>
  <?php if (isset($_GET['sent'])): ?><p class="success" role="status">Thanks for reaching out. We have received your inquiry.</p><?php endif; ?>
  <?php if (isset($errors['form']) || isset($errors['_csrf'])): ?><p class="error" role="alert"><?= e($errors['form'] ?? $errors['_csrf']) ?></p><?php endif; ?>
  <form class="form" action="/contact" method="post">
    <input type="hidden" name="_csrf" value="<?= e($csrf ?? '') ?>">
    <div class="honeypot" aria-hidden="true"><label>Leave this field empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
    <label>Name <input name="name" required minlength="2" maxlength="100" autocomplete="name" value="<?= e($old['name'] ?? '') ?>"><?php if (isset($errors['name'])): ?><span class="error"><?= e($errors['name']) ?></span><?php endif; ?></label>
    <label>Email <input type="email" name="email" required maxlength="254" autocomplete="email" value="<?= e($old['email'] ?? '') ?>"><?php if (isset($errors['email'])): ?><span class="error"><?= e($errors['email']) ?></span><?php endif; ?></label>
    <label>Phone <input type="tel" name="phone" required maxlength="30" autocomplete="tel" value="<?= e($old['phone'] ?? '') ?>"><?php if (isset($errors['phone'])): ?><span class="error"><?= e($errors['phone']) ?></span><?php endif; ?></label>
    <label>How can we help? <textarea name="message" required minlength="10" maxlength="5000"><?= e($old['message'] ?? '') ?></textarea><?php if (isset($errors['message'])): ?><span class="error"><?= e($errors['message']) ?></span><?php endif; ?></label>
    <?php if ((string) env('TURNSTILE_SITE_KEY', '') !== ''): ?><div class="cf-turnstile" data-sitekey="<?= e(env('TURNSTILE_SITE_KEY')) ?>"></div><script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script><?php endif; ?>
    <button class="button" type="submit">Send inquiry</button>
  </form>
</div></section>
