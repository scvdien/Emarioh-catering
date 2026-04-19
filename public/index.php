<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$publicSiteSettings = emarioh_default_public_site_settings();
$publicServiceCards = emarioh_default_public_service_cards();
$galleryItems = [];

try {
    $db = emarioh_db();
    $publicSiteSettings = emarioh_fetch_public_site_settings($db);
    $publicServiceCards = emarioh_fetch_public_service_cards($db);
    $galleryItems = emarioh_fetch_gallery_items($db);
} catch (Throwable $throwable) {
    $publicSiteSettings = emarioh_default_public_site_settings();
    $publicServiceCards = emarioh_default_public_service_cards();
    $galleryItems = [];
}

$heroImagePath = emarioh_normalize_public_asset_path(
    (string) ($publicSiteSettings['hero_image_path'] ?? ''),
    ''
);
$heroImageUrl = emarioh_public_asset_url($heroImagePath);
$heroImageAbsolutePath = $heroImagePath !== '' ? emarioh_public_asset_absolute_path($heroImagePath) : null;
$serviceCardMeta = [
    'service_1' => [
        'icon' => 'bi-balloon-heart',
        'detail_id' => 'service-weddings-birthdays',
        'eyebrow' => 'Celebration Catering',
        'lead' => 'Ideal for wedding receptions, birthdays, and polished family celebrations that need elegant presentation and smooth event-day service.',
        'highlights' => [
            'Curated buffet styling for milestone gatherings and formal family occasions',
            'Flexible food selections that can match intimate or larger guest counts',
            'Coordinated setup and service flow to keep the celebration polished from start to finish',
        ],
    ],
    'service_2' => [
        'icon' => 'bi-briefcase',
        'detail_id' => 'service-corporate-catering',
        'eyebrow' => 'Business Events',
        'lead' => 'Designed for meetings, seminars, launches, and company functions that need dependable timing, professional presentation, and guest-ready dining.',
        'highlights' => [
            'Reliable meal service for trainings, office functions, and formal business gatherings',
            'Professional presentation that fits both executive and team-wide events',
            'Streamlined coordination that helps keep the program organized and on schedule',
        ],
    ],
    'service_3' => [
        'icon' => 'bi-stars',
        'detail_id' => 'service-debut-social-events',
        'eyebrow' => 'Social Gatherings',
        'lead' => 'Built for debuts, anniversaries, reunions, and memorable social occasions that call for a stylish setup and welcoming dining experience.',
        'highlights' => [
            'Celebration-ready buffet styling for debuts and meaningful social milestones',
            'Guest-friendly menu planning for reunions, anniversaries, and special occasions',
            'Refined service support that helps the event feel warm, polished, and memorable',
        ],
    ],
];

if ($heroImageAbsolutePath === null || !is_file($heroImageAbsolutePath)) {
    $heroImageUrl = '';
}

$heroStyle = $heroImageUrl !== '' ? "--hero-image-url: url('" . $escape($heroImageUrl) . "');" : '';
$galleryFallbackItems = [
    [
        'title' => 'Wedding Reception',
        'filter_category' => 'wedding social',
        'image_url' => '',
        'image_alt' => 'Wedding Reception gallery image',
    ],
    [
        'title' => 'Wedding Buffet',
        'filter_category' => 'wedding social',
        'image_url' => '',
        'image_alt' => 'Wedding Buffet gallery image',
    ],
    [
        'title' => 'Birthday Celebration',
        'filter_category' => 'birthday social',
        'image_url' => '',
        'image_alt' => 'Birthday Celebration gallery image',
    ],
    [
        'title' => 'Wedding Cake Setup',
        'filter_category' => 'wedding social',
        'image_url' => '',
        'image_alt' => 'Wedding Cake Setup gallery image',
    ],
    [
        'title' => 'Corporate Event',
        'filter_category' => 'corporate',
        'image_url' => '',
        'image_alt' => 'Corporate Event gallery image',
    ],
    [
        'title' => 'Dessert Station',
        'filter_category' => 'birthday social',
        'image_url' => '',
        'image_alt' => 'Dessert Station gallery image',
    ],
];
$publicGalleryItems = [];

foreach ($galleryItems as $galleryItem) {
    if (!is_array($galleryItem)) {
        continue;
    }

    $galleryTitle = trim((string) ($galleryItem['title'] ?? ''));
    $galleryTitle = $galleryTitle !== '' ? $galleryTitle : 'Gallery image';
    $galleryCategory = emarioh_normalize_gallery_category((string) ($galleryItem['category'] ?? ''), 'wedding');
    $galleryImagePath = emarioh_normalize_public_asset_path((string) ($galleryItem['image_path'] ?? ''), '');
    $galleryImageUrl = $galleryImagePath !== '' ? emarioh_public_asset_url($galleryImagePath) : '';
    $galleryImageAbsolutePath = $galleryImagePath !== '' ? emarioh_public_asset_absolute_path($galleryImagePath) : null;
    $galleryHasImage = $galleryImageAbsolutePath !== null && is_file($galleryImageAbsolutePath) && $galleryImageUrl !== '';
    $galleryImageAlt = trim((string) ($galleryItem['image_alt'] ?? ''));
    $galleryImageAlt = $galleryImageAlt !== '' ? $galleryImageAlt : ($galleryTitle . ' gallery image');

    $publicGalleryItems[] = [
        'title' => $galleryTitle,
        'filter_category' => $galleryCategory,
        'image_url' => $galleryHasImage ? $galleryImageUrl : '',
        'image_alt' => $galleryImageAlt,
    ];
}

$galleryCards = $publicGalleryItems !== [] ? $publicGalleryItems : $galleryFallbackItems;
$publicContactServiceArea = trim((string) ($publicSiteSettings['service_area'] ?? ''));
$publicContactEmail = trim((string) ($publicSiteSettings['public_email'] ?? ''));
$publicContactMobile = trim((string) ($publicSiteSettings['primary_mobile'] ?? ''));
$publicContactBusinessHours = trim((string) ($publicSiteSettings['business_hours'] ?? ''));
$publicContactMobileHref = $publicContactMobile !== ''
    ? (preg_replace('/[^\d+]/', '', $publicContactMobile) ?: $publicContactMobile)
    : '';
$hasPublicContactDetails = $publicContactServiceArea !== ''
    || $publicContactEmail !== ''
    || $publicContactMobile !== ''
    || $publicContactBusinessHours !== '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Luxurious and professional public page for Emarioh Catering Services with packages, menu highlights, gallery, and online booking access.">
    <title>Emarioh Catering Services | Public Page</title>
    <?= emarioh_render_vendor_head_assets(false, true); ?>
    <link rel="stylesheet" href="assets/css/pages/public-page.css?v=20260412ac">
    <link rel="stylesheet" href="assets/css/public-page-overrides.css?v=20260419a">
</head>
<body class="public-page">
    <header class="public-header">
        <div class="public-shell public-header__bar">
            <a href="public-page.php" class="public-brand" aria-label="Emarioh Catering Services home">
                <span class="public-brand__frame">
                    <img src="assets/images/logo.jpg" alt="Emarioh Catering Services logo" class="public-brand__logo">
                </span>
                <span class="public-brand__copy">
                    <span class="public-brand__name">Emarioh</span>
                    <span class="public-brand__sub">Catering Services</span>
                </span>
            </a>

            <nav class="public-nav" aria-label="Primary navigation">
                <a href="#home">Home</a>
                <a href="#about">About</a>
                <a href="#services">Services</a>
                <a href="#packages">Packages</a>
                <a href="#gallery">Gallery</a>
                <a href="#contact">Contact</a>
            </nav>

            <div class="public-header__actions">
                <a class="public-header__social public-header__social--contact" href="login.php" aria-label="Open client login">
                    <i class="bi bi-person-circle" aria-hidden="true"></i>
                    <span>Client Login</span>
                </a>
                <button class="public-menu-toggle" type="button" aria-expanded="false" aria-controls="publicMobileNav" aria-label="Open navigation menu">
                    <i class="bi bi-list"></i>
                </button>
            </div>
        </div>

        <div class="public-mobile-nav" id="publicMobileNav" hidden>
            <div class="public-shell public-mobile-nav__inner">
                <a href="#home">Home</a>
                <a href="#about">About</a>
                <a href="#services">Services</a>
                <a href="#packages">Packages</a>
                <a href="#gallery">Gallery</a>
                <a href="#contact">Contact</a>
                <a href="login.php">Client Login</a>
                <a href="registration.php">Book an Event</a>
            </div>
        </div>
    </header>

    <main>
        <section class="hero" id="home"<?= $heroStyle !== '' ? ' style="' . $heroStyle . '"' : '' ?>>
            <div class="public-shell">
                <div class="hero__panel" data-reveal>
                    <div class="hero__copy">
                        <p class="section-kicker section-kicker--light">For Weddings, Birthdays, and Special Events</p>
                        <h1 class="hero__headline">
                            <span class="hero__headline-script">Emarioh</span>
                            <span class="hero__headline-brand">Catering Services</span>
                        </h1>
                        <p class="hero__lead">Delicious food, elegant presentation, and hassle-free booking for every celebration.</p>

                        <div class="hero__actions">
                            <a class="public-button public-button--gold" href="registration.php">Book An Event</a>
                            <a class="public-button public-button--outline" href="#packages">View Packages</a>
                        </div>
                    </div>

                    <div class="hero__visual" aria-hidden="true">
                        <div class="hero__image-card"></div>
                    </div>

                    <div class="public-proof public-proof--hero-cards" data-reveal>
                        <article class="public-proof__item">
                            <i class="bi bi-balloon-heart"></i>
                            <div>
                                <strong>Weddings &amp; Birthdays</strong>
                                <span>Weddings &amp; Birthdays</span>
                            </div>
                        </article>
                        <article class="public-proof__item">
                            <i class="bi bi-briefcase"></i>
                            <div>
                                <strong>Corporate Catering</strong>
                                <span>Corporate Catering</span>
                            </div>
                        </article>
                        <article class="public-proof__item">
                            <i class="bi bi-journal-check"></i>
                            <div>
                                <strong>Flexible Packages</strong>
                                <span>Flexible Packages</span>
                            </div>
                        </article>
                        <article class="public-proof__item">
                            <i class="bi bi-stars"></i>
                            <div>
                                <strong>Elegant Presentation</strong>
                                <span>Elegant Presentation</span>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section class="public-section" id="about">
            <div class="public-shell">
                <div class="about-board" data-reveal>
                    <div class="about-board__header">
                        <p class="section-kicker about-board__kicker">About Emarioh</p>
                        <h2>About Emarioh</h2>
                        <p>Professional catering for weddings, birthdays, corporate events, and special occasions with reliable service, elegant setup, flexible menus, and easy online booking.</p>
                    </div>

                    <div class="about-board__lead" data-reveal>
                        <p>We create polished celebrations with coordinated service, curated food selections, and refined presentation designed to make every gathering feel special from planning to final setup.</p>
                    </div>

                    <div class="about-board__grid">
                        <article class="about-feature-card" data-reveal>
                            <span class="about-feature-card__icon" aria-hidden="true">
                                <i class="bi bi-people"></i>
                            </span>
                            <h3>Reliable Service</h3>
                            <p>Coordinated support before, during, and after your event for a smoother celebration experience.</p>
                        </article>
                        <article class="about-feature-card" data-reveal>
                            <span class="about-feature-card__icon" aria-hidden="true">
                                <i class="bi bi-journal-check"></i>
                            </span>
                            <h3>Flexible Menu</h3>
                            <p>Food selections tailored to your package, guest count, and preferred dining style.</p>
                        </article>
                        <article class="about-feature-card" data-reveal>
                            <span class="about-feature-card__icon" aria-hidden="true">
                                <i class="bi bi-stars"></i>
                            </span>
                            <h3>Elegant Setup</h3>
                            <p>Refined buffet styling and polished presentation that elevate weddings, birthdays, and formal events.</p>
                        </article>
                        <article class="about-feature-card" data-reveal>
                            <span class="about-feature-card__icon" aria-hidden="true">
                                <i class="bi bi-calendar2-check"></i>
                            </span>
                            <h3>Easy Booking</h3>
                            <p>Reserve online, review your options clearly, and receive updates throughout your booking process.</p>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section class="public-section public-section--soft" id="services">
            <div class="public-shell">
                <div class="services-board" data-reveal>
                    <div class="services-board__header">
                        <p class="section-kicker services-board__kicker">Services</p>
                        <h2>Our Services</h2>
                        <p>Discover how we can make your event truly special with elegant setups, polished catering, and celebration-ready support.</p>
                    </div>

                    <div class="services-board__grid">
                        <?php foreach ($publicServiceCards as $slotKey => $serviceCard): ?>
                            <?php
                                $serviceTitle = trim((string) ($serviceCard['title'] ?? 'Service'));
                                $serviceDescription = trim((string) ($serviceCard['description'] ?? ''));
                                $serviceImagePath = emarioh_normalize_public_asset_path((string) ($serviceCard['image_path'] ?? ''), '');
                                $serviceImageUrl = $serviceImagePath !== '' ? emarioh_public_asset_url($serviceImagePath) : '';
                                $serviceImageAbsolutePath = $serviceImagePath !== '' ? emarioh_public_asset_absolute_path($serviceImagePath) : null;
                                $hasServiceImage = $serviceImageAbsolutePath !== null && is_file($serviceImageAbsolutePath) && $serviceImageUrl !== '';
                                $serviceImageAlt = trim((string) ($serviceCard['image_alt'] ?? ''));
                                $serviceImageAlt = $serviceImageAlt !== '' ? $serviceImageAlt : ($serviceTitle !== '' ? $serviceTitle . ' service image' : 'Service image');
                                $serviceMeta = $serviceCardMeta[$slotKey] ?? [
                                    'icon' => 'bi-stars',
                                    'detail_id' => 'service-details',
                                    'eyebrow' => 'Service Details',
                                    'lead' => 'Discover more about this service offering and how it can fit your event.',
                                    'highlights' => [],
                                ];
                                $serviceIcon = $serviceMeta['icon'] ?? 'bi-stars';
                                $serviceDetailId = (string) ($serviceMeta['detail_id'] ?? 'service-details');
                            ?>
                            <article class="services-card" data-reveal>
                                <div class="services-card__inner">
                                    <h3 class="services-card__top-title"><?= $escape($serviceTitle) ?></h3>
                                    <figure class="services-card__visual<?= $hasServiceImage ? '' : ' services-card__visual--empty' ?>" aria-hidden="<?= $hasServiceImage ? 'false' : 'true' ?>">
                                        <?php if ($hasServiceImage): ?>
                                            <img src="<?= $escape($serviceImageUrl) ?>" alt="<?= $escape($serviceImageAlt) ?>">
                                        <?php else: ?>
                                            <div class="services-card__placeholder">
                                                <i class="bi <?= $escape($serviceIcon) ?>"></i>
                                                <span>Image will appear here</span>
                                            </div>
                                        <?php endif; ?>
                                    </figure>
                                    <div class="services-card__body">
                                        <p class="services-card__body-title"><?= $escape($serviceTitle) ?></p>
                                        <p class="services-card__description"><?= $escape($serviceDescription) ?></p>
                                        <button
                                            class="public-button services-card__button"
                                            type="button"
                                            data-service-modal-open
                                            data-service-modal-target="<?= $escape($serviceDetailId) ?>"
                                            aria-haspopup="dialog"
                                            aria-controls="serviceDetailModal"
                                        >Learn More</button>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <div class="service-detail-templates" hidden aria-hidden="true">
            <?php foreach ($publicServiceCards as $slotKey => $serviceCard): ?>
                <?php
                    $serviceMeta = $serviceCardMeta[$slotKey] ?? [
                        'icon' => 'bi-stars',
                        'detail_id' => 'service-details',
                        'eyebrow' => 'Service Details',
                        'lead' => 'Discover more about this service offering and how it can fit your event.',
                        'highlights' => [],
                    ];
                    $serviceTitle = trim((string) ($serviceCard['title'] ?? 'Service'));
                    $serviceDescription = trim((string) ($serviceCard['description'] ?? ''));
                    $serviceImagePath = emarioh_normalize_public_asset_path((string) ($serviceCard['image_path'] ?? ''), '');
                    $serviceImageUrl = $serviceImagePath !== '' ? emarioh_public_asset_url($serviceImagePath) : '';
                    $serviceImageAbsolutePath = $serviceImagePath !== '' ? emarioh_public_asset_absolute_path($serviceImagePath) : null;
                    $hasServiceImage = $serviceImageAbsolutePath !== null && is_file($serviceImageAbsolutePath) && $serviceImageUrl !== '';
                    $serviceImageAlt = trim((string) ($serviceCard['image_alt'] ?? ''));
                    $serviceImageAlt = $serviceImageAlt !== '' ? $serviceImageAlt : ($serviceTitle !== '' ? $serviceTitle . ' service image' : 'Service image');
                    $serviceIcon = $serviceMeta['icon'] ?? 'bi-stars';
                    $serviceDetailId = (string) ($serviceMeta['detail_id'] ?? 'service-details');
                    $serviceLead = trim((string) ($serviceMeta['lead'] ?? ''));
                    $serviceHighlights = is_array($serviceMeta['highlights'] ?? null) ? $serviceMeta['highlights'] : [];
                ?>
                <template id="<?= $escape($serviceDetailId) ?>-template">
                    <article class="service-detail-card">
                        <figure class="service-detail-card__visual<?= $hasServiceImage ? '' : ' service-detail-card__visual--empty' ?>">
                            <?php if ($hasServiceImage): ?>
                                <img src="<?= $escape($serviceImageUrl) ?>" alt="<?= $escape($serviceImageAlt) ?>">
                            <?php else: ?>
                                <div class="service-detail-card__placeholder">
                                    <i class="bi <?= $escape($serviceIcon) ?>"></i>
                                    <span><?= $escape($serviceTitle) ?></span>
                                </div>
                            <?php endif; ?>
                        </figure>

                        <div class="service-detail-card__body">
                            <p class="section-kicker service-detail-card__kicker"><?= $escape((string) ($serviceMeta['eyebrow'] ?? 'Service Details')) ?></p>
                            <h3><?= $escape($serviceTitle) ?></h3>
                            <p class="service-detail-card__description"><?= $escape($serviceDescription) ?></p>
                            <p class="service-detail-card__lead"><?= $escape($serviceLead) ?></p>

                            <?php if ($serviceHighlights !== []): ?>
                                <ul class="service-detail-card__list">
                                    <?php foreach ($serviceHighlights as $serviceHighlight): ?>
                                        <li><?= $escape((string) $serviceHighlight) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <div class="service-detail-card__actions">
                                <a class="public-button public-button--outline" href="#packages">View Packages</a>
                                <a class="public-button public-button--gold" href="registration.php">Book Now</a>
                            </div>
                        </div>
                    </article>
                </template>
            <?php endforeach; ?>
        </div>

        <div class="service-modal" id="serviceDetailModal" hidden>
            <div class="service-modal__backdrop" data-service-modal-close></div>
            <div class="service-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="serviceDetailModalTitle">
                <div class="service-modal__toolbar">
                    <button class="service-modal__close" type="button" data-service-modal-close aria-label="Close service details">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="service-modal__content" id="service-details" data-service-modal-content></div>
            </div>
        </div>

        <section class="public-section public-section--accent" id="packages">
            <div class="public-shell">
                <div class="packages-board" data-reveal>
                    <div class="packages-board__header">
                        <p class="section-kicker packages-board__kicker">Packages</p>
                        <h2>Our Packages</h2>
                        <p>Discover the perfect catering package for your event. Choose from per-head offers or complete celebration packages for weddings, birthdays, and milestone gatherings.</p>
                    </div>

                    <div class="packages-board__body">
                        <article class="packages-panel packages-panel--per-head" data-reveal>
                            <div class="packages-panel__heading">
                                <h3>Per-Head Packages</h3>
                                <p>Minimum of 50 persons</p>
                            </div>

                            <div class="packages-tier-grid">
                                <article class="packages-tier-card" data-reveal>
                                    <p class="packages-tier-card__price">PHP 350/HEAD</p>
                                    <ul class="packages-tier-card__list">
                                        <li>Rice</li>
                                        <li>Chicken</li>
                                        <li>Pork</li>
                                        <li>Noodles or Pasta (Choose 1)</li>
                                        <li>Vegetables or Salad (Choose 1)</li>
                                        <li>Beverage</li>
                                    </ul>
                                </article>

                                <article class="packages-tier-card" data-reveal>
                                    <p class="packages-tier-card__price">PHP 400/HEAD</p>
                                    <ul class="packages-tier-card__list">
                                        <li>Rice</li>
                                        <li>Chicken</li>
                                        <li>Pork</li>
                                        <li>Fish</li>
                                        <li>Vegetables or Salad (Choose 1)</li>
                                        <li>Beverage</li>
                                    </ul>
                                </article>

                                <article class="packages-tier-card" data-reveal>
                                    <p class="packages-tier-card__price">PHP 500/HEAD</p>
                                    <ul class="packages-tier-card__list">
                                        <li>Rice</li>
                                        <li>Soup</li>
                                        <li>Chicken</li>
                                        <li>Pork</li>
                                        <li>Fish</li>
                                        <li>Vegetables or Salad (Choose 1)</li>
                                        <li>Beverage</li>
                                    </ul>
                                </article>

                                <article class="packages-tier-card" data-reveal>
                                    <p class="packages-tier-card__price">PHP 600/HEAD</p>
                                    <ul class="packages-tier-card__list">
                                        <li>Rice</li>
                                        <li>Soup</li>
                                        <li>Chicken</li>
                                        <li>Pork</li>
                                        <li>Fish</li>
                                        <li>Noodles or Pasta (Choose 1)</li>
                                        <li>Vegetables or Salad (Choose 1)</li>
                                        <li>Beverage</li>
                                        <li>Dessert</li>
                                    </ul>
                                </article>
                            </div>
                        </article>

                        <article class="packages-panel packages-panel--event" data-reveal>
                            <div class="packages-panel__heading">
                                <h3>Wedding &amp; Birthday Packages</h3>
                                <p>Complete event-ready setup for milestone celebrations</p>
                            </div>

                            <div class="packages-event-card">
                                <div class="packages-event-card__rates" aria-label="Wedding and birthday package sizes">
                                    <span class="packages-event-card__pill">50 pax</span>
                                    <span class="packages-event-card__pill">100 pax</span>
                                    <span class="packages-event-card__pill">150 pax</span>
                                </div>

                                <div class="packages-event-card__prices" aria-label="Wedding and birthday package prices">
                                    <p class="packages-event-card__price">PHP 50,000</p>
                                    <p class="packages-event-card__price">PHP 85,000</p>
                                    <p class="packages-event-card__price">PHP 120,000</p>
                                </div>

                                <div class="packages-event-card__content">
                                    <ul class="packages-event-card__list">
                                        <li>Three main dishes: pork, chicken, and fish</li>
                                        <li>Vegetables, soup, dessert, and one beverage choice</li>
                                        <li>Unlimited rice, water, and ice for the full event</li>
                                        <li>Full buffet setup and complete dining arrangement</li>
                                        <li>Two-tier wedding or celebration cake</li>
                                        <li>Tiffany chairs, couple couch, and styled centerpiece setup</li>
                                        <li>Themed event styling with polished on-site service support</li>
                                    </ul>

                                    <div class="packages-event-card__actions">
                                        <p>Ideal for weddings, debuts, birthdays, anniversaries, and elegant family celebrations.</p>
                                        <a class="public-button public-button--gold" href="registration.php">Request This Package</a>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>

                    <p class="packages-board__note">Package inclusions may vary depending on the selected event type, guest count, final arrangement, and menu availability.</p>
                </div>
            </div>
        </section>

        <section class="public-section public-section--soft" id="gallery">
            <div class="public-shell">
                <div class="gallery-board" data-reveal>
                    <div class="gallery-board__header">
                        <p class="section-kicker gallery-board__kicker">Gallery</p>
                        <h2>Gallery</h2>
                        <p class="gallery-board__eyebrow">Moments We&apos;ve Made Memorable</p>
                        <p>A glimpse of our catering setups and the beautiful events we&apos;ve been part of.</p>
                    </div>

                    <div class="gallery-toolbar" data-reveal>
                        <button class="gallery-filter is-active" type="button" data-filter="all">All Events</button>
                        <button class="gallery-filter" type="button" data-filter="wedding">Weddings</button>
                        <button class="gallery-filter" type="button" data-filter="birthday">Birthdays</button>
                        <button class="gallery-filter" type="button" data-filter="corporate">Corporate</button>
                        <button class="gallery-filter" type="button" data-filter="social">Socials</button>
                    </div>

                    <div class="gallery-grid">
                        <?php foreach ($galleryCards as $galleryCard): ?>
                            <?php
                                $galleryCardTitle = trim((string) ($galleryCard['title'] ?? ''));
                                $galleryCardTitle = $galleryCardTitle !== '' ? $galleryCardTitle : 'Gallery image';
                                $galleryCardFilterCategory = trim((string) ($galleryCard['filter_category'] ?? 'wedding'));
                                $galleryCardImageUrl = trim((string) ($galleryCard['image_url'] ?? ''));
                                $galleryCardImageAlt = trim((string) ($galleryCard['image_alt'] ?? ''));
                                $galleryCardImageAlt = $galleryCardImageAlt !== '' ? $galleryCardImageAlt : ($galleryCardTitle . ' gallery image');
                            ?>
                            <article class="gallery-card<?= $galleryCardImageUrl === '' ? ' gallery-card--empty' : '' ?>" data-category="<?= $escape($galleryCardFilterCategory) ?>" data-reveal>
                                <?php if ($galleryCardImageUrl !== ''): ?>
                                    <img
                                        src="<?= $escape($galleryCardImageUrl) ?>"
                                        alt="<?= $escape($galleryCardImageAlt) ?>"
                                        decoding="async"
                                        fetchpriority="low"
                                    >
                                <?php else: ?>
                                    <div class="gallery-card__placeholder" aria-hidden="true">
                                        <i class="bi bi-image"></i>
                                        <span>Real photo coming soon</span>
                                    </div>
                                <?php endif; ?>
                                <span class="gallery-card__tag"><?= $escape($galleryCardTitle) ?></span>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <p class="gallery-board__footer" data-reveal>Every Detail. Every Occasion. Beautifully Served.</p>
                </div>
            </div>
        </section>

        <section class="public-section public-section--contact" id="contact">
            <div class="public-shell">
                <div class="contact-showcase">
                    <div class="contact-showcase__intro" data-reveal>
                        <h2>Get in Touch</h2>
                        <p>Reach out for package guidance, booking questions, and event planning support.</p>
                    </div>

                    <div class="contact-layout">
                        <article class="contact-card contact-card--form" data-reveal>
                            <div class="contact-card__header">
                                <h3>Send Us A Message</h3>
                                <p>Share your event details or package questions and our team can guide you.</p>
                            </div>

                            <form class="contact-form" id="publicInquiryForm" action="api/inquiries/create.php" method="post">
                                <div class="contact-form__row">
                                    <label class="contact-field">
                                        <span>Name</span>
                                        <input type="text" name="name" placeholder="Your name" autocomplete="name" required>
                                    </label>

                                    <label class="contact-field">
                                        <span>Your Email</span>
                                        <input type="email" name="email" placeholder="Your Email" autocomplete="email" required>
                                    </label>
                                </div>

                                <label class="contact-field contact-field--message">
                                    <span>Message</span>
                                    <textarea name="message" rows="4" placeholder="Tell us about your event or reservation inquiry." required></textarea>
                                </label>

                                <div class="contact-form__actions">
                                    <button class="public-button public-button--gold contact-form__submit" type="submit">Send Message</button>
                                    <p class="contact-form__note">Or go straight to online booking.</p>
                                </div>
                                <p class="contact-form__feedback" data-contact-form-feedback aria-live="polite"></p>
                            </form>
                        </article>

                        <aside class="contact-card contact-card--info" data-reveal>
                            <?php if ($hasPublicContactDetails): ?>
                                <div class="contact-direct">
                                    <?php if ($publicContactServiceArea !== ''): ?>
                                        <article class="contact-direct__item">
                                            <span class="contact-direct__icon" aria-hidden="true">
                                                <i class="bi bi-geo-alt"></i>
                                            </span>
                                            <div>
                                                <span class="contact-direct__label">Service Area</span>
                                                <strong><?= $escape($publicContactServiceArea) ?></strong>
                                            </div>
                                        </article>
                                    <?php endif; ?>

                                    <?php if ($publicContactEmail !== ''): ?>
                                        <article class="contact-direct__item">
                                            <span class="contact-direct__icon" aria-hidden="true">
                                                <i class="bi bi-envelope"></i>
                                            </span>
                                            <div>
                                                <span class="contact-direct__label">Email Address</span>
                                                <a href="mailto:<?= $escape($publicContactEmail) ?>"><?= $escape($publicContactEmail) ?></a>
                                            </div>
                                        </article>
                                    <?php endif; ?>

                                    <?php if ($publicContactMobile !== ''): ?>
                                        <article class="contact-direct__item">
                                            <span class="contact-direct__icon" aria-hidden="true">
                                                <i class="bi bi-telephone"></i>
                                            </span>
                                            <div>
                                                <span class="contact-direct__label">Mobile Number</span>
                                                <a href="tel:<?= $escape($publicContactMobileHref) ?>"><?= $escape($publicContactMobile) ?></a>
                                            </div>
                                        </article>
                                    <?php endif; ?>

                                    <?php if ($publicContactBusinessHours !== ''): ?>
                                        <article class="contact-direct__item">
                                            <span class="contact-direct__icon" aria-hidden="true">
                                                <i class="bi bi-clock"></i>
                                            </span>
                                            <div>
                                                <span class="contact-direct__label">Business Hours</span>
                                                <strong><?= $escape($publicContactBusinessHours) ?></strong>
                                            </div>
                                        </article>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="contact-portal">
                                <div class="contact-portal__copy">
                                    <span>Online Booking Available</span>
                                    <strong>Reserve online and track your booking updates.</strong>
                                </div>

                                <div class="contact-portal__actions">
                                    <a class="public-button public-button--gold" href="registration.php">Reserve Your Event</a>
                                </div>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <div class="contact-success-modal" id="contactSuccessModal" hidden>
        <div class="contact-success-modal__backdrop" data-contact-success-close></div>
        <div class="contact-success-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="contactSuccessModalTitle" aria-describedby="contactSuccessModalDescription">
            <div class="contact-success-modal__content">
                <h2 id="contactSuccessModalTitle">Thank you for contacting us.</h2>
                <p id="contactSuccessModalDescription">We received your message and the admin will review your inquiry and get back to you soon.</p>
                <button class="public-button public-button--gold contact-success-modal__button" type="button" data-contact-success-close>Close</button>
            </div>
        </div>
    </div>

    <footer class="public-footer">
        <div class="public-shell">
            <div class="public-footer__bar">
                <div class="public-footer__top">
                    <div class="public-footer__brand">
                        <strong>Emarioh Catering Services</strong>
                        <p>Weddings, birthdays, and special celebrations.</p>
                    </div>

                    <nav class="public-footer__nav" aria-label="Footer navigation">
                        <a href="#about">About</a>
                        <a href="#packages">Packages</a>
                        <a href="#gallery">Gallery</a>
                        <a href="#contact">Contact</a>
                    </nav>
                </div>

                <p class="public-footer__copy">&copy; <span data-current-year></span> Emarioh Catering Services</p>
            </div>
        </div>
    </footer>

    <?= emarioh_render_vendor_runtime_assets(false); ?>
    <script src="assets/js/pages/public-page.js?v=20260412h"></script>
</body>
</html>
