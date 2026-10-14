<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';

$db = emarioh_db();
$currentUser = emarioh_require_page_role('client');
$clientProfile = emarioh_find_client_profile($db, (int) $currentUser['id']);
$bookedEventDates = emarioh_fetch_booked_event_dates($db);
$unpaidBlockingBooking = emarioh_find_client_unpaid_active_booking($db, (int) $currentUser['id']);
$activeBlockingBooking = emarioh_find_client_active_upcoming_booking($db, (int) $currentUser['id']);
$blockingBooking = $unpaidBlockingBooking ?? $activeBlockingBooking;
$bookingSubmissionLocked = $blockingBooking !== null;
$bookingSubmissionLockType = $unpaidBlockingBooking !== null ? 'unpaid' : ($activeBlockingBooking !== null ? 'active' : '');
$bookingSubmissionLockTitle = $bookingSubmissionLockType === 'unpaid' ? 'Unpaid Booking Pending' : 'Booking Pending Review';
$bookingSubmissionLockButtonLabel = $bookingSubmissionLockType === 'unpaid' ? 'Settle Billing First' : 'Waiting For Review';
$bookingSubmissionLockMessage = $bookingSubmissionLocked
    ? ($bookingSubmissionLockType === 'unpaid'
        ? emarioh_client_unpaid_booking_block_message($blockingBooking)
        : emarioh_client_active_booking_block_message($blockingBooking))
    : '';

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
    <link rel="stylesheet" href="assets/css/pages/client-bookings.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/client-portal-state.css">
    <link rel="stylesheet" href="assets/css/client-sidebar-parity.css?v=20260710a">
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
                            <?= emarioh_render_client_notification_nav_link($db, (int) $currentUser['id']) ?>
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
                    <div class="topbar-trailing">
                        <button class="booking-topbar-clear-btn btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 d-inline-flex align-items-center" id="bookingTopClearButton" type="button" data-bs-toggle="modal" data-bs-target="#bookingClearConfirmModal" title="Clear all fields">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>
                            <span class="d-none d-sm-inline">Clear All Fields</span>
                            <span class="d-sm-none">Clear</span>
                        </button>
                    </div>
                </header>

                <main class="dashboard-content client-dashboard-content">
                    <div class="client-book-event-layout">
                        <form class="booking-form booking-form--details" id="bookingFormShell" action="#" novalidate>
                            <section class="surface-card booking-unified-card">
                                <!-- Section 01: Schedule & Venue -->
                                <div class="booking-step-group">
                                    <div class="booking-step-header">
                                        <span class="booking-step-badge">01</span>
                                        <div>
                                            <h2 class="booking-step-title">Schedule &amp; Venue</h2>
                                            <p class="booking-step-subtitle">Choose your event date, start time, estimated guests, and location.</p>
                                        </div>
                                    </div>

                                    <div class="booking-form-grid">
                                        <div class="booking-field">
                                            <label for="eventType" class="form-label">Event Type</label>
                                            <select id="eventType" class="form-select" required>
                                                <option value="" selected>Select event type</option>
                                                <option value="Wedding">Wedding</option>
                                                <option value="Birthday">Birthday</option>
                                                <option value="Corporate Event">Corporate Event</option>
                                                <option value="Debut">Debut</option>
                                                <option value="Anniversary">Anniversary</option>
                                                <option value="Social Gathering / Reunion">Social Gathering / Reunion</option>
                                                <option value="Others">Others (Type own event)</option>
                                            </select>
                                            <div class="booking-event-type-wrap" id="eventTypeOtherGroup" hidden>
                                                <input type="text" id="eventTypeOther" class="form-control booking-event-type-input" placeholder="Type your event (e.g. Christening)">
                                                <button class="booking-event-type-chip-btn" type="button" id="eventTypeResetBtn" title="Back to event options">
                                                    <i class="bi bi-x-lg"></i>
                                                    <span>List</span>
                                                </button>
                                            </div>
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
                                            <input id="guestCount" class="form-control" type="number" placeholder="Example: 50" min="50" step="1" required>
                                            <p class="booking-field__hint" id="guestCountHint">Minimum of 50 guests required.</p>
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
                                    </div>
                                </div>

                                <div class="booking-step-divider"></div>

                                <!-- Section 02: Choose Package -->
                                <div class="booking-step-group">
                                    <div class="booking-step-header">
                                        <span class="booking-step-badge">02</span>
                                        <div>
                                            <h2 class="booking-step-title">Choose Package</h2>
                                            <p class="booking-step-subtitle">Select your catering category and preferred package rate.</p>
                                        </div>
                                    </div>

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
                                            <select id="packageOption" class="form-select" name="packageType">
                                                <option value="" disabled selected>-- Select a Package Option --</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Interactive Dish Selector -->
                                    <div class="booking-dish-selector-card mt-3 p-3 rounded-3" id="bookingDishSelectorCard" hidden>
                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2 border-bottom">
                                            <div>
                                                <h3 class="fs-6 fw-bold mb-0 text-dark">
                                                    <i class="bi bi-egg-fried text-primary me-1"></i> Select Your Menu / Dish Choices
                                                </h3>
                                                <p class="small text-secondary mb-0">Choose your preferred recipes for this package.</p>
                                            </div>
                                            <span class="badge bg-white text-secondary border small" id="dishSelectionCountBadge">5 Choices</span>
                                        </div>
                                        <div class="booking-dish-grid row g-3" id="bookingDishGrid">
                                            <!-- Dynamically populated by client-bookings-page.js -->
                                        </div>
                                    </div>
                                </div>

                                <div class="booking-step-divider"></div>

                                <!-- Section 03: Contact & Special Requests -->
                                <div class="booking-step-group">
                                    <div class="booking-step-header">
                                        <span class="booking-step-badge">03</span>
                                        <div>
                                            <h2 class="booking-step-title">Contact &amp; Special Requests</h2>
                                            <p class="booking-step-subtitle">Your contact details and any specific dish preferences or setup notes.</p>
                                        </div>
                                    </div>

                                    <div class="booking-form-grid">
                                        <div class="booking-field">
                                            <label for="primaryContact" class="form-label">Full Name</label>
                                            <input id="primaryContact" class="form-control" type="text" placeholder="Full name" value="<?= $escape($primaryContactValue) ?>" data-default-value="<?= $escape($primaryContactValue) ?>" required>
                                        </div>
                                        <div class="booking-field">
                                            <label for="primaryMobile" class="form-label">Mobile Number</label>
                                            <input id="primaryMobile" class="form-control" type="tel" placeholder="09XX XXX XXXX" value="<?= $escape($primaryMobileValue) ?>" data-default-value="<?= $escape($primaryMobileValue) ?>" required>
                                        </div>
                                        <div class="booking-field booking-field--full">
                                            <label for="alternateContact" class="form-label">Alternate Contact (Optional)</label>
                                            <input id="alternateContact" class="form-control" type="text" placeholder="Optional backup contact" value="<?= $escape($alternateContactValue) ?>" data-default-value="<?= $escape($alternateContactValue) ?>">
                                        </div>
                                        <div class="booking-field booking-field--full">
                                            <label for="eventNotes" class="form-label">Special Requests &amp; Additional Notes (Optional)</label>
                                            <textarea id="eventNotes" class="form-control" rows="3" placeholder="Tell us about your event theme/motif, guest allergies, dietary restrictions, or VIP setup notes."></textarea>
                                            <input id="bookingNotes" type="hidden">
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Area: Single Review & Submit Button -->
                                <div class="booking-step-divider"></div>
                                <div class="booking-form-submit-area">
                                    <div class="booking-submit-center-wrap text-center">
                                        <div class="d-flex flex-wrap align-items-center justify-content-center gap-3 w-100">
                                            <button class="booking-clear-fields-btn" id="bookingClearFieldsButton" type="button" data-bs-toggle="modal" data-bs-target="#bookingClearConfirmModal" title="Clear and reset all fields">
                                                <i class="bi bi-arrow-counterclockwise me-2"></i>
                                                <span>Clear All Fields</span>
                                            </button>
                                            <button class="client-action-button client-action-button--primary booking-review-submit-btn" id="bookingSubmitButton" type="submit"<?= $bookingSubmissionLocked ? ' disabled aria-disabled="true"' : '' ?>>
                                                <i class="bi bi-clipboard2-check me-2"></i>
                                                <span><?= $bookingSubmissionLocked ? $escape($bookingSubmissionLockButtonLabel) : 'Review &amp; Submit Booking Request →' ?></span>
                                            </button>
                                        </div>
                                        <span id="packageInclusionsCount" hidden></span>
                                        <div class="booking-submit-feedback" id="bookingSubmitFeedback" aria-live="polite" style="display: none;"></div>
                                    </div>
                                    <?php if ($bookingSubmissionLocked): ?>
                                        <div class="text-center mt-2">
                                            <a class="booking-submit-support__link small text-primary" href="client-my-bookings.php">View Current Booking</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </section>
                        </form>
                    </div>
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

    <div class="modal fade booking-modal" id="bookingDetailsModal" tabindex="-1" aria-labelledby="bookingDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content booking-modal__content">
                <div class="modal-header booking-modal__header">
                    <div>
                        <p class="panel-heading__eyebrow">Review &amp; Confirmation</p>
                        <h2 class="booking-modal__title" id="bookingDetailsModalLabel">Booking &amp; Menu Summary</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body booking-modal__body">
                    <!-- Schedule & Venue Recap -->
                    <div class="booking-modal-preview-grid mb-3">
                        <div class="modal-preview-item">
                            <i class="bi bi-calendar-event text-primary"></i>
                            <div>
                                <span class="modal-preview-label">Event Type</span>
                                <strong class="modal-preview-val" id="modalPreviewEventType">Event Celebration</strong>
                            </div>
                        </div>
                        <div class="modal-preview-item">
                            <i class="bi bi-clock text-primary"></i>
                            <div>
                                <span class="modal-preview-label">Date &amp; Time</span>
                                <strong class="modal-preview-val" id="modalPreviewSchedule">Date not selected</strong>
                            </div>
                        </div>
                        <div class="modal-preview-item">
                            <i class="bi bi-people text-primary"></i>
                            <div>
                                <span class="modal-preview-label">Guest Count</span>
                                <strong class="modal-preview-val" id="modalPreviewGuests">Min. 50 Guests</strong>
                            </div>
                        </div>
                        <div class="modal-preview-item">
                            <i class="bi bi-geo-alt text-primary"></i>
                            <div>
                                <span class="modal-preview-label">Venue</span>
                                <strong class="modal-preview-val" id="modalPreviewVenue">Own venue</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Package & Rate Overview Card -->
                    <div class="booking-modal-package-card mb-3 p-3 rounded-3 bg-light border">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2 pb-2 border-bottom">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="booking-package-summary__meta badge bg-white text-secondary border small" id="packageSummaryMeta">Per-head</span>
                                    <span class="booking-package-summary__badge badge bg-secondary-subtle text-secondary border small" id="packageSummaryBadge">Minimum of 50 persons</span>
                                </div>
                                <h3 class="booking-package-summary__title fs-5 fw-bold mb-1 text-dark" id="packageSummaryTitle">Selected Package</h3>
                                <p class="booking-package-summary__description small mb-0 text-secondary" id="packageSummaryDescription"></p>
                            </div>
                            <div class="text-end">
                                <span class="small text-secondary d-block">Package Rate</span>
                                <strong class="text-success fs-5" id="modalPreviewPackageRate"></strong>
                            </div>
                        </div>
                        <div class="booking-package-summary__tags mb-2 d-none" id="packageSummaryTags" hidden></div>
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 small text-secondary pt-2 border-top">
                            <span id="modalPreviewDownPaymentInfo"><i class="bi bi-shield-check text-success me-1"></i> QRPh down payment available</span>
                            <span id="modalPreviewTotalEstimate" class="fw-bold text-dark"></span>
                        </div>
                    </div>

                    <!-- Menu & Dishes List -->
                    <div class="booking-modal-menu-section">
                        <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
                            <h4 class="fs-6 fw-bold mb-0 text-dark">
                                <i class="bi bi-card-checklist text-success me-1"></i> Dishes &amp; Inclusions Included
                            </h4>
                            <span class="badge bg-light text-secondary border small" id="modalInclusionsCountBadge">0 Items</span>
                        </div>
                        <ul class="booking-summary-menu-list booking-summary-menu-list--modal" id="packageSummaryInclusions">
                            <!-- Populated dynamically by JavaScript -->
                        </ul>
                    </div>
                </div>
                <div class="modal-footer booking-modal__footer d-flex align-items-center justify-content-between">
                    <button type="button" class="booking-modal-btn-close" data-bs-dismiss="modal">
                        <i class="bi bi-arrow-left me-1"></i> Back to Form
                    </button>
                    <button type="button" class="client-action-button client-action-button--primary px-4 py-2 fw-bold" id="modalSubmitProxyButton">
                        <i class="bi bi-check2-circle me-1"></i> Confirm &amp; Submit Request →
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade booking-modal" id="bookingClearConfirmModal" tabindex="-1" aria-labelledby="bookingClearConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content booking-modal__content">
                <div class="modal-header booking-modal__header">
                    <div>
                        <p class="booking-modal__eyebrow">Reset Form</p>
                        <h2 class="booking-modal__title" id="bookingClearConfirmModalLabel">Clear All Fields?</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body booking-modal__body">
                    <p class="mb-2 fw-semibold text-dark" style="font-size: 0.98rem; line-height: 1.55;">
                        Are you sure you want to clear all fields in this booking form?
                    </p>
                    <p class="small text-secondary mb-0" style="line-height: 1.6;">
                        This will reset your selected event type, date, time, guest count, venue, package choices, and any custom notes.
                    </p>
                </div>
                <div class="modal-footer booking-modal__footer d-flex align-items-center justify-content-end gap-2">
                    <button type="button" class="booking-clear-modal-btn booking-clear-modal-btn--cancel" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" class="booking-clear-modal-btn booking-clear-modal-btn--confirm" id="confirmClearAllFieldsBtn">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Clear Fields
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?= emarioh_render_vendor_runtime_assets(true); ?>
    <script src="assets/js/auth-api.js"></script>
    <script src="assets/js/logout-confirmation.js?v=20260706c"></script>
    <script>
        window.EmariohServerPackageCatalog = <?= $packageCatalogJson ?>;
    </script>
    <script src="assets/js/package-catalog.js?v=<?= time() ?>"></script>
    <script src="assets/js/pages/client-bookings-page.js?v=<?= time() ?>"></script>
</body>
</html>


