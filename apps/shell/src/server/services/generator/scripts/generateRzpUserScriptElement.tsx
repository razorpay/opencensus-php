import React from 'react';

export const generateRzpUserScriptElement = (user: any): JSX.Element => {
  return (
    <script
      key="rzp-user"
      dangerouslySetInnerHTML={{
        __html: `window.rzp_user = ${JSON.stringify(user)};`,
      }}
    />
  );
};
