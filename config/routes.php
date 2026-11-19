<?php
declare(strict_types=1);

get('/', 'home');
get('/about', 'about');
get('/destinations', 'destinations');
get('/packages', 'packages');
get('/packages/{slug}', 'package-detail');
get('/blogs', 'blogs');
get('/blogs/{slug}', 'blog-detail');
get('/contact', 'contact');
post('/contact', 'submit-inquiry');
get('/privacy', 'privacy');
get('/sitemap.xml', 'sitemap');
get('/robots.txt', 'robots');
get('/theme.css', 'theme');
