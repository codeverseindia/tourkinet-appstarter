<?php
declare(strict_types=1);
$title = $title ?? 'Page not found';
$message = $message ?? 'The page you are looking for could not be found.';
?>
<section class="section"><div class="container prose"><p class="eyebrow">404</p><h1><?= e($title) ?></h1><p><?= e($message) ?></p><a class="button" href="/">Return home</a></div></section>
