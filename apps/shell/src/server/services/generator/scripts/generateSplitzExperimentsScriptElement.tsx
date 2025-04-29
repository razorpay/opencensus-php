import React from 'react';

export const generateSplitzExperimentsScriptElement = (appLocals: any): JSX.Element => {
  const isOneHomeEnabled = Boolean(appLocals?.server_evaluated_experiments?.['one-home']);

  return (
    <script
      key="splitz-experiments"
      dangerouslySetInnerHTML={{
        __html: `
        // Server evaluated Splitz experiments
        window.IS_ONE_HOME_ENABLED = ${isOneHomeEnabled};
        `,
      }}
    />
  );
};
