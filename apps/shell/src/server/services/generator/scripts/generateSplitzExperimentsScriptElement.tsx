import React from 'react';

export const generateSplitzExperimentsScriptElement = (appLocals: any): JSX.Element => {
  const role = appLocals?.user?.role;
  const bankingRole = appLocals?.user?.banking_role;

  const isOneHomeEnabled =
    Boolean(appLocals?.server_evaluated_experiments?.['one-home']) &&
    Boolean(appLocals?.server_evaluated_experiments?.['one-dashboard']) &&
    (role === 'admin' || role === 'owner' || bankingRole === 'admin' || bankingRole === 'owner');

  // Consumed by SwitchMerchantTypeahead.tsx to show/hide the create merchant CTA in one-home profile dropdown
  const isCreateMerchantCTAEnabled = Boolean(
    appLocals?.server_evaluated_experiments?.['create_merchant_cta'],
  );

  const isBillMeEnabled = Boolean(appLocals?.server_evaluated_experiments?.['bill_me_enabled']);

  return (
    <script
      key="splitz-experiments"
      dangerouslySetInnerHTML={{
        __html: `
        // Server evaluated Splitz experiments
        window.IS_ONE_HOME_ENABLED = ${isOneHomeEnabled};
        window.IS_CREATE_MERCHANT_CTA_EANABLED = ${isCreateMerchantCTAEnabled};
        window.IS_BILL_ME_ENABLED = ${isBillMeEnabled};
        `,
      }}
    />
  );
};
