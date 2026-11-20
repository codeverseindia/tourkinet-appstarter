<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$application = require $root . '/bootstrap/app.php';

use CodeverseIndia\LightPHP\Core\Request;

$tests = 0;
$failures = [];
$assert = static function (bool $condition, string $name) use (&$tests, &$failures): void {
    $tests++;
    if (!$condition) {
        $failures[] = $name;
    }
};

$home = $application->handle(new Request('GET', '/'));
$appName = (string) $application->config()->get('app.name', 'LightPHP App Starter');
$assert($home->status() === 200
    && str_contains($home->body(), 'Go a little further. Feel a lot more.')
    && str_contains($home->body(), 'Featured journeys')
    && !str_contains($home->body(), 'Welcome to ' . e($appName)), 'Travel App homepage renders instead of the starter welcome page');

$escaped = $application->view()->render('pages/welcome', ['appName' => '<script>alert(1)</script>']);
$assert(!str_contains($escaped, '<script>alert(1)</script>')
    && str_contains($escaped, '&lt;script&gt;alert(1)&lt;/script&gt;'), 'Welcome page escapes dynamic application names');

$blog = $application->view()->render('pages/blog-detail', [
    'blog' => [
        'slug' => 'reading-test',
        'title' => 'Reading <script>alert(1)</script>',
        'short_intro' => 'A story introduction.',
        'images' => [
            'banner' => ['url' => 'https://images.example.test/banner.jpg'],
            'thumbnail' => ['url' => 'https://images.example.test/thumbnail.jpg'],
        ],
        'content' => '<h2>First section</h2><h3>Subsection</h3><p>First paragraph.</p><p>Second paragraph with <strong>emphasis</strong>, <em>italics</em>, <u>underline</u>, <s>strike</s>, <mark>highlight</mark>, <sup>2</sup>, <sub>2</sub>, and <code>inline</code> code.</p><pre><code>const value = "safe";' . "\n" . 'console.log(value);</code></pre><blockquote><p>A quote.</p></blockquote><ul><li>Bullet.</li></ul><ol start="2"><li>Ordered.</li></ol><hr><table><colgroup><col><col></colgroup><thead><tr><th>Expense</th><th>Budget</th></tr></thead><tbody><tr><td>Hotel</td><td>INR 2,000</td></tr></tbody></table><p><a href="https://example.test/story">Safe link</a> <a href="javascript:alert(1)">Unsafe link</a> <a href="mailto:editor@example.test">Email</a></p><img src="https://images.example.test/content.jpg" alt="Content image"><img src="javascript:alert(1)" alt="Unsafe image"><script>alert(2)</script>',
        'published_at' => '2026-11-20T08:00:00+00:00',
        'category' => ['name' => 'Travel'],
        'author' => ['name' => 'Editor'],
        'tags' => [['name' => 'Topic']],
    ],
    'canonical' => 'https://example.test/blogs/reading-test',
]);
$assert(str_contains($blog, '<h2 class="blog-reading__section-title">First section</h2>')
    && str_contains($blog, '<h3 class="blog-reading__section-title">Subsection</h3>')
    && str_contains($blog, '<p>First paragraph.</p>')
    && str_contains($blog, '<strong>emphasis</strong>')
    && str_contains($blog, '<em>italics</em>')
    && str_contains($blog, '<u>underline</u>')
    && str_contains($blog, '<s>strike</s>')
    && str_contains($blog, '<mark>highlight</mark>')
    && str_contains($blog, '<sup>2</sup>')
    && str_contains($blog, '<sub>2</sub>')
    && str_contains($blog, '<code>inline</code>')
    && str_contains($blog, '<pre class="blog-content__pre"><code>const value = &quot;safe&quot;;')
    && str_contains($blog, 'console.log(value);</code></pre>')
    && str_contains($blog, '<blockquote class="blog-content__blockquote">')
    && str_contains($blog, '<ul><li>Bullet.</li></ul>')
    && str_contains($blog, '<ol start="2"><li>Ordered.</li></ol>')
    && str_contains($blog, '<hr>')
    && str_contains($blog, '<table class="blog-content__table">')
    && str_contains($blog, '<colgroup><col><col></colgroup>')
    && str_contains($blog, '<th>Expense</th>')
    && str_contains($blog, '<td>INR 2,000</td>')
    && str_contains($blog, 'href="https://example.test/story"')
    && str_contains($blog, 'href="mailto:editor@example.test"')
    && !str_contains($blog, 'href="javascript:')
    && str_contains($blog, 'src="https://images.example.test/content.jpg"')
    && !str_contains($blog, 'src="javascript:')
    && str_contains($blog, 'src="https://images.example.test/banner.jpg"')
    && !str_contains($blog, 'src="https://images.example.test/thumbnail.jpg"')
    && str_contains($blog, '&lt;script&gt;alert(1)&lt;/script&gt;')
    && !str_contains($blog, '<script>'), 'Blog reading page formats rich text into safe, readable blocks');

$contact = $application->view()->render('pages/contact', [
    'csrf' => 'contact-test-token',
    'package' => [],
]);
$assert(str_contains($contact, 'name="_csrf" value="contact-test-token"')
    && str_contains($contact, 'name="first_name"')
    && str_contains($contact, 'name="email"')
    && str_contains($contact, 'name="phone"')
    && str_contains($contact, 'name="message"')
    && !str_contains($contact, 'name="destination"')
    && !str_contains($contact, 'name="travel_start_date"')
    && !str_contains($contact, 'name="estimated_budget"'), 'Contact form keeps only concise contact and message fields');

$packages = $application->view()->render('pages/packages', [
    'packages' => [
        'data' => [[
            'slug' => 'sikkim-highlights',
            'title' => 'Sikkim Highlights',
            'overview' => 'A varied trip across the eastern Himalayas.',
            'destinations' => [
                ['slug' => 'sikkim', 'name' => 'Sikkim'],
                ['slug' => 'darjeeling', 'name' => 'Darjeeling'],
                ['name' => 'sikkim'],
            ],
            'duration' => ['days' => 7, 'nights' => 6],
            'starting_price' => '34900.00',
            'currency' => 'INR',
            'is_featured' => true,
        ]],
        'meta' => ['total' => 1, 'current_page' => 1, 'last_page' => 1],
        'filters' => [
            'destinations' => [['slug' => 'sikkim', 'name' => 'Sikkim']],
            'themes' => [['slug' => 'adventure', 'name' => 'Adventure']],
            'types' => [['slug' => 'family', 'name' => 'Family']],
        ],
    ],
    'filters' => [
        'destination' => 'sikkim',
        'theme' => 'adventure',
        'type' => 'family',
        'duration_min' => '5',
    ],
]);
$assert(str_contains($packages, '1</strong>')
    && str_contains($packages, '<option value="sikkim" selected>Sikkim</option>')
    && str_contains($packages, '<option value="adventure" selected>Adventure</option>')
    && str_contains($packages, '<option value="family" selected>Family</option>')
    && str_contains($packages, '<details class="package-filters__advanced" open>')
    && str_contains($packages, 'value="5"')
    && str_contains($packages, 'Advanced filters')
    && str_contains($packages, 'Sikkim Highlights')
    && str_contains($packages, 'Sikkim</span>')
    && str_contains($packages, 'Darjeeling</span>')
    && str_contains($packages, '34900.00')
    && str_contains($packages, 'Featured'), 'Package browser displays results and all unique returned destinations');
$packagesWithoutOptionLists = $application->view()->render('pages/packages', [
    'packages' => ['data' => [], 'filters' => []],
    'filters' => ['theme' => 'adventure', 'type' => 'family'],
]);
$assert(str_contains($packagesWithoutOptionLists, 'id="theme" name="theme"')
    && str_contains($packagesWithoutOptionLists, 'value="adventure"')
    && str_contains($packagesWithoutOptionLists, 'id="type" name="type"')
    && str_contains($packagesWithoutOptionLists, 'value="family"'), 'Package theme and tour type remain editable when the API provides no option lists');

$packageDetail = $application->view()->render('pages/package-detail', [
    'csrf' => 'package-test-token',
    'package' => [
        'package' => [
            'slug' => 'faq-test',
            'title' => 'FAQ Test Package',
            'package_hash' => 'pkg_test',
            'package_code' => 'TEST-001',
            'destinations' => [['name' => 'Darjeeling'], ['name' => 'Kathmandu']],
        ],
        'itinerary' => [['day' => 1, 'title' => 'Arrival', 'description' => 'Meet your guide.']],
        'images' => ['gallery' => [['url' => 'https://images.example.test/package-gallery.jpg', 'caption' => 'Mountain view']]],
        'inclusions' => ['Breakfast'],
        'exclusions' => ['Flights'],
        'terms_conditions' => [['title' => 'Booking', 'description' => 'Supplier confirmation is required.']],
        'destination_faqs' => [
            [
                'question' => 'When is the best time to visit?',
                'answer' => 'March to May.',
                'destination' => ['name' => 'Darjeeling'],
                'location' => ['name' => 'Tiger Hill'],
            ],
            [
                'question' => 'Unsafe <script>alert(1)</script> question',
                'answer' => 'Safe & escaped answer.',
                'destination' => ['name' => 'Kathmandu'],
                'location' => null,
            ],
        ],
    ],
]);
$packageAsideStart = strpos($packageDetail, '<aside');
$packageAsideEnd = $packageAsideStart === false ? false : strpos($packageDetail, '</aside>', $packageAsideStart);
$packageInquiryPosition = strpos($packageDetail, 'id="package-inquiry"');
$assert(str_contains($packageDetail, '<nav class="package-detail-nav" aria-label="On this page">')
    && str_contains($packageDetail, 'href="#package-itinerary"')
    && str_contains($packageDetail, 'href="#package-faq-heading"')
    && !str_contains($packageDetail, 'data-bs-toggle="collapse" data-bs-target="#package-group-content-'), 'Package details expose in-page section navigation without parent accordion controls');
$assert(str_contains($packageDetail, 'id="package-itinerary"')
    && str_contains($packageDetail, 'id="package-group-itinerary"')
    && str_contains($packageDetail, 'class="mt-5 package-detail-group package-detail-group--gallery"')
    && str_contains($packageDetail, 'class="mt-5 package-detail-group package-detail-group--included"')
    && str_contains($packageDetail, 'class="mt-5 package-detail-group package-detail-group--excluded"')
    && str_contains($packageDetail, 'class="mt-5 package-detail-group package-detail-group--terms"')
    && str_contains($packageDetail, 'href="#package-included"')
    && str_contains($packageDetail, 'href="#package-terms"')
    && str_contains($packageDetail, '>Terms and conditions</h2>')
    && str_contains($packageDetail, 'class="package-detail-group__body"')
    && !str_contains($packageDetail, 'package-group-content-itinerary'), 'Package detail groups render with themed, always-visible sections');
$assert($packageAsideStart !== false
    && $packageAsideEnd !== false
    && $packageInquiryPosition !== false
    && $packageInquiryPosition > $packageAsideStart
    && $packageInquiryPosition < $packageAsideEnd
    && str_contains($packageDetail, 'class="package-inquiry__form"')
    && str_contains($packageDetail, 'name="_csrf" value="package-test-token"')
    && str_contains($packageDetail, 'name="package_hash" value="pkg_test"')
    && str_contains($packageDetail, 'name="package_code" value="TEST-001"')
    && str_contains($packageDetail, 'name="inquiry_type" value="package"'), 'Package inquiry remains in the sidebar with CSRF and package identifiers');
$assert(str_contains($packageDetail, 'Frequently asked questions')
    && str_contains($packageDetail, '>Darjeeling</h3>')
    && str_contains($packageDetail, '>Kathmandu</h3>')
    && str_contains($packageDetail, 'id="package-faqs-0"')
    && str_contains($packageDetail, 'id="package-faqs-1"')
    && str_contains($packageDetail, 'class="mt-5 package-detail-group package-detail-group--faqs"')
    && str_contains($packageDetail, 'data-bs-parent="#package-faqs-0"')
    && !str_contains($packageDetail, 'package-group-content-faqs')
    && str_contains($packageDetail, 'When is the best time to visit?')
    && str_contains($packageDetail, '<p class="small text-secondary">Tiger Hill</p>')
    && str_contains($packageDetail, 'March to May.')
    && str_contains($packageDetail, 'Unsafe &lt;script&gt;alert(1)&lt;/script&gt; question')
    && str_contains($packageDetail, 'Safe &amp; escaped answer.')
    && !str_contains($packageDetail, '<script>alert(1)</script>'), 'Package FAQs remain grouped by destination and safely escaped');
$packageWithoutFaqs = $application->view()->render('pages/package-detail', [
    'csrf' => 'no-faq-token',
    'package' => [
        'package' => ['slug' => 'faq-test', 'title' => 'FAQ Test Package'],
        'destination_faqs' => [['question' => '', 'answer' => '']],
    ],
]);
$assert(!str_contains($packageWithoutFaqs, 'Frequently asked questions')
    && !str_contains($packageWithoutFaqs, 'package-detail-nav'), 'Package detail omits FAQs and section navigation when none are available');

$about = $application->handle(new Request('GET', '/about'));
$assert($about->status() === 200 && str_contains($about->body(), 'About us'), 'Travel App page routes render package templates');

$routes = $application->router()->all();
$registeredPaths = array_column($routes, 'uri');
$assert(in_array('/packages', $registeredPaths, true)
    && in_array('/blogs', $registeredPaths, true)
    && in_array('/contact', $registeredPaths, true)
    && in_array('/sitemap.xml', $registeredPaths, true), 'Travel App routes are registered');
$assert(count(array_filter($routes, static fn(array $route): bool => $route['uri'] === '/' && $route['method'] === 'GET')) === 1,
    'Travel App homepage route is registered once');
$imageSources = $application->config()->get('app.security_headers.img-src', []);
$localImageAllowed = is_array($imageSources) && in_array('http://app.tourkinet.loc', $imageSources, true);
$assert(($application->config()->get('app.env') === 'local') === $localImageAllowed,
    'Local Tour Kinet image host is allowed only in local mode');

echo "{$tests} starter tests, " . count($failures) . " failures\n";
foreach ($failures as $failure) {
    fwrite(STDERR, "FAIL: {$failure}\n");
}
exit($failures === [] ? 0 : 1);
