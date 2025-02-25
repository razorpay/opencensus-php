import React from 'react';

export const generateBladeCoverageScriptElement = () => {
  return (
    <script
      key="blade-coverage-script"
      dangerouslySetInnerHTML={{
        __html: `
          let script = document.createElement('script');
          script.src = 'https://app-metrics.razorpay.com/static/blade-analytics.js';
          script.async = true;
          document.body.append(script);
          script.onload = () => {
            window.initBladeCoverageAnalytics?.({ businessUnit: 'payments' });
          };
        `,
      }}
    />
  );
};
