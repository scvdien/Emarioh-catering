(function (window, document) {
    const API_BASE = "api/auth/";
    const CLIENT_PORTAL_STORAGE_KEY = "emariohClientPortalState";
    let statusPromise = null;

    function buildError(message, payload) {
        const error = new Error(message || "Something went wrong.");
        error.payload = payload || null;
        return error;
    }

    async function requestJson(endpoint, options) {
        const requestOptions = Object.assign({
            credentials: "same-origin",
            headers: {
                Accept: "application/json"
            }
        }, options || {});

        const response = await fetch(API_BASE + endpoint, requestOptions);
        const rawText = await response.text();
        let payload = {};

        try {
            payload = rawText ? JSON.parse(rawText) : {};
        } catch (error) {
            payload = {};
        }

        if (!response.ok || payload.ok === false) {
            throw buildError(payload.message || "Request failed.", payload);
        }

        return payload;
    }

    function post(endpoint, data) {
        return requestJson(endpoint, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json"
            },
            body: JSON.stringify(data || {}),
            credentials: "same-origin"
        });
    }

    function clearStatusCache() {
        statusPromise = null;
    }

    function getStatus(forceRefresh) {
        if (!forceRefresh && statusPromise) {
            return statusPromise;
        }

        statusPromise = requestJson("status.php")
            .then(function (payload) {
                window.EmariohAuth.state = payload;
                applyUserContext(payload.user || null);
                return payload;
            })
            .catch(function (error) {
                clearStatusCache();
                throw error;
            });

        return statusPromise;
    }

    function syncClientPortalIdentity(user) {
        if (!user || user.role !== "client") {
            return;
        }

        try {
            const currentState = JSON.parse(window.localStorage.getItem(CLIENT_PORTAL_STORAGE_KEY) || "{}");
            const nextState = Object.assign({}, currentState, {
                clientName: user.full_name
            });

            if (nextState.bookingRequest && !String(nextState.bookingRequest.primaryContact || "").trim()) {
                nextState.bookingRequest = Object.assign({}, nextState.bookingRequest, {
                    primaryContact: user.full_name,
                    primaryMobile: nextState.bookingRequest.primaryMobile || user.mobile
                });
            }

            window.localStorage.setItem(CLIENT_PORTAL_STORAGE_KEY, JSON.stringify(nextState));
        } catch (error) {
            return;
        }
    }

    function applyUserContext(user) {
        if (!user) {
            return;
        }

        syncClientPortalIdentity(user);

        document.querySelectorAll("[data-auth-full-name]").forEach(function (element) {
            element.textContent = user.full_name;
        });

        document.querySelectorAll("[data-auth-first-name]").forEach(function (element) {
            element.textContent = user.first_name;
        });

        document.querySelectorAll("[data-auth-mobile]").forEach(function (element) {
            element.textContent = user.mobile;
        });

        document.querySelectorAll("[data-auth-input]").forEach(function (element) {
            var target = element.getAttribute("data-auth-input");

            if (target === "full_name") {
                element.value = user.full_name;
            }

            if (target === "mobile") {
                element.value = user.mobile;
            }
        });

        var primaryContactField = document.getElementById("primaryContact");
        var primaryMobileField = document.getElementById("primaryMobile");

        if (user.role === "client" && primaryContactField && !primaryContactField.value.trim()) {
            primaryContactField.value = user.full_name;
        }

        if (user.role === "client" && primaryMobileField && !primaryMobileField.value.trim()) {
            primaryMobileField.value = user.mobile;
        }

        const dashboardHeaderTitle = document.getElementById("dashboardHeaderTitle");

        if (dashboardHeaderTitle && user.role === "client") {
            dashboardHeaderTitle.textContent = "Welcome back, " + user.first_name;
        }
    }

    function resolveGuestRedirect(status) {
        return status.redirect_url || (status.user && status.user.role === "admin" ? "index.php" : "client-dashboard.php");
    }

    function resolveProtectedRedirect(status, requiredRole) {
        if (status.authenticated && status.user && status.user.role !== requiredRole) {
            return status.user.role === "admin" ? "index.php" : "client-dashboard.php";
        }

        const reason = requiredRole === "admin" ? "admin_only" : "client_only";
        return "login.php?reason=" + encodeURIComponent(reason);
    }

    async function handlePageGuard() {
        const body = document.body;

        if (!body) {
            return;
        }

        const guard = body.getAttribute("data-auth-guard");

        if (!guard) {
            return;
        }

        try {
            const status = await getStatus(false);

            if (guard === "guest" && status.authenticated) {
                window.location.replace(resolveGuestRedirect(status));
                return;
            }

            if ((guard === "admin" || guard === "client") && (!status.authenticated || !status.user || status.user.role !== guard)) {
                window.location.replace(resolveProtectedRedirect(status, guard));
                return;
            }

            body.setAttribute("data-auth-ready", "true");
        } catch (error) {
            if (guard === "admin" || guard === "client") {
                window.location.replace("login.php?reason=session");
                return;
            }

            body.setAttribute("data-auth-ready", "true");
        }
    }

    window.EmariohAuth = {
        state: null,
        post: post,
        getStatus: getStatus,
        clearStatusCache: clearStatusCache,
        syncClientPortalIdentity: syncClientPortalIdentity,
        applyUserContext: applyUserContext,
        logout: function () {
            clearStatusCache();
            return post("logout.php", {});
        }
    };

    document.addEventListener("DOMContentLoaded", function () {
        handlePageGuard();
    });
}(window, document));
