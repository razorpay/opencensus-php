import React from 'react';

interface NavItemsReturnType {
  title: string | React.ReactNode;
  url: string;
  hidden?: boolean;
}

interface NavItemsProps {
  isRoutePartnershipEnabled?: boolean;
  isRoutePlusPartnershipsEnabled?: boolean;
}
const isNewTab = (tabName: string): JSX.Element => (
  <span>
    {tabName}
    <span className="badge bg-success">New</span>
  </span>
);
export const navItems = (user: NavItemsProps): NavItemsReturnType[] => {
  const tabsData = [
    { title: 'Payments', url: '/route/payments' },
    { title: 'Transfers', url: '/route/transfers' },
    {
      title: 'Platform Fee',
      url: '/route/platformfee',
      hidden: !user.isRoutePartnershipEnabled && !user.isRoutePlusPartnershipsEnabled,
    },
    { title: 'Reversals', url: '/route/reversals' },
    { title: 'Accounts', url: '/route/accounts' },
    { title: isNewTab('Batch Upload'), url: '/route/batchuploads' },
  ];
  return tabsData;
};
