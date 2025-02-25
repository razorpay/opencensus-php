import { BANKING_SERVICE_URL } from '@apps/shell/src/env';
import React from 'react';

// TODO: FIX THIS
export const generateBankingServicePGClientScript = () => {
  const rzpAppHost = BANKING_SERVICE_URL;
  const rzpAppName = 'businessbanking';
  return (
    <script
      key="banking-service-script"
      dangerouslySetInnerHTML={{
        __html: `document.domain = window.location.hostname.split(".").slice(-2).join(".");
                
                 window.RZP = window.RZP || {}; 
                 
                 if (
                  window.parent !== window &&
                  window.parent.location.href.indexOf(${JSON.stringify(rzpAppHost)})
                 ) {
              
                  document.write(${JSON.stringify(
                    <script src="${rzpAppHost}/dist/pgClient.js"></script>,
                  )});
              
                  window.RZP.appHost = ${JSON.stringify(rzpAppHost)};
                  window.RZP.appName = ${JSON.stringify(rzpAppName)};
              
                  window.rzpTicketSystem = {
                    hostname: ${JSON.stringify(rzpAppHost)},
                  };
                 }`,
      }}
    />
  );
};
