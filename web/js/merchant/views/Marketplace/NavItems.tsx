import React from 'react';

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
  isPlatformFeeTabEnabled: boolean,
  isPartnerPlatformFeeEnabled: boolean,
): NavItemsReturnType[] => {
  const tabsData = [
    { title: 'Payments', url: '/route/payments' },
    { title: 'Transfers', url: '/route/transfers' },
    {
      title: isPartnerPlatformFeeEnabled ? 'Platform Fee' : 'Partner Fee',
      url: '/route/platformfee',
      hidden: !isPlatformFeeTabEnabled,
    },
    { title: 'Reversals', url: '/route/reversals' },
    { title: 'Accounts', url: '/route/accounts' },
    { title: isNewTab('Batch Upload'), url: '/route/batchuploads' },
  ];
  return tabsData;
};
