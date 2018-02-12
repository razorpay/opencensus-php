function prefixCdn(url) {
  prefix = '';
  var match = location.hostname.match(/(.+dashboard)?\.razorpay\.com$/);
  if (location.protocol === 'https:' && match) {
    prefix =
      'https://' + (match[1] ? 'beta' : '') + 'cdn.razorpay.com/dashboard';
  }
  return prefix + '/dist/' + url;
}

// TODO: Make common utilty folder for admin and merchant(refer same fn. in admin-entry.js)
function appendLink(src) {
  var link = document.createElement('link');
  link.rel = 'stylesheet';
  link.href = src;
  document.documentElement.appendChild(link);
}

document.write('<script src="' + prefixCdn('vendor_m.js') + '"></script>');
document.write('<script src="' + prefixCdn('pokedex.js') + '"></script>');

appendLink(prefixCdn('css/merchant.css'));
appendLink(
  'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css'
);
