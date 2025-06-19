export const loadPlotlineScript = () => {
  const script = document.createElement('script');
  script.innerHTML = `(function(pl,ot,line) {
        if (window.plotline) return;
        window._plotQueue = window._plotQueue || [];
        window.plotline = function() {window._plotQueue.push(arguments)}
        var a = document.createElement('script');
        a.async = 1;  
        a.src = 'https://sdk.plotline.so/plotline-engage@latest/sdk.min.js';
        var b = document.getElementsByTagName('script')[0];
        b.parentNode.insertBefore(a, b);
      })()`;
  script.async = true;

  document.body.appendChild(script);
};
