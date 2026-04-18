<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

emarioh_require_method('POST');

$db = emarioh_db();
emarioh_require_role('admin');
$data = emarioh_request_data();
$packages = $data['packages'] ?? null;

if (!is_array($packages) || $packages === []) {
    emarioh_fail('No package rules were provided.');
}

try {
    $db->beginTransaction();
    $incomingPackageCodes = [];

    foreach ($packages as $index => $rawPackage) {
        if (!is_array($rawPackage)) {
            throw new RuntimeException('Invalid package payload.');
        }

        $packageCode = trim((string) ($rawPackage['id'] ?? ''));
        $packageName = trim((string) ($rawPackage['name'] ?? ''));
        $groupKey = trim((string) ($rawPackage['group'] ?? 'per-head'));
        $categoryLabel = trim((string) ($rawPackage['category'] ?? ''));
        $guestLabel = trim((string) ($rawPackage['guestLabel'] ?? ''));
        $rateLabel = trim((string) ($rawPackage['rateLabel'] ?? ''));
        $status = trim((string) ($rawPackage['status'] ?? 'review'));
        $description = trim((string) ($rawPackage['description'] ?? ''));
        $allowDownPayment = !empty($rawPackage['allowDownPayment']);
        $downPaymentAmount = trim((string) ($rawPackage['downPaymentAmount'] ?? ''));
        $pricingTiers = is_array($rawPackage['pricingTiers'] ?? null) ? $rawPackage['pricingTiers'] : [];
        $downPaymentTierRows = is_array($rawPackage['downPaymentTiers'] ?? null) ? $rawPackage['downPaymentTiers'] : [];
        $tagRows = is_array($rawPackage['tags'] ?? null) ? $rawPackage['tags'] : [];
        $inclusionRows = is_array($rawPackage['inclusions'] ?? null) ? $rawPackage['inclusions'] : [];

        if ($packageCode === '' || $packageName === '' || $categoryLabel === '' || $guestLabel === '' || $rateLabel === '') {
            throw new RuntimeException(sprintf('Package #%d is missing required fields.', $index + 1));
        }

        $incomingPackageCodes[] = $packageCode;

        if (!in_array($groupKey, ['per-head', 'celebration'], true)) {
            $groupKey = 'per-head';
        }

        if (!in_array($status, ['active', 'review', 'inactive'], true)) {
            $status = 'review';
        }

        $existingPackage = emarioh_find_service_package_by_code($db, $packageCode);

        if ($existingPackage) {
            $db->prepare('
                UPDATE service_packages
                SET group_key = :group_key,
                    name = :name,
                    category_label = :category_label,
                    guest_label = :guest_label,
                    rate_label = :rate_label,
                    description = :description,
                    status = :status,
                    allow_down_payment = :allow_down_payment,
                    down_payment_amount = :down_payment_amount,
                    sort_order = :sort_order
                WHERE id = :id
            ')->execute([
                ':group_key' => $groupKey,
                ':name' => $packageName,
                ':category_label' => $categoryLabel,
                ':guest_label' => $guestLabel,
                ':rate_label' => $rateLabel,
                ':description' => $description === '' ? null : $description,
                ':status' => $status,
                ':allow_down_payment' => $allowDownPayment ? 1 : 0,
                ':down_payment_amount' => $downPaymentAmount === '' ? null : $downPaymentAmount,
                ':sort_order' => $index + 1,
                ':id' => (int) $existingPackage['id'],
            ]);

            $packageId = (int) $existingPackage['id'];
        } else {
            $db->prepare('
                INSERT INTO service_packages (
                    package_code,
                    group_key,
                    name,
                    category_label,
                    guest_label,
                    rate_label,
                    description,
                    status,
                    allow_down_payment,
                    down_payment_amount,
                    sort_order
                ) VALUES (
                    :package_code,
                    :group_key,
                    :name,
                    :category_label,
                    :guest_label,
                    :rate_label,
                    :description,
                    :status,
                    :allow_down_payment,
                    :down_payment_amount,
                    :sort_order
                )
            ')->execute([
                ':package_code' => $packageCode,
                ':group_key' => $groupKey,
                ':name' => $packageName,
                ':category_label' => $categoryLabel,
                ':guest_label' => $guestLabel,
                ':rate_label' => $rateLabel,
                ':description' => $description === '' ? null : $description,
                ':status' => $status,
                ':allow_down_payment' => $allowDownPayment ? 1 : 0,
                ':down_payment_amount' => $downPaymentAmount === '' ? null : $downPaymentAmount,
                ':sort_order' => $index + 1,
            ]);

            $packageId = (int) $db->lastInsertId();
        }

        $db->prepare('DELETE FROM package_pricing_tiers WHERE package_id = :package_id')->execute([
            ':package_id' => $packageId,
        ]);
        $db->prepare('DELETE FROM package_tags WHERE package_id = :package_id')->execute([
            ':package_id' => $packageId,
        ]);
        $db->prepare('DELETE FROM package_inclusions WHERE package_id = :package_id')->execute([
            ':package_id' => $packageId,
        ]);

        $downPaymentTierMap = [];
        foreach ($downPaymentTierRows as $tierRow) {
            if (!is_array($tierRow)) {
                continue;
            }

            $tierLabel = trim((string) ($tierRow['label'] ?? ''));

            if ($tierLabel === '') {
                continue;
            }

            $downPaymentTierMap[strtolower($tierLabel)] = trim((string) ($tierRow['amount'] ?? ''));
        }

        $validPricingTierCount = count(array_filter($pricingTiers, static function ($tierRow): bool {
            if (!is_array($tierRow)) {
                return false;
            }

            return trim((string) ($tierRow['label'] ?? '')) !== ''
                && trim((string) ($tierRow['price'] ?? '')) !== '';
        }));

        if ($validPricingTierCount === 1 && $pricingTiers !== []) {
            $singleTierLabel = trim((string) ($pricingTiers[0]['label'] ?? ''));
            $singleTierAmount = $singleTierLabel !== ''
                ? trim((string) ($downPaymentTierMap[strtolower($singleTierLabel)] ?? ''))
                : '';

            if ($singleTierAmount !== '') {
                $downPaymentAmount = $singleTierAmount;
            }
        }

        foreach ($pricingTiers as $tierIndex => $tierRow) {
            if (!is_array($tierRow)) {
                continue;
            }

            $tierLabel = trim((string) ($tierRow['label'] ?? ''));
            $priceLabel = trim((string) ($tierRow['price'] ?? ''));

            if ($tierLabel === '' || $priceLabel === '') {
                continue;
            }

            $db->prepare('
                INSERT INTO package_pricing_tiers (
                    package_id,
                    tier_label,
                    guest_count,
                    price_label,
                    price_amount,
                    down_payment_amount,
                    sort_order
                ) VALUES (
                    :package_id,
                    :tier_label,
                    :guest_count,
                    :price_label,
                    :price_amount,
                    :down_payment_amount,
                    :sort_order
                )
            ')->execute([
                ':package_id' => $packageId,
                ':tier_label' => $tierLabel,
                ':guest_count' => emarioh_parse_guest_count_hint($tierLabel),
                ':price_label' => $priceLabel,
                ':price_amount' => emarioh_parse_money_amount($priceLabel),
                ':down_payment_amount' => ($downPaymentTierMap[strtolower($tierLabel)] ?? '') !== ''
                    ? $downPaymentTierMap[strtolower($tierLabel)]
                    : null,
                ':sort_order' => $tierIndex + 1,
            ]);
        }

        foreach ($tagRows as $tagIndex => $tagRow) {
            $tagText = trim((string) $tagRow);

            if ($tagText === '') {
                continue;
            }

            $db->prepare('
                INSERT INTO package_tags (
                    package_id,
                    tag_text,
                    sort_order
                ) VALUES (
                    :package_id,
                    :tag_text,
                    :sort_order
                )
            ')->execute([
                ':package_id' => $packageId,
                ':tag_text' => $tagText,
                ':sort_order' => $tagIndex + 1,
            ]);
        }

        foreach ($inclusionRows as $inclusionIndex => $inclusionRow) {
            $inclusionText = trim((string) $inclusionRow);

            if ($inclusionText === '') {
                continue;
            }

            $db->prepare('
                INSERT INTO package_inclusions (
                    package_id,
                    inclusion_text,
                    sort_order
                ) VALUES (
                    :package_id,
                    :inclusion_text,
                    :sort_order
                )
            ')->execute([
                ':package_id' => $packageId,
                ':inclusion_text' => $inclusionText,
                ':sort_order' => $inclusionIndex + 1,
            ]);
        }
    }

    if ($incomingPackageCodes !== []) {
        $placeholders = implode(', ', array_fill(0, count($incomingPackageCodes), '?'));
        $staleStatement = $db->prepare("
            SELECT id
            FROM service_packages
            WHERE package_code NOT IN ($placeholders)
        ");
        $staleStatement->execute($incomingPackageCodes);
        $stalePackageIds = array_values(array_map(
            static fn ($value): int => (int) $value,
            $staleStatement->fetchAll(PDO::FETCH_COLUMN)
        ));

        foreach ($stalePackageIds as $stalePackageId) {
            if ($stalePackageId < 1) {
                continue;
            }

            $bookingCountStatement = $db->prepare('SELECT COUNT(*) FROM booking_requests WHERE package_id = :package_id');
            $bookingCountStatement->execute([
                ':package_id' => $stalePackageId,
            ]);
            $bookingCount = (int) $bookingCountStatement->fetchColumn();

            if ($bookingCount > 0) {
                $db->prepare('UPDATE service_packages SET status = :status WHERE id = :id')->execute([
                    ':status' => 'inactive',
                    ':id' => $stalePackageId,
                ]);
                continue;
            }

            $db->prepare('DELETE FROM service_packages WHERE id = :id')->execute([
                ':id' => $stalePackageId,
            ]);
        }
    }

    $db->commit();
} catch (Throwable $throwable) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    emarioh_fail(
        $throwable instanceof RuntimeException
            ? $throwable->getMessage()
            : 'Service rules could not be saved right now.',
        500
    );
}

emarioh_success([
    'message' => 'Service rules saved successfully.',
    'catalog' => emarioh_fetch_service_package_catalog($db),
]);
