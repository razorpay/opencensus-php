import React from 'react';

export const generateDCSScriptElement = () => {
  return (
    <script
      defer
      key="dcs-i"
      dangerouslySetInnerHTML={{
        __html: `var _dcq = _dcq || [];
                 var _dcs = _dcs || {};
                 _dcs.account = '9421167';

                 (function () {
                    var dc = document.createElement('script');
                    dc.type = 'text/javascript'; dc.async = true;
                    dc.src = '//tag.getdrip.com/9421167.js';
                    var s = document.getElementsByTagName('script')[0];
                    s.parentNode.insertBefore(dc, s);
                 })();`,
      }}
    />
  );
};
