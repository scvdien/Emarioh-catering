<?php
declare(strict_types=1);

if (!isset($db, $currentUser, $escape)) {
    http_response_code(403);
    exit;
}

$adminHubName = trim((string) ($currentUser['full_name'] ?? '')) ?: 'Emarioh Admin';
$adminHubMobileRaw = trim((string) ($currentUser['mobile'] ?? ''));
$adminHubMobile = $adminHubMobileRaw !== '' ? emarioh_format_mobile($adminHubMobileRaw) : '';
$adminHubUnreadInquiries = emarioh_count_unread_website_inquiries($db);
$adminProfileHubClasses = isset($adminProfileHubClasses)
    ? trim((string) $adminProfileHubClasses)
    : 'surface-card settings-profile-hub admin-profile-hub';
$adminProfileHubTitleId = isset($adminProfileHubTitleId)
    ? trim((string) $adminProfileHubTitleId)
    : 'adminProfileHubTitle';
$adminProfileHubHasDataAttr = !empty($adminProfileHubHasDataAttr);
?>
<section class="<?= $escape($adminProfileHubClasses) ?>"<?= $adminProfileHubHasDataAttr ? ' data-settings-hub' : '' ?> aria-labelledby="<?= $escape($adminProfileHubTitleId) ?>">
    <div class="admin-profile-hub__inner">
        <a class="admin-profile-card" href="admin-settings-account.php" aria-label="Open admin account settings">
            <span class="admin-profile-card__avatar">
                <img src="assets/images/logo.jpg" alt="Emarioh Catering Services">
            </span>
            <span class="admin-profile-card__body">
                <span class="admin-profile-card__badge">Administrator</span>
                <strong class="admin-profile-card__name" id="<?= $escape($adminProfileHubTitleId) ?>"><?= $escape($adminHubName) ?></strong>
                <?php if ($adminHubMobile !== ''): ?>
                    <span class="admin-profile-card__meta">
                        <i class="bi bi-phone" aria-hidden="true"></i>
                        <span><?= $escape($adminHubMobile) ?></span>
                    </span>
                <?php else: ?>
                    <span class="admin-profile-card__meta">Manage account &amp; system settings</span>
                <?php endif; ?>
            </span>
            <span class="admin-profile-card__edit" aria-hidden="true">
                <span>Edit</span>
                <i class="bi bi-chevron-right"></i>
            </span>
        </a>

        <div class="admin-profile-group">
            <p class="admin-profile-group__label">Account &amp; Clients</p>
            <div class="admin-profile-list">
                <a class="admin-profile-item" href="admin-settings-account.php">
                    <span class="admin-profile-item__icon admin-profile-item__icon--emerald" aria-hidden="true">
                        <i class="bi bi-person-badge"></i>
                    </span>
                    <span class="admin-profile-item__copy">
                        <strong>Admin Account</strong>
                        <span>Name, mobile number, and password</span>
                    </span>
                    <span class="admin-profile-item__trail" aria-hidden="true">
                        <i class="bi bi-chevron-right"></i>
                    </span>
                </a>

                <a class="admin-profile-item" href="admin-clients.php">
                    <span class="admin-profile-item__icon admin-profile-item__icon--emerald" aria-hidden="true">
                        <i class="bi bi-people"></i>
                    </span>
                    <span class="admin-profile-item__copy">
                        <strong>Clients</strong>
                        <span>Registered clients and booking records</span>
                    </span>
                    <span class="admin-profile-item__trail" aria-hidden="true">
                        <i class="bi bi-chevron-right"></i>
                    </span>
                </a>

                <a class="admin-profile-item" href="admin-inquiries.php">
                    <span class="admin-profile-item__icon admin-profile-item__icon--gold" aria-hidden="true">
                        <i class="bi bi-envelope-paper"></i>
                    </span>
                    <span class="admin-profile-item__copy">
                        <strong>Website Inquiries</strong>
                        <span>Customer messages from the public page</span>
                    </span>
                    <span class="admin-profile-item__trail">
                        <?php if ($adminHubUnreadInquiries > 0): ?>
                            <span class="admin-profile-item__badge" aria-label="<?= $escape((string) $adminHubUnreadInquiries) ?> unread inquiries"><?= $escape((string) min($adminHubUnreadInquiries, 99)) ?></span>
                        <?php endif; ?>
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </span>
                </a>
            </div>
        </div>

        <div class="admin-profile-group">
            <p class="admin-profile-group__label">System Settings</p>
            <div class="admin-profile-list">
                <a class="admin-profile-item" href="admin-settings-public-page.php">
                    <span class="admin-profile-item__icon admin-profile-item__icon--gold" aria-hidden="true">
                        <i class="bi bi-window-stack"></i>
                    </span>
                    <span class="admin-profile-item__copy">
                        <strong>Public Page</strong>
                        <span>Hero image, services, gallery, and contacts</span>
                    </span>
                    <span class="admin-profile-item__trail" aria-hidden="true">
                        <i class="bi bi-chevron-right"></i>
                    </span>
                </a>

                <a class="admin-profile-item" href="admin-settings-payment.php">
                    <span class="admin-profile-item__icon admin-profile-item__icon--gold" aria-hidden="true">
                        <i class="bi bi-wallet2"></i>
                    </span>
                    <span class="admin-profile-item__copy">
                        <strong>Payment Rules</strong>
                        <span>Down payment rules for each service</span>
                    </span>
                    <span class="admin-profile-item__trail" aria-hidden="true">
                        <i class="bi bi-chevron-right"></i>
                    </span>
                </a>

                <a class="admin-profile-item" href="admin-settings-sms.php">
                    <span class="admin-profile-item__icon admin-profile-item__icon--gold" aria-hidden="true">
                        <i class="bi bi-chat-dots"></i>
                    </span>
                    <span class="admin-profile-item__copy">
                        <strong>SMS Templates</strong>
                        <span>Message templates and placeholders</span>
                    </span>
                    <span class="admin-profile-item__trail" aria-hidden="true">
                        <i class="bi bi-chevron-right"></i>
                    </span>
                </a>
            </div>
        </div>

        <a class="admin-profile-logout" href="logout.php" data-logout-link>
            <span class="admin-profile-item__icon admin-profile-item__icon--danger" aria-hidden="true">
                <i class="bi bi-box-arrow-right"></i>
            </span>
            <span class="admin-profile-item__copy">
                <strong>Log Out</strong>
                <span>Sign out of the admin dashboard</span>
            </span>
            <span class="admin-profile-item__trail" aria-hidden="true">
                <i class="bi bi-chevron-right"></i>
            </span>
        </a>
    </div>
</section>
