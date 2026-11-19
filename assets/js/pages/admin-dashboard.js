(function (window, document) {
    "use strict";

    const SVG_NS = "http://www.w3.org/2000/svg";

    function escapeHtml(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");
    }

    function formatMoney(amount) {
        const safe = Math.max(0, Number(amount) || 0);
        return "PHP " + safe.toLocaleString("en-PH", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    function formatAxisMoney(amount) {
        const value = Math.max(0, Number(amount) || 0);

        if (value === 0) {
            return "₱0";
        }

        if (value >= 1000000) {
            const millions = value / 1000000;
            return "₱" + (Number.isInteger(millions) ? millions : millions.toFixed(1)) + "M";
        }

        if (value >= 1000) {
            const thousands = value / 1000;
            return "₱" + (thousands >= 100 || Number.isInteger(thousands) ? Math.round(thousands) : thousands.toFixed(1)) + "k";
        }

        return "₱" + Math.round(value).toLocaleString("en-PH");
    }

    function niceScaleMax(maxValue) {
        if (!Number.isFinite(maxValue) || maxValue <= 0) {
            return 10000;
        }

        const rawStep = maxValue / 4;
        const magnitude = Math.pow(10, Math.floor(Math.log10(rawStep)));
        const residual = rawStep / magnitude;
        let niceStep;

        if (residual <= 1) {
            niceStep = 1 * magnitude;
        } else if (residual <= 2) {
            niceStep = 2 * magnitude;
        } else if (residual <= 2.5) {
            niceStep = 2.5 * magnitude;
        } else if (residual <= 5) {
            niceStep = 5 * magnitude;
        } else {
            niceStep = 10 * magnitude;
        }

        return Math.ceil(maxValue / niceStep) * niceStep;
    }

    function buildMonotonePath(points) {
        const n = points.length;

        if (n === 0) {
            return "";
        }

        if (n === 1) {
            return `M ${points[0].x.toFixed(2)} ${points[0].y.toFixed(2)}`;
        }

        if (n === 2) {
            return `M ${points[0].x.toFixed(2)} ${points[0].y.toFixed(2)} L ${points[1].x.toFixed(2)} ${points[1].y.toFixed(2)}`;
        }

        const dx = [];
        const dy = [];
        const slopes = [];

        for (let i = 0; i < n - 1; i += 1) {
            dx[i] = points[i + 1].x - points[i].x;
            dy[i] = points[i + 1].y - points[i].y;
            slopes[i] = dx[i] !== 0 ? dy[i] / dx[i] : 0;
        }

        const tangents = new Array(n).fill(0);
        tangents[0] = slopes[0];
        tangents[n - 1] = slopes[n - 2];

        for (let i = 1; i < n - 1; i += 1) {
            if (slopes[i - 1] * slopes[i] <= 0) {
                tangents[i] = 0;
            } else {
                const w1 = dx[i - 1] + 2 * dx[i];
                const w2 = 2 * dx[i - 1] + dx[i];
                tangents[i] = (w1 + w2) / (w1 / slopes[i - 1] + w2 / slopes[i]);
            }
        }

        let path = `M ${points[0].x.toFixed(2)} ${points[0].y.toFixed(2)}`;

        for (let i = 0; i < n - 1; i += 1) {
            const step = dx[i] / 3;
            const c1x = points[i].x + step;
            const c1y = points[i].y + tangents[i] * step;
            const c2x = points[i + 1].x - step;
            const c2y = points[i + 1].y - tangents[i + 1] * step;

            path += ` C ${c1x.toFixed(2)} ${c1y.toFixed(2)}, ${c2x.toFixed(2)} ${c2y.toFixed(2)}, ${points[i + 1].x.toFixed(2)} ${points[i + 1].y.toFixed(2)}`;
        }

        return path;
    }

    function createSvgEl(tag, attrs = {}) {
        const el = document.createElementNS(SVG_NS, tag);

        Object.entries(attrs).forEach(([key, value]) => {
            if (value !== undefined && value !== null) {
                el.setAttribute(key, String(value));
            }
        });

        return el;
    }

    function applyTrendBadge(element, trend, includeDetail) {
        if (!element || !trend) {
            return;
        }

        const direction = trend.direction === "up" || trend.direction === "down" ? trend.direction : "flat";
        const iconClass = direction === "up"
            ? "bi-arrow-up-right"
            : (direction === "down" ? "bi-arrow-down-right" : "bi-dash");

        element.className = `trend-badge trend-badge--${direction}`;
        const label = includeDetail && trend.detailText
            ? `${trend.badgeText} ${trend.detailText}`
            : trend.badgeText;

        element.innerHTML = `<i class="bi ${iconClass}" aria-hidden="true"></i><span>${escapeHtml(label)}</span>`;
    }

    document.addEventListener("DOMContentLoaded", function () {
        const dataElement = document.getElementById("adminDashboardAnalyticsData");

        if (!dataElement) {
            return;
        }

        let analytics;
        try {
            analytics = JSON.parse(dataElement.textContent || "{}");
        } catch (error) {
            return;
        }

        if (!analytics || !analytics.periods) {
            return;
        }

        const state = {
            period: analytics.defaultPeriod && analytics.periods[analytics.defaultPeriod]
                ? analytics.defaultPeriod
                : "six_months",
            seriesMode: "both",
            chartType: "line",
            activeIndex: null,
        };

        const els = {
            headerTitle: document.getElementById("salesDashboardTitle"),
            headerSubtitle: document.getElementById("salesDashboardSubtitle"),
            kpiBookedValue: document.getElementById("kpiBookedSalesValue"),
            kpiBookedTrend: document.getElementById("kpiBookedSalesTrend"),
            kpiBookedNote: document.getElementById("kpiBookedSalesNote"),
            kpiCollectedValue: document.getElementById("kpiCollectedSalesValue"),
            kpiCollectionRate: document.getElementById("kpiCollectionRateLabel"),
            kpiCollectionBar: document.getElementById("kpiCollectionRateBar"),
            kpiRemainingValue: document.getElementById("kpiRemainingBalanceValue"),
            kpiRemainingNote: document.getElementById("kpiRemainingBalanceNote"),
            kpiBookingsValue: document.getElementById("kpiConfirmedBookingsValue"),
            kpiBookingsTrend: document.getElementById("kpiConfirmedBookingsTrend"),
            kpiBookingsNote: document.getElementById("kpiConfirmedBookingsNote"),
            chartTitle: document.getElementById("salesChartTitle"),
            chartSubtitle: document.getElementById("salesChartSubtitle"),
            chartStage: document.getElementById("salesChartStage"),
            chartSvg: document.getElementById("salesChartSvg"),
            chartEmpty: document.getElementById("salesChartEmpty"),
            chartTooltip: document.getElementById("salesChartTooltip"),
            chartStatusBadge: document.getElementById("salesChartLatestTrendBadge"),
            chartStatusText: document.getElementById("salesChartLatestTrendText"),
            statPeakValue: document.getElementById("salesStatPeakValue"),
            statPeakSub: document.getElementById("salesStatPeakSub"),
            statAvgValue: document.getElementById("salesStatAvgValue"),
            statAvgSub: document.getElementById("salesStatAvgSub"),
            statYtdValue: document.getElementById("salesStatYtdValue"),
            statYtdSub: document.getElementById("salesStatYtdSub"),
            celebrationsList: document.getElementById("salesCelebrationsList"),
            celebrationsSubtitle: document.getElementById("salesCelebrationsSubtitle"),
            periodTabs: document.querySelectorAll("[data-sales-period]"),
            seriesButtons: document.querySelectorAll("[data-chart-series]"),
            modeButtons: document.querySelectorAll("[data-chart-mode]"),
        };

        function getCurrentPeriodData() {
            return analytics.periods[state.period] || analytics.periods.six_months;
        }

        function hideTooltip() {
            state.activeIndex = null;
            if (els.chartTooltip) {
                els.chartTooltip.classList.remove("is-visible");
                els.chartTooltip.setAttribute("aria-hidden", "true");
            }
            updateChartHighlights(null);
        }

        function updateChartHighlights(index) {
            if (!els.chartSvg) {
                return;
            }

            const guide = els.chartSvg.querySelector("[data-chart-guide]");
            const xLabels = els.chartSvg.querySelectorAll("[data-chart-xlabel]");
            const points = els.chartSvg.querySelectorAll("[data-chart-point]");
            const bars = els.chartSvg.querySelectorAll("[data-chart-bar]");

            if (guide) {
                if (index === null) {
                    guide.setAttribute("opacity", "0");
                } else {
                    const targetX = els.chartSvg.querySelector(`[data-chart-col="${index}"]`)?.getAttribute("data-center-x");
                    if (targetX) {
                        guide.setAttribute("x1", targetX);
                        guide.setAttribute("x2", targetX);
                        guide.setAttribute("opacity", "1");
                    }
                }
            }

            xLabels.forEach((label) => {
                const isMatch = index !== null && Number(label.getAttribute("data-chart-xlabel")) === index;
                label.classList.toggle("sales-chart-axis-text--active", isMatch);
            });

            points.forEach((pt) => {
                const ptIndex = Number(pt.getAttribute("data-chart-point"));
                const baseR = Number(pt.getAttribute("data-base-r") || 4.2);
                pt.setAttribute("r", index === ptIndex ? String(baseR + 2.3) : String(baseR));
            });

            bars.forEach((bar) => {
                const barIndex = Number(bar.getAttribute("data-chart-bar"));
                bar.classList.toggle("is-dimmed", index !== null && barIndex !== index);
            });
        }

        function showTooltip(index, anchorX, anchorY, svgWidth) {
            const periodData = getCurrentPeriodData();
            const item = periodData.series[index];

            if (!item || !els.chartTooltip || !els.chartStage) {
                return;
            }

            state.activeIndex = index;
            updateChartHighlights(index);

            const deltaDir = item.deltaDirection === "up" || item.deltaDirection === "down"
                ? item.deltaDirection
                : "flat";
            const deltaArrow = deltaDir === "up" ? "▲ " : (deltaDir === "down" ? "▼ " : "");
            const deltaBadgeLabel = `${deltaArrow}${item.deltaText}`;
            const collectionPct = item.booked > 0
                ? Math.min(100, Math.round((item.collected / item.booked) * 100))
                : 0;

            els.chartTooltip.innerHTML = `
                <div class="sales-chart-tooltip__head">
                    <span class="sales-chart-tooltip__title">${escapeHtml(item.fullLabel)}</span>
                    <span class="sales-chart-tooltip__delta sales-chart-tooltip__delta--${deltaDir}">${escapeHtml(deltaBadgeLabel)}</span>
                </div>
                <div class="sales-chart-tooltip__rows">
                    <div class="sales-chart-tooltip__row">
                        <span><i class="chart-dot chart-dot--booked"></i> Booked Sales</span>
                        <strong>${escapeHtml(formatMoney(item.booked))}</strong>
                    </div>
                    <div class="sales-chart-tooltip__row">
                        <span><i class="chart-dot chart-dot--collected"></i> Collected</span>
                        <strong>${escapeHtml(formatMoney(item.collected))}</strong>
                    </div>
                    <div class="sales-chart-tooltip__row">
                        <span>Remaining Balance</span>
                        <strong>${escapeHtml(formatMoney(item.remaining))}</strong>
                    </div>
                </div>
                <div class="sales-chart-tooltip__foot">
                    <span>${escapeHtml(item.bookings)} confirmed booking${item.bookings === 1 ? "" : "s"}</span>
                    <span>${escapeHtml(item.deltaRefLabel || `${collectionPct}% collected`)}</span>
                </div>
            `;

            els.chartTooltip.classList.add("is-visible");
            els.chartTooltip.setAttribute("aria-hidden", "false");

            const stageRect = els.chartStage.getBoundingClientRect();
            const tipWidth = els.chartTooltip.offsetWidth || 220;
            const tipHeight = els.chartTooltip.offsetHeight || 130;
            const scaleX = svgWidth > 0 ? stageRect.width / svgWidth : 1;
            const pixelX = anchorX * scaleX;
            const pad = 10;

            let left = pixelX + 14;
            if (left + tipWidth > stageRect.width - pad) {
                left = pixelX - tipWidth - 14;
            }
            left = Math.max(pad, Math.min(left, stageRect.width - tipWidth - pad));

            let top = anchorY - tipHeight / 2;
            top = Math.max(6, Math.min(top, stageRect.height - tipHeight - 10));

            els.chartTooltip.style.left = `${left.toFixed(1)}px`;
            els.chartTooltip.style.top = `${top.toFixed(1)}px`;
        }

        function renderKpis(periodData) {
            const kpis = periodData.kpis;

            if (els.headerTitle) {
                els.headerTitle.textContent = periodData.title;
            }
            if (els.headerSubtitle) {
                els.headerSubtitle.textContent = periodData.subtitle;
            }

            if (els.kpiBookedValue) {
                els.kpiBookedValue.textContent = formatMoney(kpis.bookedSales);
            }
            if (els.kpiBookedTrend) {
                applyTrendBadge(els.kpiBookedTrend, kpis.salesTrend, true);
            }
            if (els.kpiBookedNote) {
                els.kpiBookedNote.textContent = kpis.bookedNote;
            }

            if (els.kpiCollectedValue) {
                els.kpiCollectedValue.textContent = formatMoney(kpis.collectedSales);
            }
            if (els.kpiCollectionRate) {
                els.kpiCollectionRate.textContent = `${Math.round(kpis.collectionRate)}% collected`;
            }
            if (els.kpiCollectionBar) {
                els.kpiCollectionBar.style.width = `${Math.min(100, Math.max(0, kpis.collectionRate)).toFixed(1)}%`;
            }

            if (els.kpiRemainingValue) {
                els.kpiRemainingValue.textContent = formatMoney(kpis.remainingBalance);
            }
            if (els.kpiRemainingNote) {
                els.kpiRemainingNote.textContent = kpis.remainingBalance > 0
                    ? "Unpaid balance from confirmed events"
                    : "All confirmed events are fully settled";
            }

            if (els.kpiBookingsValue) {
                els.kpiBookingsValue.textContent = String(kpis.confirmedBookings);
            }
            if (els.kpiBookingsTrend) {
                applyTrendBadge(els.kpiBookingsTrend, kpis.bookingsTrend, true);
            }
            if (els.kpiBookingsNote) {
                els.kpiBookingsNote.textContent = kpis.confirmedBookings === 1
                    ? "Approved or completed event"
                    : "Approved or completed events";
            }

            if (els.chartTitle) {
                els.chartTitle.textContent = periodData.chartTitle;
            }
            if (els.chartSubtitle) {
                els.chartSubtitle.textContent = periodData.chartSubtitle;
            }

            if (els.chartStatusBadge && analytics.latestMonthTrend) {
                applyTrendBadge(els.chartStatusBadge, analytics.latestMonthTrend, false);
            }
            if (els.chartStatusText && analytics.latestMonthTrend) {
                const lt = analytics.latestMonthTrend;
                els.chartStatusText.innerHTML = `Latest month: <strong>${escapeHtml(lt.currentLabel)}</strong> (${escapeHtml(formatMoney(lt.currentSales))}) vs ${escapeHtml(lt.previousLabel)}`;
            }

            if (els.statPeakValue) {
                els.statPeakValue.textContent = periodData.summary.peakValue > 0
                    ? formatMoney(periodData.summary.peakValue)
                    : "No sales yet";
            }
            if (els.statPeakSub) {
                els.statPeakSub.textContent = periodData.summary.peakValue > 0
                    ? periodData.summary.peakLabel
                    : "Waiting for bookings";
            }
            if (els.statAvgValue) {
                els.statAvgValue.textContent = formatMoney(periodData.summary.avgValue);
            }
            if (els.statAvgSub) {
                els.statAvgSub.textContent = periodData.summary.avgLabel;
            }
            if (els.statYtdValue && analytics.ytdSummary) {
                els.statYtdValue.textContent = formatMoney(analytics.ytdSummary.bookedSales);
            }
            if (els.statYtdSub && analytics.ytdSummary) {
                els.statYtdSub.textContent = `${analytics.ytdSummary.yearLabel} · ${analytics.ytdSummary.confirmedBookings} confirmed booking${analytics.ytdSummary.confirmedBookings === 1 ? "" : "s"}`;
            }
        }

        function renderCelebrations(periodData) {
            if (!els.celebrationsList) {
                return;
            }

            if (els.celebrationsSubtitle) {
                els.celebrationsSubtitle.textContent = periodData.celebrationsSubtitle;
            }

            const items = periodData.celebrations || [];

            if (items.length === 0) {
                els.celebrationsList.innerHTML = `
                    <div class="celebrations-empty">
                        <i class="bi bi-stars" aria-hidden="true"></i>
                        <p>No confirmed celebration bookings recorded for this period yet.</p>
                    </div>
                `;
                return;
            }

            const maxCount = Math.max(1, ...items.map((item) => Number(item.bookingCount) || 0));

            els.celebrationsList.innerHTML = items.map((item, idx) => {
                const count = Number(item.bookingCount) || 0;
                const sales = Number(item.bookedSales) || 0;
                const width = Math.min(100, Math.max(10, (count / maxCount) * 100));
                const topClass = idx === 0 ? " celebration-row--top" : "";

                return `
                    <div class="celebration-row${topClass}">
                        <span class="celebration-row__rank">${idx + 1}</span>
                        <div class="celebration-row__body">
                            <div class="celebration-row__head">
                                <span class="celebration-row__name" title="${escapeHtml(item.eventType)}">${escapeHtml(item.eventType)}</span>
                                <span class="celebration-row__count">${count} booking${count === 1 ? "" : "s"}</span>
                            </div>
                            <div class="celebration-row__bar" aria-hidden="true">
                                <span style="width: ${width.toFixed(1)}%;"></span>
                            </div>
                            <span class="celebration-row__sales">${escapeHtml(formatMoney(sales))} total sales</span>
                        </div>
                    </div>
                `;
            }).join("");
        }

        function renderChart(periodData) {
            const svg = els.chartSvg;
            const stage = els.chartStage;

            if (!svg || !stage) {
                return;
            }

            hideTooltip();

            const series = periodData.series || [];
            const stageWidth = Math.max(320, Math.round(stage.clientWidth || 680));
            const height = stageWidth < 520 ? 225 : 250;
            const pad = {
                top: 20,
                right: 18,
                bottom: 34,
                left: stageWidth < 520 ? 48 : 58,
            };
            const plotW = Math.max(120, stageWidth - pad.left - pad.right);
            const plotH = Math.max(100, height - pad.top - pad.bottom);
            const baselineY = pad.top + plotH;

            svg.setAttribute("viewBox", `0 0 ${stageWidth} ${height}`);
            svg.innerHTML = "";

            const showBooked = state.seriesMode === "both" || state.seriesMode === "booked";
            const showCollected = state.seriesMode === "both" || state.seriesMode === "collected";

            let rawMax = 0;
            let peakIndex = -1;
            let peakVal = 0;

            series.forEach((item, idx) => {
                if (showBooked) {
                    rawMax = Math.max(rawMax, item.booked);
                }
                if (showCollected) {
                    rawMax = Math.max(rawMax, item.collected);
                }
                const compareVal = showBooked ? item.booked : item.collected;
                if (compareVal > peakVal) {
                    peakVal = compareVal;
                    peakIndex = idx;
                }
            });

            if (els.chartEmpty) {
                els.chartEmpty.hidden = rawMax > 0;
            }

            const yMax = niceScaleMax(rawMax);
            const valueToY = (value) => baselineY - (Math.max(0, value) / yMax) * plotH;

            // Gradients
            const defs = createSvgEl("defs");

            const bookedGrad = createSvgEl("linearGradient", {
                id: "emariohBookedFill",
                x1: "0",
                y1: "0",
                x2: "0",
                y2: "1",
            });
            bookedGrad.appendChild(createSvgEl("stop", { offset: "0%", "stop-color": "#1F3B2F", "stop-opacity": "0.22" }));
            bookedGrad.appendChild(createSvgEl("stop", { offset: "100%", "stop-color": "#1F3B2F", "stop-opacity": "0.01" }));
            defs.appendChild(bookedGrad);

            const collectedGrad = createSvgEl("linearGradient", {
                id: "emariohCollectedFill",
                x1: "0",
                y1: "0",
                x2: "0",
                y2: "1",
            });
            collectedGrad.appendChild(createSvgEl("stop", { offset: "0%", "stop-color": "#C9A24A", "stop-opacity": "0.26" }));
            collectedGrad.appendChild(createSvgEl("stop", { offset: "100%", "stop-color": "#C9A24A", "stop-opacity": "0.02" }));
            defs.appendChild(collectedGrad);

            svg.appendChild(defs);

            // Gridlines & Y-axis ticks
            const ticks = 4;
            for (let i = 0; i <= ticks; i += 1) {
                const tickVal = (yMax / ticks) * i;
                const y = baselineY - (plotH / ticks) * i;

                svg.appendChild(createSvgEl("line", {
                    x1: pad.left,
                    x2: pad.left + plotW,
                    y1: y.toFixed(2),
                    y2: y.toFixed(2),
                    class: i === 0 ? "sales-chart-baseline" : "sales-chart-gridline",
                }));

                const yText = createSvgEl("text", {
                    x: pad.left - 10,
                    y: (y + 3.5).toFixed(2),
                    "text-anchor": "end",
                    class: "sales-chart-axis-text",
                });
                yText.textContent = formatAxisMoney(tickVal);
                svg.appendChild(yText);
            }

            if (series.length === 0) {
                return;
            }

            const count = series.length;
            const slotW = plotW / count;
            const getX = (idx) => {
                if (state.chartType === "bar") {
                    return pad.left + slotW * idx + slotW / 2;
                }
                if (count === 1) {
                    return pad.left + plotW / 2;
                }
                const inset = Math.min(28, slotW * 0.35);
                const span = Math.max(10, plotW - inset * 2);
                return pad.left + inset + (span / (count - 1)) * idx;
            };

            // Vertical hover guide line
            svg.appendChild(createSvgEl("line", {
                x1: pad.left,
                x2: pad.left,
                y1: pad.top,
                y2: baselineY,
                opacity: 0,
                class: "sales-chart-guide",
                "data-chart-guide": "true",
            }));

            if (state.chartType === "line") {
                const bookedPts = series.map((item, idx) => ({
                    x: getX(idx),
                    y: valueToY(item.booked),
                }));
                const collectedPts = series.map((item, idx) => ({
                    x: getX(idx),
                    y: valueToY(item.collected),
                }));

                if (showBooked && rawMax > 0) {
                    const bookedLineD = buildMonotonePath(bookedPts);
                    if (bookedPts.length > 1) {
                        const first = bookedPts[0];
                        const last = bookedPts[bookedPts.length - 1];
                        const areaD = `${bookedLineD} L ${last.x.toFixed(2)} ${baselineY.toFixed(2)} L ${first.x.toFixed(2)} ${baselineY.toFixed(2)} Z`;
                        svg.appendChild(createSvgEl("path", {
                            d: areaD,
                            fill: "url(#emariohBookedFill)",
                        }));
                    }
                    svg.appendChild(createSvgEl("path", {
                        d: bookedLineD,
                        class: "sales-chart-line sales-chart-line--booked",
                    }));
                }

                if (showCollected && rawMax > 0) {
                    const collectedLineD = buildMonotonePath(collectedPts);
                    if (!showBooked && collectedPts.length > 1) {
                        const first = collectedPts[0];
                        const last = collectedPts[collectedPts.length - 1];
                        const areaD = `${collectedLineD} L ${last.x.toFixed(2)} ${baselineY.toFixed(2)} L ${first.x.toFixed(2)} ${baselineY.toFixed(2)} Z`;
                        svg.appendChild(createSvgEl("path", {
                            d: areaD,
                            fill: "url(#emariohCollectedFill)",
                        }));
                    }
                    svg.appendChild(createSvgEl("path", {
                        d: collectedLineD,
                        class: "sales-chart-line sales-chart-line--collected",
                        "stroke-dasharray": showBooked ? "6 4" : null,
                    }));
                }

                // Points
                series.forEach((item, idx) => {
                    if (showCollected) {
                        const pt = collectedPts[idx];
                        const baseR = idx === peakIndex && !showBooked ? 5.2 : 3.8;
                        svg.appendChild(createSvgEl("circle", {
                            cx: pt.x.toFixed(2),
                            cy: pt.y.toFixed(2),
                            r: baseR,
                            "data-base-r": baseR,
                            "data-chart-point": idx,
                            class: `sales-chart-point sales-chart-point--collected${idx === peakIndex && !showBooked ? " sales-chart-point--peak" : ""}`,
                        }));
                    }

                    if (showBooked) {
                        const pt = bookedPts[idx];
                        const baseR = idx === peakIndex && rawMax > 0 ? 5.4 : 4.2;
                        svg.appendChild(createSvgEl("circle", {
                            cx: pt.x.toFixed(2),
                            cy: pt.y.toFixed(2),
                            r: baseR,
                            "data-base-r": baseR,
                            "data-chart-point": idx,
                            class: `sales-chart-point sales-chart-point--booked${idx === peakIndex && rawMax > 0 ? " sales-chart-point--peak" : ""}`,
                        }));
                    }
                });
            } else {
                // Grouped Bar Chart
                const visibleSeriesCount = (showBooked ? 1 : 0) + (showCollected ? 1 : 0);
                const maxGroupW = Math.min(46, slotW * 0.68);
                const barGap = visibleSeriesCount === 2 ? 4 : 0;
                const barW = Math.max(8, (maxGroupW - barGap) / Math.max(1, visibleSeriesCount));

                series.forEach((item, idx) => {
                    const centerX = getX(idx);
                    const totalGroupW = barW * visibleSeriesCount + barGap;
                    let cursorX = centerX - totalGroupW / 2;

                    if (showBooked) {
                        const y = valueToY(item.booked);
                        const h = Math.max(item.booked > 0 ? 3 : 0, baselineY - y);
                        svg.appendChild(createSvgEl("rect", {
                            x: cursorX.toFixed(2),
                            y: (baselineY - h).toFixed(2),
                            width: barW.toFixed(2),
                            height: h.toFixed(2),
                            rx: Math.min(5, barW / 2).toFixed(2),
                            class: "sales-chart-bar sales-chart-bar--booked",
                            "data-chart-bar": idx,
                        }));
                        cursorX += barW + barGap;
                    }

                    if (showCollected) {
                        const y = valueToY(item.collected);
                        const h = Math.max(item.collected > 0 ? 3 : 0, baselineY - y);
                        svg.appendChild(createSvgEl("rect", {
                            x: cursorX.toFixed(2),
                            y: (baselineY - h).toFixed(2),
                            width: barW.toFixed(2),
                            height: h.toFixed(2),
                            rx: Math.min(5, barW / 2).toFixed(2),
                            class: "sales-chart-bar sales-chart-bar--collected",
                            "data-chart-bar": idx,
                        }));
                    }
                });
            }

            // X-axis labels & interactive hitboxes
            const skipEveryOther = count > 8 && stageWidth < 460;

            series.forEach((item, idx) => {
                const centerX = getX(idx);

                if (!skipEveryOther || idx % 2 === 0 || idx === count - 1) {
                    const xLabel = createSvgEl("text", {
                        x: centerX.toFixed(2),
                        y: (baselineY + 21).toFixed(2),
                        "text-anchor": "middle",
                        class: `sales-chart-axis-text${item.isCurrent ? " sales-chart-axis-text--active" : ""}`,
                        "data-chart-xlabel": idx,
                    });
                    xLabel.textContent = item.shortLabel;
                    svg.appendChild(xLabel);
                }

                const hitW = count === 1 ? plotW : Math.max(22, plotW / count);
                const hitX = Math.max(pad.left, Math.min(centerX - hitW / 2, pad.left + plotW - hitW));
                const anchorY = valueToY(showBooked ? item.booked : item.collected);

                const hitbox = createSvgEl("rect", {
                    x: hitX.toFixed(2),
                    y: pad.top,
                    width: hitW.toFixed(2),
                    height: plotH + 26,
                    class: "sales-chart-hitbox",
                    tabindex: "0",
                    role: "button",
                    "aria-label": `${item.fullLabel}: Booked ${formatMoney(item.booked)}, Collected ${formatMoney(item.collected)}`,
                    "data-chart-col": idx,
                    "data-center-x": centerX.toFixed(2),
                });

                hitbox.addEventListener("mouseenter", () => {
                    showTooltip(idx, centerX, anchorY, stageWidth);
                });

                hitbox.addEventListener("focus", () => {
                    showTooltip(idx, centerX, anchorY, stageWidth);
                });

                hitbox.addEventListener("click", (event) => {
                    event.stopPropagation();
                    if (state.activeIndex === idx) {
                        hideTooltip();
                    } else {
                        showTooltip(idx, centerX, anchorY, stageWidth);
                    }
                });

                hitbox.addEventListener("keydown", (event) => {
                    if (event.key === "ArrowRight" && idx + 1 < count) {
                        event.preventDefault();
                        svg.querySelector(`[data-chart-col="${idx + 1}"]`)?.focus();
                    } else if (event.key === "ArrowLeft" && idx > 0) {
                        event.preventDefault();
                        svg.querySelector(`[data-chart-col="${idx - 1}"]`)?.focus();
                    } else if (event.key === "Escape") {
                        hideTooltip();
                    }
                });

                svg.appendChild(hitbox);
            });
        }

        function syncControlStates() {
            els.periodTabs.forEach((btn) => {
                const isActive = btn.getAttribute("data-sales-period") === state.period;
                btn.classList.toggle("is-active", isActive);
                btn.setAttribute("aria-pressed", isActive ? "true" : "false");
            });

            els.seriesButtons.forEach((btn) => {
                const isActive = btn.getAttribute("data-chart-series") === state.seriesMode;
                btn.classList.toggle("is-active", isActive);
                btn.setAttribute("aria-pressed", isActive ? "true" : "false");
            });

            els.modeButtons.forEach((btn) => {
                const isActive = btn.getAttribute("data-chart-mode") === state.chartType;
                btn.classList.toggle("is-active", isActive);
                btn.setAttribute("aria-pressed", isActive ? "true" : "false");
            });
        }

        function renderAll() {
            const periodData = getCurrentPeriodData();
            syncControlStates();
            renderKpis(periodData);
            renderCelebrations(periodData);
            renderChart(periodData);
        }

        els.periodTabs.forEach((btn) => {
            btn.addEventListener("click", () => {
                const nextPeriod = btn.getAttribute("data-sales-period");
                if (nextPeriod && analytics.periods[nextPeriod] && nextPeriod !== state.period) {
                    state.period = nextPeriod;
                    renderAll();
                }
            });
        });

        els.seriesButtons.forEach((btn) => {
            btn.addEventListener("click", () => {
                const nextSeries = btn.getAttribute("data-chart-series");
                if (nextSeries && nextSeries !== state.seriesMode) {
                    state.seriesMode = nextSeries;
                    syncControlStates();
                    renderChart(getCurrentPeriodData());
                }
            });
        });

        els.modeButtons.forEach((btn) => {
            btn.addEventListener("click", () => {
                const nextMode = btn.getAttribute("data-chart-mode");
                if (nextMode && nextMode !== state.chartType) {
                    state.chartType = nextMode;
                    syncControlStates();
                    renderChart(getCurrentPeriodData());
                }
            });
        });

        if (els.chartStage) {
            els.chartStage.addEventListener("mouseleave", hideTooltip);
        }

        document.addEventListener("click", (event) => {
            if (els.chartStage && !els.chartStage.contains(event.target)) {
                hideTooltip();
            }
        });

        let resizeTimer = null;
        const scheduleRedraw = () => {
            window.clearTimeout(resizeTimer);
            resizeTimer = window.setTimeout(() => {
                renderChart(getCurrentPeriodData());
            }, 90);
        };

        if (typeof window.ResizeObserver === "function" && els.chartStage) {
            const observer = new window.ResizeObserver(scheduleRedraw);
            observer.observe(els.chartStage);
        } else {
            window.addEventListener("resize", scheduleRedraw);
        }

        renderAll();
    });
})(window, document);
