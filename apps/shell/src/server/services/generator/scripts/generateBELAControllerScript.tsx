import { API_URL } from '@apps/shell/src/env';
import React from 'react';

export const generateBELAControllerScript = (appLocals: { user: any; org: any }) => {
  const org = appLocals.org;
  const user = appLocals.user;
  const apiHost = API_URL;

  return (
    <script
      key="rzp-miscellaneous"
      dangerouslySetInnerHTML={{
        __html: `window.rzp_user = ${JSON.stringify(user)};
                   window.rzp_org = ${JSON.stringify(org)};
                   window.api_host = ${JSON.stringify(apiHost)};
             `,
      }}
    />
  );
};
