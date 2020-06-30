export function loadColorJs(onLoadCB, onErrorCB) {
  let script = document.createElement('script');
  script.src = 'https://cdn.razorpay.com/static/assets/color.js';

  script.onload = onLoadCB;
  script.onerror = onErrorCB;

  document.head.appendChild(script);
}
