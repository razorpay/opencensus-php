(function() {
  var base = Array.prototype.slice
    .call(document.querySelectorAll('script[src]'), -1)[0]
    .src.replace(/[^\/]+$/, '');

  var appendLink = function(src) {
    var link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = src;
    document.documentElement.appendChild(link);
  };

  // polyfills for ie 10/11
  document.write(
    '<script src="https://cdnjs.cloudflare.com/ajax/libs/babel-polyfill/6.26.0/polyfill.min.js"></script>'
  );

  document.write('<script src="' + base + 'vendor_m.js"></script>');
  document.write('<script src="' + base + 'merchantLA.js"></script>');
  appendLink(base + 'css/merchant-la.css');
  appendLink(
    'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css'
  );
})();
