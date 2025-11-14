// InterHop — raw shim global pour EasyAppointments
(function () {
    try {
        if (!('raw' in window)) {
            Object.defineProperty(window, 'raw', { configurable: true, writable: true, value: {} });
        } else if (window.raw == null || typeof window.raw !== 'object') {
            window.raw = {};
        }
    } catch (_) {
        window.raw = {};
    }
})();
