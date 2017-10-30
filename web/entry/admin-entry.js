function prefixCdn(url) {
  prefix = '';
  var match = location.hostname.match(/(.*)dashboard\.razorpay\.com$/);
  if (location.protocol === 'https' && match) {
    prefix =
      'https://' + (match[1] ? 'beta' : '') + 'cdn.razorpay.com/dashboard';
  }
  return prefix + url;
}

document.write('<script src="' + prefixCdn('vendor.js') + '"></script>');
document.write('<script src="' + prefixCdn('admin/admin.js') + '"></script>');
document.write(
  '<link href="' + prefixCdn('css/admin.css') + '" rel="stylesheet"></link>'
);
