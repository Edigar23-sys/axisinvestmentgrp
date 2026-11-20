<?php
declare(strict_types=1);

function notice(string $message, bool $error = false): void
{
    if ($message !== '') {
        echo '<p class="notice' . ($error ? ' error' : '') . '" role="' . ($error ? 'alert' : 'status') . '">' . e($message) . '</p>';
    }
}

function signIn(string $role, string $error): void
{
    $admin = $role === 'admin';
    ?><form method="post" class="form panel">
        <h2><?= $admin ? 'Admin sign-in' : 'Investor sign-in' ?></h2>
        <?php notice($error, true); ?>
        <?= csrf() ?>
        <input type="hidden" name="action" value="login">
        <label>Email <input type="email" name="email" autocomplete="username" required value="<?= e(field('email')) ?>"></label>
        <label>Password <input type="password" name="password" autocomplete="current-password" required></label>
        <button type="submit" class="button primary">Sign in</button>
        <p class="small">Need help with access? <a href="mailto:info@axisinvestmentgrp.com">Contact Axis</a>.</p>
    </form><?php
}

function requestForm(string $kind, string $error): void
{
    $name = $kind === 'Advisory' ? 'Advisory enquiry' : $kind . ' investor enquiry';
    $user = viewer();
    $address = ($user['role'] ?? '') === 'investor' ? $user['email'] : '';
    $orgRequired = in_array($kind, ['Institutional', 'SACCO'], true);
    ?>
    <form method="post" class="form panel">
        <h2><?= e($name) ?></h2>
        <?php notice($error, true); ?>
        <?= csrf() ?>
        <div class="form-grid">
            <label>Full name <input name="name" autocomplete="name" maxlength="120" required value="<?= e(field('name')) ?>"></label>
            <label>Email <input type="email" name="email" autocomplete="email" maxlength="254" required value="<?= e($address ?: field('email')) ?>"<?= $address ? ' readonly' : '' ?>></label>
            <label><?= $orgRequired ? 'Organization' : 'Organization (optional)' ?> <input name="organization" maxlength="160" autocomplete="organization" <?= $orgRequired ? 'required' : '' ?> value="<?= e(field('organization')) ?>"></label>
            <label>Country <input name="country" maxlength="100" autocomplete="country-name" required value="<?= e(field('country')) ?>"></label>
        </div>
        <?php if ($kind === 'Advisory'): ?>
            <label>Service <select name="topic"><option value="">Choose a service</option><?php foreach (SERVICES as [$service]): ?><option<?= field('topic', SERVICES[param('service')][0] ?? '') === $service ? ' selected' : '' ?>><?= e($service) ?></option><?php endforeach; ?></select></label>
        <?php else: ?><label>Investment interest (optional) <input name="topic" maxlength="180" value="<?= e(field('topic', param('topic'))) ?>"></label><?php endif; ?>
        <label><?= $kind === 'Advisory' ? 'How can we help?' : 'Tell us about your investment objectives' ?> <textarea name="message" rows="5" maxlength="5000" required><?= e(field('message')) ?></textarea></label>
        <label class="check"><input type="checkbox" name="consent" value="confirmed" required <?= field('consent') ? 'checked' : '' ?>><span>I agree that Axis may contact me about this request. <a href="/privacy">Privacy information</a></span></label>
        <button type="submit" class="button primary">Submit enquiry</button>
    </form>
    <?php
}

function advisory(string $error): void
{
    $message = takeFlash();
    layout('Advisory', function () use ($error, $message) {
        pageHead('Advisory services', 'Work with Axis on investment decisions, business growth, fundraising readiness and market development.');
        ?>
        <section class="section shell sector-grid"><?php foreach (SERVICES as $key => [$name, $description]): ?>
            <div id="<?= e($key) ?>"><h2><?= e($name) ?></h2><p><?= e($description) ?></p><a class="text-link" href="/advisory?service=<?= e($key) ?>#enquire">Discuss this service</a></div>
        <?php endforeach; ?></section>
        <section class="section tinted" id="enquire"><div class="shell split">
            <div class="intro"><h2>Speak with our team</h2><p>Tell us about your business, the decision you’re facing and how Axis can help.</p><a class="text-link" href="/insights">Read our latest insights</a>
                <img class="editorial-photo advisory-photo" src="/assets/advisory-meeting.jpg" width="1200" height="800" alt="Business advisers reviewing a plan together around a conference table" loading="lazy" decoding="async">
            </div>
            <div><?php notice($message); requestForm('Advisory', $error); ?></div>
        </div></section>
        <?php
    });
}

function applyPage(string $kind, string $error): void
{
    $types = ['Individual' => 'individual', 'Institutional' => 'institutional', 'SACCO' => 'sacco'];
    layout('Invest Now', function () use ($types, $kind, $error) {
        pageHead('Invest with Axis', 'Tell us what you’re looking for and the Axis team can follow up about relevant opportunities.', 'Investor enquiries');
        ?><section class="section shell split">
            <div><h2>Who’s investing?</h2><nav class="type-nav" aria-label="Investor type"><?php foreach ($types as $label => $type): ?>
                <a href="/investors/apply?type=<?= e($type) ?><?= param('topic') ? '&amp;topic=' . rawurlencode(param('topic')) : '' ?>"<?= $kind === $label ? ' aria-current="page"' : '' ?>><?= e($label) ?><?= $label === 'SACCO' ? '' : ' investor' ?></a>
            <?php endforeach; ?></nav><p class="small">Submitting an enquiry does not commit you to an investment. Please leave out banking details and identity documents.</p><a class="text-link" href="/investors">Already have an account?</a></div>
            <?php requestForm($kind, $error); ?>
        </section><?php
    }, private: true);
}

function logoutForm(): void
{
    ?><form method="post"><?= csrf() ?><input type="hidden" name="action" value="logout"><button type="submit" class="button outline">Sign out</button></form><?php
}

function investorPage(?array $user, string $error): void
{
    $message = takeFlash();
    $requests = $user ? query('SELECT kind, topic, status, created_at FROM requests WHERE user_id=? ORDER BY id DESC', [$user['id']])->fetchAll() : [];
    layout('Investor portal', function () use ($user, $requests, $error, $message) {
        pageHead('Investor portal', 'Explore opportunities, submit an enquiry or check the status of your requests.');
        ?><section class="section shell split">
            <div class="intro"><?php notice($message); ?><h2><?= $user ? 'Explore opportunities' : 'New to Axis?' ?></h2><p><?= $user ? 'Browse published projects, investment products and research from Axis.' : 'Start with an enquiry. If Axis approves portal access, you’ll receive an invitation to set up your account.' ?></p><div class="actions"><?php button('/investors/apply', 'Invest Now'); ?><?php button('/projects', 'Browse projects', 'outline'); ?></div></div>
            <div>
                <?php if (!$user): signIn('investor', $error); else: ?>
                    <div class="panel"><h2>Your enquiries</h2><p class="small">Signed in as <?= e($user['email']) ?></p>
                    <?php if ($requests): ?><ul class="request-list"><?php foreach ($requests as $item): ?><li><strong><?= e($item['topic'] ?: $item['kind']) ?></strong><span><?= e($item['status']) ?> · <?= e(substr($item['created_at'], 0, 10)) ?></span></li><?php endforeach; ?></ul><?php else: ?><p>No enquiries linked to your account yet.</p><?php endif; ?>
                    <?php logoutForm(); ?></div>
                <?php endif; ?>
            </div>
        </section><?php
    }, private: true);
}

function activationPage(string $token, string $error): void
{
    session();
    $valid = preg_match('/^[a-f0-9]{64}$/D', $token)
        && query('SELECT 1 FROM invites WHERE token_hash=? AND expires>=?', [hash('sha256', $token), time()])->fetchColumn();
    layout('Activate account', function () use ($valid, $error) {
        pageHead('Set up your investor account', 'Create a password to access your Axis investor portal.');
        ?><section class="section shell narrow">
        <?php notice($error, true); ?>
        <?php if ($valid): ?>
            <form method="post" class="form panel"><?= csrf() ?><label>New password <input type="password" name="password" minlength="12" maxlength="72" autocomplete="new-password" required></label><p class="small">Use at least 12 characters.</p><button type="submit" class="button primary">Activate account</button></form>
        <?php else: ?><p>This invitation is invalid or has expired. <a href="mailto:info@axisinvestmentgrp.com">Contact Axis</a> for a new link.</p><?php endif; ?>
        </section><?php
    }, private: true);
}

function adminNav(string $active): void
{
    $sections = ['' => 'Overview', 'requests' => 'Enquiries'];
    foreach (COLLECTIONS as $key => [$label]) {
        $sections[$key] = $label;
    }
    ?><div class="admin-bar"><nav class="admin-nav" aria-label="Dashboard"><?php foreach ($sections as $key => $label): ?>
        <a href="/admin<?= $key ? '/' . e($key) : '' ?>"<?= $active === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?></nav><?php logoutForm(); ?></div><?php
}

function adminPage(?array $user, string $section, string $error): void
{
    $admin = ($user['role'] ?? '') === 'admin';
    $message = $admin ? takeFlash() : '';
    $id = max(0, (int) param('id'));
    layout('Admin dashboard', function () use ($admin, $section, $error, $message, $id) {
        pageHead($admin ? 'Axis dashboard' : 'Admin portal', $admin ? 'Manage published content and respond to enquiries.' : 'Sign in to manage the Axis website.');
        if (!$admin) {
            echo '<section class="section shell narrow">';
            signIn('admin', $error);
            echo '</section>';
            return;
        }
        ?><section class="section shell dashboard"><?php adminNav($section); notice($message); notice($error, true); ?>
        <?php if ($section === 'requests'): adminRequests($id); 
        elseif (isset(COLLECTIONS[$section])): adminContent($section, $id);
        else: ?>
            <h2>Overview</h2><div class="sector-grid">
            <a href="/admin/requests"><h3>Enquiries</h3><p><?= (int) query("SELECT count(*) FROM requests WHERE status = 'Received'")->fetchColumn() ?> new</p></a>
            <?php foreach (COLLECTIONS as $key => [$label]): ?><a href="/admin/<?= e($key) ?>"><h3><?= e($label) ?></h3><p><?= (int) query('SELECT count(*) FROM content WHERE kind=? AND published=1', [$key])->fetchColumn() ?> published</p></a><?php endforeach; ?>
            </div>
        <?php endif; ?>
        </section><?php
    }, private: true);
}

function adminContent(string $kind, int $id): void
{
    [$label, $categories] = COLLECTIONS[$kind];
    $rows = query('SELECT id,title,category,published FROM content WHERE kind=? ORDER BY updated_at DESC, id DESC', [$kind])->fetchAll();
    $item = $id ? query('SELECT * FROM content WHERE id=? AND kind=?', [$id, $kind])->fetch() : [];
    if ($id && !$item) {
        notice('This item is unavailable.', true);
        return;
    }
    $value = static fn(string $key): string => field($key, (string) ($item[$key] ?? ''));
    ?>
    <div class="dashboard-grid">
        <div><div class="section-heading"><h2><?= e($label) ?></h2><?php button('/admin/' . $kind, 'Add new', 'outline'); ?></div>
            <?php if ($rows): ?><ul class="record-list"><?php foreach ($rows as $row): ?><li><a href="/admin/<?= e($kind) ?>?id=<?= (int) $row['id'] ?>"<?= $id === (int) $row['id'] ? ' aria-current="page"' : '' ?>><strong><?= e($row['title']) ?></strong><span><?= $row['published'] ? 'Published' : 'Draft' ?><?= $row['category'] ? ' · ' . e($row['category']) : '' ?></span></a></li><?php endforeach; ?></ul><?php else: ?><p>No entries yet.</p><?php endif; ?>
        </div>
        <form method="post" enctype="multipart/form-data" class="panel form">
            <h2><?= $id ? 'Edit entry' : 'New entry' ?></h2><?= csrf() ?>
            <input type="hidden" name="action" value="save">
            <label><?= $kind === 'portfolio' ? 'Brand name' : 'Title' ?> <input name="title" maxlength="150" required value="<?= e($value('title')) ?>"></label>
            <label>Page address (optional) <input name="slug" maxlength="95" placeholder="generated-from-title" value="<?= e($value('slug')) ?>"></label>
            <?php if ($categories): ?><label>Category <select name="category" required><option value="">Choose a category</option><?php foreach ($categories as $category): ?><option<?= $category === $value('category') ? ' selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?></select></label>
            <?php else: ?><label>Sector or category (optional) <input name="category" maxlength="120" value="<?= e($value('category')) ?>"></label><?php endif; ?>
            <label>Summary <textarea name="summary" maxlength="450" rows="3" required><?= e($value('summary')) ?></textarea></label>
            <label><?= $kind === 'portfolio' ? 'About the brand' : 'Page content' ?> <textarea name="body" maxlength="24000" rows="8"><?= e($value('body')) ?></textarea></label>
            <label><?= $kind === 'portfolio' ? 'Brand website' : 'Related website (optional)' ?> <input type="url" name="external_url" maxlength="500" placeholder="https://" value="<?= e($value('external_url')) ?>"></label>
            <label><?= $kind === 'portfolio' ? 'Brand logo' : 'Cover image' ?> <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif"></label>
            <p class="small">JPEG, PNG, WebP or GIF, up to 6 MB.</p>
            <?php if (!empty($item['image_id'])): ?><img class="admin-image" src="<?= assetUrl($item['image_id']) ?>" alt="Current image"><label class="check"><input type="checkbox" name="remove_image" value="yes">Remove current image</label><?php endif; ?>
            <?php if ($kind !== 'portfolio'): ?>
                <label>Document or media (optional) <input type="file" name="attachment" accept=".pdf,.mp4,.webm,.mp3,.ogg,.wav"></label><p class="small">PDF, MP4, WebM, MP3, Ogg or WAV, up to 20 MB.</p>
                <?php if (!empty($item['file_id'])): ?><a class="text-link" href="<?= assetUrl($item['file_id']) ?>">Open current attachment</a><label class="check"><input type="checkbox" name="remove_attachment" value="yes">Remove current attachment</label><?php endif; ?>
            <?php endif; ?>
            <?php $visibility = field('visibility', !empty($item['published']) ? 'published' : 'draft'); ?>
            <label>Visibility <select name="visibility"><option value="draft"<?= $visibility === 'draft' ? ' selected' : '' ?>>Draft</option><option value="published"<?= $visibility === 'published' ? ' selected' : '' ?>>Published</option></select></label>
            <div class="actions"><button class="button primary" type="submit">Save entry</button>
            <?php if (!empty($item['published'])): button($kind === 'portfolio' ? '/portfolio' : '/' . $kind . '/' . $item['slug'], 'View published entry', 'outline'); endif; ?></div>
        </form>
    </div>
    <?php
}

function adminRequests(int $id): void
{
    $rows = query('SELECT id,name,kind,status FROM requests ORDER BY id DESC')->fetchAll();
    $item = $id ? query('SELECT * FROM requests WHERE id=?', [$id])->fetch() : false;
    $invite = $_SESSION['invite_links'][$id] ?? '';
    unset($_SESSION['invite_links'][$id]);
    ?><div class="dashboard-grid">
        <div><h2>Enquiries</h2><?php if (!$rows): ?><p>No enquiries yet.</p><?php endif; ?>
            <ul class="record-list"><?php foreach ($rows as $row): ?><li><a href="/admin/requests?id=<?= (int) $row['id'] ?>"<?= $id === (int) $row['id'] ? ' aria-current="page"' : '' ?>><strong><?= e($row['name']) ?></strong><span><?= e($row['kind'] . ' · ' . $row['status']) ?></span></a></li><?php endforeach; ?></ul>
        </div>
        <?php if ($item): ?>
        <div class="panel">
            <h2><?= e($item['name']) ?></h2><p><?= e($item['kind']) ?> · <?= e($item['created_at']) ?> UTC</p>
            <dl class="values">
                <?php foreach (['Email' => 'email', 'Organization' => 'organization', 'Country' => 'country', 'Interest' => 'topic'] as $label => $key): ?>
                <div><dt><?= e($label) ?></dt><dd><?= e($item[$key]) ?></dd></div>
                <?php endforeach; ?>
            </dl><p><?= nl2br(e($item['message'])) ?></p>
            <form method="post" class="form"><?= csrf() ?><input type="hidden" name="action" value="status"><label>Status <select name="status"><?php foreach (REQUEST_STATUSES as $status): ?><option<?= $status === $item['status'] ? ' selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></label><button class="button outline" type="submit">Update status</button></form>
            <?php if ($item['kind'] !== 'Advisory' && $item['status'] === 'Approved'): ?>
                <form method="post" class="form invite"><?= csrf() ?><input type="hidden" name="action" value="invite"><button class="button primary" type="submit">Generate investor invitation</button></form>
            <?php endif; ?>
            <?php if ($invite): ?><div class="notice"><label for="invite-link">Copy this one-time link and send it to the approved investor. It expires in 48 hours.</label><input id="invite-link" readonly value="<?= e($invite) ?>"></div><?php endif; ?>
        </div>
        <?php elseif ($id): ?><p>This enquiry is unavailable.</p><?php endif; ?>
    </div><?php
}

function privacy(): void
{
    layout('Privacy', function () {
        pageHead('Privacy information', 'How information submitted through this website is used.');
        ?><section class="section shell article"><h2>Enquiries and account access</h2><p>Axis uses the contact details and information you submit to review and respond to enquiries, provide access to the investor portal where applicable, and protect website access. The site uses a session cookie when you use a form or sign in.</p><h2>Contact</h2><p>For questions about your information or to request a correction, contact <a href="mailto:info@axisinvestmentgrp.com">info@axisinvestmentgrp.com</a>.</p></section><?php
    });
}
