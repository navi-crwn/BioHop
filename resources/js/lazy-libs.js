// Big libraries load only where they are used, from our own files (no third-party CDN, no blocking script in <head>).
let chart, qr;
window.loadChart = () => (chart ??= import('chart.js/auto').then((m) => (window.Chart = m.Chart ?? m.default)));
window.loadQr = () => (qr ??= import('qr-code-styling').then((m) => (window.QRCodeStyling = m.default)));
