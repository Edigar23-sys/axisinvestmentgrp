<?php
declare(strict_types=1);

const SERVICES = [
    'investment-advisory' => ['Investment Advisory', 'Assess opportunities, clarify investment priorities and prepare for conversations with capital partners.'],
    'business-consulting' => ['Business Management & Consulting', 'Build growth plans, strengthen operations and turn strategy into work your team can deliver.'],
    'funding-facilitation' => ['Funding Facilitation', 'Prepare your proposition and supporting materials, and identify capital partners aligned with your goals.'],
    'business-development' => ['Business Development & Policy Lobbying', 'Identify routes to market, build strategic partnerships and engage relevant industry and policy stakeholders.'],
];
const SECTORS = [
    ['Technology & AI', 'Digital transformation, AI and robotics, data centres, internet access and software services.'],
    ['Financial & Capital Markets', 'IPOs, trading, foreign exchange, digital assets, funds and financial services advisory.'],
    ['Media, Entertainment & Advertising', 'Content, events, nightlife, advertising, video on demand, free ad-supported streaming and online media.'],
    ['Publishing', 'Digital magazines, editorial content and publishing platforms and services for institutions.'],
    ['Mining', 'Exploration, mineral processing, critical minerals, advisory and project capital.'],
    ['Food Security', 'Agricultural development, food processing, value addition and water security.'],
    ['Energy', 'Renewables, solar, rural electrification, energy financing and digital distribution.'],
];
const MAIN_LINKS = [
    '/about' => 'About', '/advisory' => 'Advisory', '/sectors' => 'Sectors',
    '/projects' => 'Projects', '/products' => 'Products', '/portfolio' => 'Portfolio', '/insights' => 'Insights',
];

function button(string $path, string $label, string $style = 'primary'): void
{
    echo '<a class="button ' . e($style) . '" href="' . e($path) . '">' . e($label) . '</a>';
}

function layout(string $title, callable $content, string $description = 'Axis Investment Group connects capital with Africa’s most promising ventures.', bool $private = false): void
{
    $path = rtrim(explode('?', $_SERVER['REQUEST_URI'] ?? '/', 2)[0], '/') ?: '/';
    $main = $path === '/' ? 'Axis Investment Group | Investing for an Impact' : $title . ' | Axis Investment Group';
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($main) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <?php if ($private): ?><meta name="robots" content="noindex,nofollow"><?php endif; ?>
    <meta name="theme-color" content="#101b2b">
    <link rel="icon" href="/assets/axis-icon.png" type="image/png">
    <link rel="stylesheet" href="/assets/style.css">
    <script src="/assets/site.js" defer></script>
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<header class="site-header">
    <div class="shell header-inner">
        <a class="brand" href="/" aria-label="Axis Investment Group home"><img src="/assets/axis-logo.png" width="580" height="329" alt=""></a>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-nav">Menu <span aria-hidden="true">☰</span></button>
        <nav id="site-nav" class="main-nav" aria-label="Main navigation">
            <?php foreach (MAIN_LINKS as $url => $label): ?>
            <a href="<?= e($url) ?>"<?= $path === $url || str_starts_with($path, $url . '/') ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
            <a class="login-link" href="/investors">Investor login</a>
            <?php button('/investors/apply', 'Invest Now'); ?>
        </nav>
    </div>
</header>
<main id="main"><?php $content(); ?></main>
<footer class="site-footer">
    <div class="shell">
        <div class="footer-grid">
            <div><a class="footer-brand" href="/"><img src="/assets/axis-logo.png" width="580" height="329" alt="Axis Investment Group"></a><p>Connecting capital with Africa’s most promising ventures.</p></div>
            <div><h2>Explore Axis</h2><a href="/about">About us</a><a href="/advisory">Advisory</a><a href="/sectors">Sectors</a><a href="/portfolio">Portfolio</a><a href="/insights">Insights & resources</a></div>
            <div><h2>Invest with Axis</h2><a href="/projects">Browse projects</a><a href="/products">Investment products</a><a href="/products/sacco">For SACCOs</a><a href="/investors">Investor login</a><?php button('/investors/apply', 'Invest Now'); ?></div>
            <div><h2>Contact</h2><a href="mailto:info@axisinvestmentgrp.com">info@axisinvestmentgrp.com</a><a href="tel:+256776494472">+256 776 494 472</a><p>P.O. Box 194269<br>Kampala, Uganda</p></div>
        </div>
        <div class="footer-base"><small>&copy; <?= gmdate('Y') ?> Axis Investment Group</small><a href="/privacy">Privacy</a></div>
    </div>
</footer>
</body></html>
    <?php
}

function pageHead(string $title, string $intro, string $tag = ''): void
{
    ?>
    <section class="page-head"><div class="shell">
        <?php if ($tag): ?><p class="eyebrow"><?= e($tag) ?></p><?php endif; ?>
        <h1><?= e($title) ?></h1>
        <p class="lead"><?= e($intro) ?></p>
    </div></section>
    <?php
}

function callout(string $heading, string $copy, string $path, string $label): void
{
    ?><section class="callout"><div class="shell callout-inner"><div><h2><?= e($heading) ?></h2><p><?= e($copy) ?></p></div><?php button($path, $label); ?></div></section><?php
}

function assetUrl(int|string $id): string
{
    return '/files/' . (int) $id;
}

function cards(array $items, string $kind): void
{
    ?><div class="card-grid"><?php foreach ($items as $item): ?>
        <article class="content-card">
            <a href="/<?= e($kind) ?>/<?= e($item['slug']) ?>">
                <?php if ($item['image_id']): ?><img class="card-image" src="<?= assetUrl($item['image_id']) ?>" alt="" loading="lazy"><?php endif; ?>
                <div class="card-copy"><p class="eyebrow"><?= e($item['category']) ?></p><h3><?= e($item['title']) ?></h3><p><?= e($item['summary']) ?></p></div>
            </a>
        </article>
    <?php endforeach; ?></div><?php
}

function home(): void
{
    $insights = published('insights', '', 3);
    layout('Home', function () use ($insights) { ?>
    <section class="hero">
        <div class="shell hero-inner">
            <p class="eyebrow">Africa-focused investment & advisory</p>
            <h1>Investing for<br>an <em>Impact.</em></h1>
            <p class="hero-copy">Connecting capital with Africa’s most promising ventures.</p>
            <div class="actions"><?php button('/investors/apply', 'Invest Now'); ?><?php button('/projects', 'Browse Projects', 'outline-light'); ?></div>
        </div>
    </section>
    <section class="section shell split">
        <div class="intro"><p class="eyebrow">What we do</p><h2>Capital with purpose.<br>Advice with direction.</h2><p>We help African businesses prepare for growth and connect investors with opportunities across seven sectors.</p><a class="text-link" href="/about">Get to know Axis</a>
            <img class="editorial-photo advisory-photo" src="/assets/advisory-meeting.jpg" width="1200" height="800" alt="Business advisers reviewing a plan together around a conference table" loading="lazy" decoding="async">
        </div>
        <div class="service-list"><?php foreach (SERVICES as $key => [$name, $description]): ?>
            <a href="/advisory#<?= e($key) ?>"><div><h3><?= e($name) ?></h3><p><?= e($description) ?></p></div><span aria-hidden="true">+</span></a>
        <?php endforeach; ?></div>
    </section>
    <section class="section tinted"><div class="shell">
        <div class="section-heading"><div><p class="eyebrow">Where we focus</p><h2>Seven sectors for African growth</h2></div><a class="text-link" href="/sectors">Explore all sectors</a></div>
        <div class="sector-feature">
            <img class="editorial-photo sector-photo" src="/assets/solar-infrastructure.jpg" width="1200" height="800" alt="Engineers walking between rows of solar panels in a green landscape" loading="lazy" decoding="async">
            <div class="sector-grid"><?php foreach (SECTORS as $index => [$name, $description]): ?>
                <a href="/sectors#sector-<?= $index + 1 ?>"><h3><?= e($name) ?></h3><p><?= e($description) ?></p></a>
            <?php endforeach; ?></div>
        </div>
    </div></section>
    <section class="section shell split network-feature">
        <img class="editorial-photo network-photo" src="/assets/east-africa-port.jpg" width="1200" height="800" alt="Aerial view of a coastal container terminal with cargo cranes" loading="lazy" decoding="async">
        <div>
            <div class="intro"><p class="eyebrow">Global capital network</p><h2>Connecting Africa with the world’s capital markets</h2><p>We seek to bring international investors, development finance institutions and local capital pools into conversations about African growth.</p><a class="text-link" href="/about#capital-network">Explore our capital network</a></div>
            <div class="network-links">
                <a href="/investors/apply?type=institutional"><h3>Institutional investors</h3><span>Discuss African investment opportunities</span></a>
                <a href="/investors/apply?type=individual"><h3>Individual investors</h3><span>Tell us what you’re looking for</span></a>
                <a href="/products/sacco"><h3>SACCOs & local capital pools</h3><span>Explore capital mobilization options</span></a>
            </div>
        </div>
    </section>
    <?php if ($insights): ?>
    <section class="section tinted"><div class="shell"><div class="section-heading"><h2>Latest insights</h2><a class="text-link" href="/insights">See all insights</a></div><?php cards($insights, 'insights'); ?></div></section>
    <?php endif; ?>
    <?php callout('Invest with Axis', 'Tell us about your investment goals or the business you want to grow.', '/investors/apply', 'Invest Now'); ?>
    <?php });
}

function about(): void
{
    layout('About', function () { 
        pageHead('Investing for an Impact.', 'We work to unlock Africa’s potential through strategic investment that supports growth, innovation and inclusive prosperity.', 'About Axis');
        ?>
        <section class="shell section split">
            <div><h2>Our mission</h2><p>We help mobilize capital and create long-term value in the sectors where we work. Our ambition is to make impact-driven investing a standard for how success is measured across African markets.</p></div>
            <div><h2>Our values</h2><dl class="values">
                <div><dt>Integrity</dt><dd>Do business the right way.</dd></div>
                <div><dt>Innovation</dt><dd>Mobilize and apply capital to address African challenges.</dd></div>
                <div><dt>Impact</dt><dd>Create value that uplifts communities.</dd></div>
                <div><dt>Partnership</dt><dd>Connect Africa with the world.</dd></div>
            </dl></div>
        </section>
        <section class="section tinted" id="capital-network"><div class="shell">
            <div class="split network-summary">
                <div class="intro"><h2>Global capital network</h2><p>Our outreach focuses on strategic investors, private equity firms and development finance institutions across international markets.</p></div>
                <img class="editorial-photo" src="/assets/east-africa-port.jpg" width="1200" height="800" alt="Aerial view of a coastal container terminal with cargo cranes" loading="lazy" decoding="async">
            </div>
            <div class="sector-grid region-grid">
                <div><h3>Asia</h3><p>Japan, South Korea, Thailand, Singapore, Taiwan, Hong Kong and Malaysia.</p></div>
                <div><h3>Middle East</h3><p>Saudi Arabia, Qatar and Oman.</p></div>
                <div><h3>Europe</h3><p>United Kingdom, Switzerland, Croatia, Malta, Spain, Sweden and Luxembourg.</p></div>
                <div><h3>South America</h3><p>We are exploring relationships with investors in the region.</p></div>
            </div>
            <p class="market-cities">Markets of interest · Nairobi, Johannesburg, Kampala, Dubai, London and Hong Kong</p>
        </div></section>
        <section class="section shell split"><h2>Mobilizing local capital</h2><div><p>In Uganda, we aim to work with SACCOs, microfinance institutions and other local capital pools on ways to finance SMEs, startups and technology ventures, and to explore relevant capital market opportunities.</p><a class="text-link" href="/products/sacco">Explore the SACCO pathway</a></div></section>
        <?php callout('Partner with Axis', 'Start a conversation about your priorities.', '/advisory#enquire', 'Contact our team');
    });
}

function sectors(): void
{
    layout('Sectors', function () {
        pageHead('Where we focus', 'Explore the seven sectors that guide our investment and advisory work.');
        ?><section class="section shell">
            <img class="editorial-photo sector-banner" src="/assets/solar-infrastructure.jpg" width="1200" height="800" alt="Engineers walking between rows of solar panels in a green landscape" decoding="async">
            <div class="sector-grid"><?php foreach (SECTORS as $index => [$name, $description]): ?>
                <div id="sector-<?= $index + 1 ?>"><h2><?= e($name) ?></h2><p><?= e($description) ?></p><a class="text-link" href="/projects?category=<?= rawurlencode($name) ?>">Browse related projects</a></div>
            <?php endforeach; ?></div>
        </section><?php
    });
}

function listing(string $kind): void
{
    [$title, $categories] = COLLECTIONS[$kind];
    $introductions = [
        'projects' => 'Browse projects by sector and register your interest with Axis.',
        'products' => 'Explore investment and capital mobilization options for individuals, institutions and SACCOs.',
        'portfolio' => 'Explore the brands in the Axis portfolio.',
        'insights' => 'Read articles and perspectives, and find white papers, media and other resources from Axis.',
    ];
    $category = in_array(param('category'), $categories, true) ? param('category') : '';
    $page = max(1, min((int) param('page'), 1000000));
    $items = published($kind, $category, 25, ($page - 1) * 24);
    $hasNext = count($items) > 24;
    $items = array_slice($items, 0, 24);
    layout($title, function () use ($kind, $title, $introductions, $categories, $category, $items, $hasNext, $page) {
        pageHead($title, $introductions[$kind]);
        ?>
        <section class="section shell">
            <?php if ($categories): ?><form class="filter" method="get"><label for="category">Filter by <?= $kind === 'projects' ? 'sector' : 'category' ?></label><select id="category" name="category"><option value="">All</option><?php foreach ($categories as $option): ?><option<?= $category === $option ? ' selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select><button class="button outline" type="submit">Apply</button></form><?php endif; ?>
            <?php if ($kind === 'products' && $page === 1 && ($category === '' || $category === 'SACCOs')): ?>
                <div class="feature-card">
                    <div class="feature-image" aria-hidden="true"></div>
                    <div><p class="eyebrow">Capital mobilization</p><h2>Investment conversations for SACCOs</h2><p>Explore how your SACCO can discuss potential ways to finance African enterprise with Axis.</p><a class="text-link" href="/products/sacco">Explore the SACCO pathway</a></div>
                </div>
            <?php endif; ?>
            <?php if ($items && $kind === 'portfolio'): ?>
                <div class="card-grid"><?php foreach ($items as $item): ?>
                    <details class="portfolio-card"><summary><?php if ($item['image_id']): ?><img src="<?= assetUrl($item['image_id']) ?>" alt="" loading="lazy"><?php endif; ?><strong><?= e($item['title']) ?></strong></summary>
                    <div><p><?= e($item['summary']) ?></p><?php if ($item['body']): ?><p><?= nl2br(e($item['body'])) ?></p><?php endif; ?><?php if ($item['external_url']): button($item['external_url'], 'Visit site', 'outline'); endif; ?></div></details>
                <?php endforeach; ?></div>
            <?php elseif ($items): cards($items, $kind); ?>
            <?php else: ?>
                <div class="empty"><h2><?= $category ? 'No listings in this category yet' : 'No published listings yet' ?></h2><?php if ($kind === 'projects' || $kind === 'products'): ?><p>You can still tell us what you’re looking for.</p><a class="text-link" href="/investors/apply">Register your interest</a><?php endif; ?></div>
            <?php endif; ?>
            <?php if ($page > 1 || $hasNext): ?>
                <nav class="actions" aria-label="Listing pages">
                    <?php $prefix = '/' . $kind . '?' . ($category ? 'category=' . rawurlencode($category) . '&' : ''); ?>
                    <?php if ($page > 1): button($prefix . 'page=' . ($page - 1), 'Previous page', 'outline'); endif; ?>
                    <span>Page <?= $page ?></span>
                    <?php if ($hasNext): button($prefix . 'page=' . ($page + 1), 'Next page', 'outline'); endif; ?>
                </nav>
            <?php endif; ?>
        </section>
        <?php
    });
}

function sacco(): void
{
    layout('For SACCOs', function () {
        pageHead('Investment conversations for SACCOs', 'Talk to Axis about how your SACCO or local capital pool could support enterprise growth and explore investment opportunities.', 'Capital mobilization');
        ?><section class="shell section split"><div><h2>What we can discuss</h2><p>We can discuss your SACCO’s investment objectives and possible areas of interest, including SMEs, startups, technology ventures and capital markets.</p></div><div><h2>Start the conversation</h2><p>Share your organization’s priorities and the kind of opportunity you want to explore. Axis can follow up to discuss suitability, structure, risk and available documentation.</p><?php button('/investors/apply?type=sacco', 'Enquire for your SACCO'); ?></div></section><?php
    });
}

function detail(string $kind, string $slug): bool
{
    $item = query(
        'SELECT c.*, a.mime AS file_mime FROM content c LEFT JOIN assets a ON c.file_id = a.id
         WHERE c.kind = ? AND c.slug = ? AND c.published = 1',
        [$kind, $slug]
    )->fetch();
    if (!$item) {
        return false;
    }
    layout($item['title'], function () use ($item, $kind) {
        pageHead($item['title'], $item['summary'], $item['category']);
        ?>
        <article class="shell section article">
            <a class="text-link" href="/<?= e($kind) ?>">Back to <?= e(strtolower(COLLECTIONS[$kind][0])) ?></a>
            <?php if ($item['image_id']): ?><img class="detail-image" src="<?= assetUrl($item['image_id']) ?>" alt=""><?php endif; ?>
            <?php foreach (preg_split('/\R{2,}/', $item['body'], -1, PREG_SPLIT_NO_EMPTY) as $paragraph): ?><p><?= nl2br(e($paragraph)) ?></p><?php endforeach; ?>
            <div class="actions">
            <?php if ($item['file_id']): ?>
                <?php if (str_starts_with($item['file_mime'], 'video/')): ?><video controls preload="metadata" src="<?= assetUrl($item['file_id']) ?>"></video>
                <?php elseif (str_starts_with($item['file_mime'], 'audio/')): ?><audio controls preload="metadata" src="<?= assetUrl($item['file_id']) ?>"></audio>
                <?php else: button(assetUrl($item['file_id']), 'Download PDF', 'outline'); endif; ?>
            <?php endif; ?>
            <?php if ($item['external_url']): button($item['external_url'], 'Visit related website', 'outline'); endif; ?>
            <?php if ($kind === 'projects' || $kind === 'products'): button('/investors/apply?topic=' . rawurlencode($item['title']), 'Register interest'); endif; ?>
            </div>
        </article>
        <?php
    }, $item['summary']);
    return true;
}
