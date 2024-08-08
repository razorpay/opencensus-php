import React from 'react';

import User from 'common/typings/User';

interface NavItemsReturnType {
  title: string | React.ReactNode;
  url: string;
  hidden?: boolean;
}

const isNewTab = (tabName: string): JSX.Element => (
  <span>
    {tabName}
    <span className="badge bg-success">New</span>
  </span>
);
export const navItems = (
  user: User,
  isPlatformFeeTabEnabled: boolean,
  isPartnerPlatformFeeEnabled: boolean,
): NavItemsReturnType[] => {
  const tabsData = [
    { title: 'Payments', url: '/route/payments', hidden: user.isPartnerRole },
    // Note: allow only Transfers tab to be visible for Partner role
    { title: 'Transfers', url: '/route/transfers' },
    {
      title: isPartnerPlatformFeeEnabled ? 'Platform Fee' : 'Partner Fee',
      url: '/route/platformfee',
      hidden: !isPlatformFeeTabEnabled || user.isPartnerRole,
    },
    { title: 'Reversals', url: '/route/reversals', hidden: user.isPartnerRole },
    { title: 'Accounts', url: '/route/accounts', hidden: user.isPartnerRole },
    {
      title: isNewTab('Batch Upload'),
      url: '/route/batchuploads',
      hidden: user.isPartnerRole || user.isOrgCurlec,
    },
  ];

  return tabsData;
};
