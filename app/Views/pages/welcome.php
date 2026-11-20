<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($appName) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.9/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-yEPZ7jP9S0QKiUQ0m3jjsO4ProIW7wvR9gXkI7JOGa2XVsw53Yke2bsdkBbjmmiX" crossorigin="anonymous">
</head>
<body>
  <main class="container min-vh-100 d-flex align-items-center justify-content-center py-5">
    <section class="text-center">
      <p class="text-uppercase text-secondary small fw-semibold mb-2">LightPHP Core</p>
      <h1 class="display-4 fw-bold">Welcome to <?= e($appName) ?></h1>
      <p class="lead text-secondary mt-3">Your application is ready. Start building your project.</p>
    </section>
  </main>
</body>
</html>
