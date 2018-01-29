function prefixCdn(url) {
  prefix = '';
  var match = location.hostname.match(/(.+dashboard)?\.razorpay\.com$/);
  if (location.protocol === 'https:' && match) {
    prefix =
      'https://' + (match[1] ? 'beta' : '') + 'cdn.razorpay.com/dashboard';
  }
  return prefix + '/dist/' + url;
}

function appendLink(src) {
  var link = document.createElement('link');
  link.rel = 'stylesheet';
  link.href = src;
  document.documentElement.appendChild(link);
}

document.write('<script src="' + prefixCdn('vendor_a.js') + '"></script>');
document.write('<script src="' + prefixCdn('admin.js') + '"></script>');

appendLink(prefixCdn('css/admin.css'));
