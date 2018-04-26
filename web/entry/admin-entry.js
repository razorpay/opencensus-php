(function() {
  var base = Array.prototype.slice.call(document.querySelectorAll('script[src]'), -1)[0]
    .src.replace(/[^\/]+$/, '');

  var appendLink = function(src) {
    var link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = src;
    document.documentElement.appendChild(link);
  }

  document.write('<script src="' + base + 'vendor_a.js"></script>');
  document.write('<script src="' + base + 'admin.js"></script>');
  appendLink(base + 'css/admin.css');
})()
