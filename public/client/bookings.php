<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';

$db = emarioh_db();
$currentUser = emarioh_require_page_role('client');
$clientProfile = emarioh_find_client_profile($db, (int) $currentUser['id']);
$bookedEventDates = emarioh_fetch_booked_event_dates($db);

$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$primaryContactValue = (string) ($currentUser['full_name'] ?? '');
$primaryMobileValue = emarioh_format_mobile((string) ($currentUser['mobile'] ?? ''));
$alternateContactValue = trim((string) ($clientProfile['alternate_contact'] ?? ''));
$packageCatalogJson = json_encode(
    emarioh_fetch_service_package_catalog($db),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?: '[]';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emarioh Catering Services Client Book Event</title>
    <?= emarioh_render_vendor_head_assets(); ?>
    <link rel="stylesheet" href="assets/css/pages/client-bookings.css?v=20260418v">
    <link rel="stylesheet" href="assets/css/client-portal-state.css">
    <link rel="stylesheet" href="assets/css/client-sidebar-parity.css?v=20260418e">
</head>
<body class="dashboard-page client-dashboard-page client-book-event-page client-page--sticky-topbar" data-auth-guard="client">
    <div class="dashboard-shell container-fluid">
        <div class="dashboard-frame">
            <aside class="dashboard-sidebar offcanvas-xl offcanvas-start border-0" tabindex="-1" id="dashboardSidebar" aria-labelledby="dashboardSidebarLabel">
                <div class="offcanvas-header sidebar-mobile-header d-xl-none">
                    <div class="sidebar-mobile-brand">
                        <span class="sidebar-brand__frame sidebar-brand__frame--small">
                            <img src="assets/images/logo.jpg" alt="Emarioh Catering Services" class="sidebar-brand__logo">
                        </span>
                        <div class="sidebar-brand__copy">
                            <span class="sidebar-brand__name">Emarioh</span>
                            <span class="sidebar-brand__sub">Client Portal</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body p-0">
                    <div class="sidebar-inner d-flex flex-column h-100">
                        <div class="sidebar-brand">
                            <a href="client-dashboard.php" class="sidebar-brand__link text-decoration-none" id="dashboardSidebarLabel" aria-label="Emarioh Catering Services Client Portal">
                                <span class="sidebar-brand__frame"><img src="assets/images/logo.jpg" alt="Emarioh Catering Services" class="sidebar-brand__logo"></span>
                                <span class="sidebar-brand__copy"><span class="sidebar-brand__name">Emarioh</span><span class="sidebar-brand__sub">Client Portal</span></span>
                            </a>
                        </div>
                        <div class="sidebar-divider" aria-hidden="true"></div>
                        <nav class="dashboard-nav nav flex-column" aria-label="Client portal navigation">
                            <a class="nav-link" href="client-dashboard.php"><span class="nav-link__icon"><i class="bi bi-grid-1x2-fill"></i></span><span>Dashboard</span></a>
                            <a class="nav-link active" href="client-bookings.php" aria-current="page"><span class="nav-link__icon"><i class="bi bi-calendar2-plus"></i></span><span>Book Event</span></a>
                            <a class="nav-link" href="client-my-bookings.php"><span class="nav-link__icon"><i class="bi bi-calendar2-check"></i></span><span>My Bookings</span></a>
                            <a class="nav-link" href="client-billing.php"><span class="nav-link__icon"><i class="bi bi-receipt-cutoff"></i></span><span>Billing</span></a>
                            <a class="nav-link" href="client-preferences.php"><span class="nav-link__icon"><i class="bi bi-gear"></i></span><span>Account Settings</span></a>
                        </nav>
                        <div class="sidebar-footer mt-auto">
                            <a class="sidebar-logout" href="logout.php" data-logout-link><span class="sidebar-logout__icon"><i class="bi bi-box-arrow-right"></i></span><span class="sidebar-logout__label">Log out</span></a>
                        </div>
                    </div>
                </div>
            </aside>

            <div class="dashboard-main">
                <header class="dashboard-topbar">
                    <div class="topbar-leading">
                        <button class="btn mobile-menu-button d-xl-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#dashboardSidebar" aria-controls="dashboardSidebar" aria-label="Open navigation"><i class="bi bi-list"></i></button>
                        <div class="topbar-copy"><h1 class="topbar-copy__title">Book Event</h1></div>
                    </div>
                </header>

                <main class="dashboard-content client-dashboard-content">
                    <section class="client-dashboard-grid" id="bookingFormShell">
                        <div class="content-main">
                            <section class="surface-card booking-section-card booking-section-card--event">
                                <div class="panel-heading"><div><p class="panel-heading__eyebrow">Booking Request</p><h2>Event Details</h2></div></div>
                                <form class="booking-form booking-form--event" action="#">
                                    <div class="booking-form-grid">
                                        <div class="booking-field">
                                            <label for="eventType" class="form-label">Event Type</label>
                                            <select id="eventType" class="form-select" required>
                                                <option value="" selected>Select event type</option>
                                                <option>Wedding</option>
                                                <option>Birthday</option>
                                                <option>Corporate Event</option>
                                                <option>Debut</option>
                                                <option>Anniversary</option>
                                                <option>Social Gathering / Reunion</option>
                                            </select>
                                        </div>
                                        <div class="booking-field">
                                            <label for="eventDate" class="form-label">Event Date</label>
                                            <div class="booking-date-picker" id="eventDatePicker" data-booked-dates="<?= $escape(implode(',', $bookedEventDates)) ?>">
                                                <input id="eventDate" name="eventDate" type="hidden">
                                                <button class="booking-date-picker__trigger" id="eventDateTrigger" type="button" aria-haspopup="dialog" aria-expanded="false" aria-controls="eventDateCalendar">
                                                    <span class="booking-date-picker__value" id="eventDateValue">Select date</span>
                                                    <i class="bi bi-calendar3"></i>
                                                </button>
                                                <div class="booking-date-picker__panel" id="eventDateCalendar" hidden>
                                                    <div class="booking-date-picker__header">
                                                        <button class="booking-date-picker__nav" type="button" data-calendar-nav="-1" aria-label="Previous month">
                                                            <i class="bi bi-chevron-left"></i>
                                                        </button>
                                                        <p class="booking-date-picker__month" id="eventDateMonthLabel">Month Year</p>
                                                        <button class="booking-date-picker__nav" type="button" data-calendar-nav="1" aria-label="Next month">
                                                            <i class="bi bi-chevron-right"></i>
                                                        </button>
                                                    </div>
                                                    <div class="booking-date-picker__weekdays" aria-hidden="true">
                                                        <span>Sun</span>
                                                        <span>Mon</span>
                                                        <span>Tue</span>
                                                        <span>Wed</span>
                                                        <span>Thu</span>
                                                        <span>Fri</span>
                                                        <span>Sat</span>
                                                    </div>
                                                    <div class="booking-date-picker__grid" id="eventDateGrid" role="grid" aria-labelledby="eventDateMonthLabel"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="booking-field">
                                            <label for="eventTime" class="form-label">Start Time</label>
                                            <input id="eventTime" class="form-control" type="time" required>
                                        </div>
                                        <div class="booking-field">
                                            <label for="guestCount" class="form-label">Estimated Guest Count</label>
                                            <input id="guestCount" class="form-control" type="number" min="20" placeholder="Example: 120" required>
                                        </div>
                                        <div class="booking-field booking-field--full">
                                            <span class="form-label">Venue Setup</span>
                                            <div class="booking-venue-toggle" role="radiogroup" aria-label="Venue setup">
                                                <label class="booking-venue-toggle__option">
                                                    <input class="booking-venue-toggle__input" type="radio" name="venueOption" value="own" checked>
                                                    <span class="booking-venue-toggle__button">Own venue</span>
                                                </label>
                                                <label class="booking-venue-toggle__option">
                                                    <input class="booking-venue-toggle__input" type="radio" name="venueOption" value="emarioh">
                                                    <span class="booking-venue-toggle__button">Emarioh venue</span>
                                                </label>
                                            </div>
                                            <p class="booking-field__hint" id="venueSetupHint">Add venue name and city.</p>
                                        </div>
                                        <div class="booking-field booking-field--full" id="ownVenueField">
                                            <label for="venue" class="form-label">Venue / Event Location</label>
                                            <input id="venue" class="form-control" type="text" placeholder="Venue name, barangay, city or municipality">
                                        </div>
                                        <div class="booking-field booking-field--full booking-venue-panel" id="emariohVenueField" hidden>
                                            <div class="booking-venue-panel__body">
                                                <strong>Emarioh venue selected</strong>
                                                <p>The in-house venue request will be checked for availability during review.</p>
                                            </div>
                                        </div>
                                        <div class="booking-field booking-field--full">
                                            <label for="eventNotes" class="form-label">Brief Event Description</label>
                                            <textarea id="eventNotes" class="form-control" rows="3" placeholder="Tell us about your event setup, preferred service time, and any important details."></textarea>
                                        </div>
                                    </div>
                                </form>
                            </section>

                            <section class="surface-card">
                                <div class="panel-heading"><div><h2>Package</h2></div></div>
                                <form class="booking-form booking-form--package" action="#">
                                    <div class="booking-package-picker">
                                        <div class="booking-form-grid booking-form-grid--package-picker">
                                            <div class="booking-field">
                                                <label for="packageCategory" class="form-label">Package Category</label>
                                                <select id="packageCategory" class="form-select" name="packageCategory">
                                                    <option value="per-head" selected>Per-Head Packages</option>
                                                    <option value="celebration">Celebration Packages</option>
                                                </select>
                                            </div>
                                            <div class="booking-field">
                                                <label for="packageOption" class="form-label">Package Option</label>
                                                <select id="packageOption" class="form-select" name="packageType"></select>
                                            </div>
                                        </div>
                                    </div>

                                </form>
                            </section>

                            <section class="surface-card booking-section-card booking-section-card--details">
                                <div class="panel-heading"><div><h2>Contact &amp; Notes</h2></div></div>
                                <form class="booking-form booking-form--details" action="#">
                                    <div class="booking-form-group">
                                        <div class="booking-form-group__header"><h3>Contact Details</h3></div>
                                        <div class="booking-form-grid booking-form-grid--details">
                                            <div class="booking-field">
                                                <label for="primaryContact" class="form-label">Full Name</label>
                                                <input id="primaryContact" class="form-control" type="text" placeholder="Full name" value="<?= $escape($primaryContactValue) ?>" required>
                                            </div>
                                            <div class="booking-field">
                                                <label for="primaryMobile" class="form-label">Mobile Number</label>
                                                <input id="primaryMobile" class="form-control" type="tel" placeholder="09XX XXX XXXX" value="<?= $escape($primaryMobileValue) ?>" required>
                                            </div>
                                            <div class="booking-field">
                                                <label for="alternateContact" class="form-label">Alternate Contact</label>
                                                <input id="alternateContact" class="form-control" type="text" placeholder="Optional" value="<?= $escape($alternateContactValue) ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="booking-form-group booking-form-group--notes">
                                        <div class="booking-form-grid booking-form-grid--details">
                                            <div class="booking-field booking-field--full">
                                                <label for="bookingNotes" class="form-label">Notes (Optional)</label>
                                                <textarea id="bookingNotes" class="form-control" rows="3" placeholder="Motif, allergies, VIP notes, or setup requests."></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="booking-submit-box">
                                        <p class="booking-submit-box__title" id="bookingSubmitTitle">Ready To Submit?</p>
                                        <p class="booking-submit-box__text" id="bookingSubmitText">After submission, your request will appear in My Bookings while the team checks availability and prepares billing.</p>
                                        <div class="booking-submit-actions">
                                            <button class="client-action-button client-action-button--primary client-action-button--block" id="bookingSubmitButton" type="submit">Submit Request</button>
                                        </div>
                                        <p class="booking-submit-feedback" id="bookingSubmitFeedback" aria-live="polite"></p>
                                        <div class="booking-submit-links">
                                            <button class="booking-submit-support__link" type="button">Save draft</button>
                                            <button class="booking-submit-support__link" type="button" data-bs-toggle="modal" data-bs-target="#bookingChecklistModal">Review checklist</button>
                                        </div>
                                    </div>
                                </form>
                            </section>
                        </div>
                    </section>
                </main>

                <?= emarioh_render_client_mobile_nav(basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'client-dashboard.php'))) ?>
            </div>
        </div>
    </div>

    <div class="modal fade booking-modal" id="bookingFlowModal" tabindex="-1" aria-labelledby="bookingFlowModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content booking-modal__content">
                <div class="modal-header booking-modal__header">
                    <div>
                        <p class="panel-heading__eyebrow">Guide</p>
                        <h2 class="booking-modal__title" id="bookingFlowModalLabel">Booking Flow</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body booking-modal__body">
                    <div class="booking-step-list">
                        <article class="booking-step-item">
                            <span class="booking-step-item__number">1</span>
                            <div class="booking-step-item__body"><h3>Enter event details</h3><p>Share your date, venue choice, guest estimate, and core event information.</p></div>
                        </article>
                        <article class="booking-step-item">
                            <span class="booking-step-item__number">2</span>
                            <div class="booking-step-item__body"><h3>Choose service and package</h3><p>Select from the same public-page offers and pick the package that fits your event size.</p></div>
                        </article>
                        <article class="booking-step-item">
                            <span class="booking-step-item__number">3</span>
                            <div class="booking-step-item__body"><h3>Add notes and contacts</h3><p>Share motif, dietary requests, and the contact details the team should use for coordination.</p></div>
                        </article>
                        <article class="booking-step-item">
                            <span class="booking-step-item__number">4</span>
                            <div class="booking-step-item__body"><h3>Submit request</h3><p>Your request moves to My Bookings under Pending while the team reviews your event details.</p></div>
                        </article>
                        <article class="booking-step-item">
                            <span class="booking-step-item__number">5</span>
                            <div class="booking-step-item__body"><h3>Admin reviews availability</h3><p>We check the date, venue setup, guest count, and package request before confirming the slot.</p></div>
                        </article>
                        <article class="booking-step-item">
                            <span class="booking-step-item__number">6</span>
                            <div class="booking-step-item__body"><h3>Billing opens after approval</h3><p>Once approved, the Billing page shows the deposit invoice so you can settle the first payment and secure the booking.</p></div>
                        </article>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade booking-modal" id="bookingChecklistModal" tabindex="-1" aria-labelledby="bookingChecklistModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content booking-modal__content">
                <div class="modal-header booking-modal__header">
                    <div>
                        <p class="panel-heading__eyebrow">Checklist</p>
                        <h2 class="booking-modal__title" id="bookingChecklistModalLabel">Before You Submit</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body booking-modal__body">
                    <div class="client-checklist">
                        <article class="client-checklist__item"><span class="client-checklist__icon client-checklist__icon--pending"><i class="bi bi-calendar-week"></i></span><div class="client-checklist__body"><h3>Prepare your preferred date</h3><p>Choose your target event date and one backup option in case the first slot is unavailable.</p></div></article>
                        <article class="client-checklist__item"><span class="client-checklist__icon client-checklist__icon--pending"><i class="bi bi-geo-alt"></i></span><div class="client-checklist__body"><h3>Confirm venue details</h3><p>Use your own venue address or request the Emarioh venue so the team can review logistics early.</p></div></article>
                        <article class="client-checklist__item"><span class="client-checklist__icon client-checklist__icon--pending"><i class="bi bi-chat-square-text"></i></span><div class="client-checklist__body"><h3>List dietary or service notes</h3><p>Add any allergy concerns, vegetarian count, VIP service notes, or setup concerns before submission.</p></div></article>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?= emarioh_render_vendor_runtime_assets(true); ?>
    <script src="assets/js/auth-api.js"></script>
    <script src="assets/js/logout-confirmation.js?v=20260418a"></script>
    <script>
        window.EmariohServerPackageCatalog = <?= $packageCatalogJson ?>;
    </script>
    <script src="assets/js/package-catalog.js?v=20260413a"></script>
    <script src="assets/js/pages/client-bookings-page.js?v=20260412c"></script>
</body>
</html>


