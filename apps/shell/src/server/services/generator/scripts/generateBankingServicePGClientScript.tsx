import { BANKING_SERVICE_URL } from '@apps/shell/src/env';
import React from 'react';

export const generateBankingServicePGClientScript = (clientTemplate: string) => {
  const rzpAppHost = BANKING_SERVICE_URL;
  const rzpAppName = 'businessbanking';

  return (
    <script
      key="banking-service-script"
      dangerouslySetInnerHTML={{
        __html: `
          document.domain = window.location.hostname.split(".").slice(-2).join(".");

          window.RZP = window.RZP || {};

          if (
            window.parent !== window &&
            ~window.parent.location.href.indexOf(${JSON.stringify(rzpAppHost)})
          ) {
            const script = document.createElement('script');
            script.src = ${JSON.stringify(rzpAppHost)} + "/dist/pgClient.js";
            document.head.appendChild(script);

            window.RZP.appHost = ${JSON.stringify(rzpAppHost)};
            window.RZP.appName = ${JSON.stringify(rzpAppName)};

            if (${JSON.stringify(clientTemplate)} !== "one-dashboard") {
              window.rzpTicketSystem = {
                hostname: ${JSON.stringify(rzpAppHost)},
              };
            }
          }
        `,
      }}
    />
  );
};
