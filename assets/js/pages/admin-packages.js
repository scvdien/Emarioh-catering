document.addEventListener("DOMContentLoaded", () => {
    const catalogApi = window.EmariohPackageCatalog;

    if (!catalogApi) {
        return;
    }

    const packageForm = document.getElementById("packageManagerForm");
    const packageIdField = document.getElementById("packageId");
    const packageNameField = document.getElementById("packageName");
    const packageGroupField = document.getElementById("packageGroup");
    const packageStatusField = document.getElementById("packageStatus");
    const packageCategoryField = document.getElementById("packageCategory");
    const packageGuestsField = document.getElementById("packageGuests");
    const packageRateField = document.getElementById("packageRate");
    const packageDescriptionField = document.getElementById("packageDescription");
    const packageTagsField = document.getElementById("packageTags");
    const packageInclusionsField = document.getElementById("packageInclusions");
    const packageFormHeading = document.getElementById("packageFormHeading");
    const packageSaveButton = document.getElementById("packageSaveButton");
    const packageFormClearButton = document.getElementById("packageFormClearButton");
    const packageResetDefaultsButton = document.getElementById("packageResetDefaultsButton");
    const packageFormFeedback = document.getElementById("packageFormFeedback");
    const packageSearchInput = document.getElementById("packageSearchInput");
    const packageTableBody = document.getElementById("packageTableBody");
    const packageEmptyState = document.getElementById("packageEmptyState");
    const packageTableWrap = packageTableBody?.closest(".dashboard-table-wrap");
    const packageSummaryTotal = document.getElementById("packageSummaryTotal");
    const packageSummaryActive = document.getElementById("packageSummaryActive");
    const packageSummaryReview = document.getElementById("packageSummaryReview");
    const packageSummaryInactive = document.getElementById("packageSummaryInactive");
    const filterButtons = Array.from(document.querySelectorAll("[data-package-filter]"));
    let activeFilter = "all";

    function escapeHtml(value) {
        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/\"/g, "&quot;")
            .replace(/'/g, "&#39;");
    }

    function formatTags(value) {
        return String(value || "")
            .split(",")
            .map((item) => item.trim())
            .filter(Boolean);
    }

    function formatInclusions(value) {
        return String(value || "")
            .split(/\r?\n/)
            .map((item) => item.trim())
            .filter(Boolean);
    }

    function buildUniqueId(name, existingId = "") {
        const currentCatalog = catalogApi.getCatalog();
        const baseId = catalogApi.slugify(name) || "package";
        let nextId = existingId || baseId;
        let suffix = 2;

        while (currentCatalog.some((packageItem) => packageItem.id === nextId && packageItem.id !== existingId)) {
            nextId = `${baseId}-${suffix}`;
            suffix += 1;
        }

        return nextId;
    }

    function setFeedback(message = "", state = "") {
        if (!packageFormFeedback) {
            return;
        }

        packageFormFeedback.textContent = message;
        packageFormFeedback.className = "package-form-feedback";

        if (state) {
            packageFormFeedback.classList.add(`is-${state}`);
        }
    }

    function resetForm(options = {}) {
        packageForm?.reset();
        packageIdField.value = "";
        packageGroupField.value = "per-head";
        packageStatusField.value = "active";
        packageFormHeading.textContent = "Add Package";
        packageSaveButton.textContent = "Save package";

        if (options.message) {
            setFeedback(options.message, options.state || "success");
        } else if (!options.preserveFeedback) {
            setFeedback("");
        }
    }

    function fillForm(packageItem) {
        packageIdField.value = packageItem.id;
        packageNameField.value = packageItem.name;
        packageGroupField.value = packageItem.group;
        packageStatusField.value = packageItem.status;
        packageCategoryField.value = packageItem.category;
        packageGuestsField.value = packageItem.guestLabel;
        packageRateField.value = packageItem.rateLabel;
        packageDescriptionField.value = packageItem.description;
        packageTagsField.value = packageItem.tags.join(", ");
        packageInclusionsField.value = packageItem.inclusions.join("\n");
        packageFormHeading.textContent = "Edit Package";
        packageSaveButton.textContent = "Update package";
        setFeedback("Editing selected package.", "success");
        packageNameField.focus();
    }

    function renderSummary() {
        const catalog = catalogApi.getCatalog();
        const activeCount = catalog.filter((packageItem) => packageItem.status === "active").length;
        const reviewCount = catalog.filter((packageItem) => packageItem.status === "review").length;
        const inactiveCount = catalog.filter((packageItem) => packageItem.status === "inactive").length;

        packageSummaryTotal.textContent = String(catalog.length);
        packageSummaryActive.textContent = String(activeCount);
        packageSummaryReview.textContent = String(reviewCount);
        packageSummaryInactive.textContent = String(inactiveCount);
    }

    function getFilteredCatalog() {
        const searchValue = packageSearchInput?.value.trim().toLowerCase() || "";

        return catalogApi.getCatalog().filter((packageItem) => {
            const matchesFilter = activeFilter === "all" || packageItem.status === activeFilter;
            const searchIndex = [
                packageItem.name,
                packageItem.group,
                packageItem.category,
                packageItem.guestLabel,
                packageItem.rateLabel,
                packageItem.description,
                packageItem.tags.join(" "),
                packageItem.inclusions.join(" ")
            ].join(" ").toLowerCase();
            const matchesSearch = !searchValue || searchIndex.includes(searchValue);

            return matchesFilter && matchesSearch;
        });
    }

    function getActionLabel(packageItem) {
        if (packageItem.status === "active") {
            return "Archive";
        }

        if (packageItem.status === "review") {
            return "Publish";
        }

        return "Activate";
    }

    function getNextStatus(packageItem) {
        if (packageItem.status === "active") {
            return "inactive";
        }

        return "active";
    }

    function renderTable() {
        const filteredCatalog = getFilteredCatalog();

        packageTableBody.innerHTML = filteredCatalog.map((packageItem) => {
            const statusMeta = catalogApi.STATUS_META[packageItem.status] || catalogApi.STATUS_META.review;
            const groupMeta = catalogApi.GROUPS[packageItem.group] || catalogApi.GROUPS["per-head"];
            const tagLine = packageItem.tags.slice(0, 2).join(" | ");

            return `
                <tr>
                    <td>
                        <span class="package-row__name">${escapeHtml(packageItem.name)}</span>
                        <span class="package-row__description">${escapeHtml(packageItem.description)}</span>
                    </td>
                    <td>
                        <span class="package-row__meta"><strong>${escapeHtml(groupMeta.shortLabel)}</strong></span>
                        <span class="package-row__description">${escapeHtml(packageItem.category)}${tagLine ? ` | ${escapeHtml(tagLine)}` : ""}</span>
                    </td>
                    <td>${escapeHtml(packageItem.guestLabel)}</td>
                    <td>${escapeHtml(packageItem.rateLabel)}</td>
                    <td><span class="status-pill status-pill--${escapeHtml(statusMeta.pillClass)}">${escapeHtml(statusMeta.label)}</span></td>
                    <td>
                        <div class="package-row__actions">
                            <button class="action-btn action-btn--ghost" type="button" data-package-action="edit" data-package-id="${escapeHtml(packageItem.id)}">Edit</button>
                            <button class="action-btn action-btn--soft" type="button" data-package-action="toggle" data-package-id="${escapeHtml(packageItem.id)}">${escapeHtml(getActionLabel(packageItem))}</button>
                            <button class="action-btn action-btn--ghost" type="button" data-package-action="delete" data-package-id="${escapeHtml(packageItem.id)}">Delete</button>
                        </div>
                    </td>
                </tr>
            `;
        }).join("");

        const hasRows = filteredCatalog.length > 0;
        packageEmptyState.hidden = hasRows;
        if (packageTableWrap) {
            packageTableWrap.hidden = !hasRows;
        }
    }

    function refreshView() {
        filterButtons.forEach((button) => {
            const isActive = button.dataset.packageFilter === activeFilter;
            button.classList.toggle("is-active", isActive);
            button.setAttribute("aria-pressed", String(isActive));
        });

        renderSummary();
        renderTable();
    }

    packageForm?.addEventListener("submit", (event) => {
        event.preventDefault();

        if (!packageNameField.value.trim() || !packageCategoryField.value.trim() || !packageGuestsField.value.trim() || !packageRateField.value.trim() || !packageDescriptionField.value.trim()) {
            setFeedback("Please complete the required package details before saving.", "error");
            return;
        }

        const inclusions = formatInclusions(packageInclusionsField.value);

        if (!inclusions.length) {
            setFeedback("Add at least one package inclusion before saving.", "error");
            packageInclusionsField.focus();
            return;
        }

        const nextId = buildUniqueId(packageNameField.value, packageIdField.value.trim());

        catalogApi.upsertPackage({
            id: nextId,
            group: packageGroupField.value,
            status: packageStatusField.value,
            name: packageNameField.value.trim(),
            category: packageCategoryField.value.trim(),
            guestLabel: packageGuestsField.value.trim(),
            rateLabel: packageRateField.value.trim(),
            description: packageDescriptionField.value.trim(),
            tags: formatTags(packageTagsField.value),
            inclusions
        });

        refreshView();
        resetForm({
            message: "Package saved successfully.",
            state: "success"
        });
    });

    packageFormClearButton?.addEventListener("click", () => {
        resetForm();
    });

    packageResetDefaultsButton?.addEventListener("click", () => {
        const confirmed = window.confirm("Restore the default package catalog? This removes your custom package edits.");

        if (!confirmed) {
            return;
        }

        catalogApi.resetCatalog();
        refreshView();
        resetForm({
            message: "Default package catalog restored.",
            state: "success"
        });
    });

    packageSearchInput?.addEventListener("input", () => {
        refreshView();
    });

    filterButtons.forEach((button) => {
        button.addEventListener("click", () => {
            activeFilter = button.dataset.packageFilter || "all";
            refreshView();
        });
    });

    packageTableBody?.addEventListener("click", (event) => {
        const actionButton = event.target.closest("[data-package-action]");

        if (!actionButton) {
            return;
        }

        const packageId = actionButton.dataset.packageId;
        const packageItem = catalogApi.getPackageById(packageId);

        if (!packageItem) {
            return;
        }

        if (actionButton.dataset.packageAction === "edit") {
            fillForm(packageItem);
            return;
        }

        if (actionButton.dataset.packageAction === "toggle") {
            const nextStatus = getNextStatus(packageItem);
            const statusLabel = catalogApi.STATUS_META[nextStatus]?.label || "Active";

            catalogApi.upsertPackage({
                ...packageItem,
                status: nextStatus
            });
            refreshView();
            setFeedback(`${packageItem.name} is now marked as ${statusLabel}.`, "success");
            return;
        }

        if (actionButton.dataset.packageAction === "delete") {
            const confirmed = window.confirm(`Delete "${packageItem.name}" from the package catalog?`);

            if (!confirmed) {
                return;
            }

            catalogApi.deletePackage(packageItem.id);

            if (packageIdField.value === packageItem.id) {
                resetForm({
                    message: "Deleted package removed from the form.",
                    state: "success"
                });
            } else {
                setFeedback(`${packageItem.name} deleted.`, "success");
            }

            refreshView();
        }
    });

    window.addEventListener(catalogApi.PACKAGE_CHANGE_EVENT, () => {
        refreshView();
    });

    refreshView();
    resetForm({
        preserveFeedback: true
    });
});
