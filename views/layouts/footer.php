</main>
<footer class="site-footer">
  <div class="container footer-row">
    <span>&copy; <?= date('Y') ?> <?= e($website['company_name'] ?? config('website.company_name')) ?></span>
    <span><a href="/privacy">Privacy</a> &middot; <a href="mailto:<?= e($website['email'] ?? '') ?>">Email us</a></span>
  </div>
</footer>
</body>
</html>
