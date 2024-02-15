import React from 'react';
import { connect } from 'react-redux';
import { useLocation } from 'react-router-dom';

import Announcement from 'merchant/components/Announcements/Instant';
import ProductTabsWrapper from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper';

import { getProductFromLocation } from './utils';

// Note: This component aims to deprecate the
// 'PartnerDashboard/SubMerchant/List.js' component and all of its child imports.
// The ramp control from parent for this is isAccountsListRevampEnabled.
// The (v2?) scope includes legacy components for curlec org as well

const ClientsAccounts = ({ user, mode }) => {
  const location = useLocation();
  const productType = getProductFromLocation(location);

  return (
    <>
      <Announcement user={user} mode={mode} />
      <ProductTabsWrapper productType={productType} />
    </>
  );
};
export default connect(
  (state) => ({
    user: state.session.user,
    mode: state.session.mode,
    org: state.session.org,
  }),
  null,
)(ClientsAccounts);
