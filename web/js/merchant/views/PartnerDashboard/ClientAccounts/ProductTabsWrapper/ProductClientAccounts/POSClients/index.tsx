import React from 'react';
import { Route, Routes } from 'react-router-dom';

import InviteNavLinks from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/InviteNavLinks';
import {
  trackAcceptedInvitesClick,
  trackAllInvitesClick,
} from 'merchant/views/PartnerDashboard/SubMerchant/analytics';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { RouteGuard } from 'merchant_common/components/RouteGuard';

import AcceptedInvites from './AcceptedInvites';
import AllInvites from './AllInvites';

const PaymentsClients = (): JSX.Element => {
  return (
    <>
      <InviteNavLinks
        productType={PRODUCT_TYPE.POS}
        onAcceptedInvitesClick={trackAcceptedInvitesClick}
        onAllInvitesClick={trackAllInvitesClick}
      />

      <Routes>
        <Route
          path="all"
          element={
            <RouteGuard>
              <AllInvites />
            </RouteGuard>
          }
        />
        <Route
          path="*"
          element={
            <RouteGuard>
              <AcceptedInvites />
            </RouteGuard>
          }
        />
      </Routes>
    </>
  );
};
export default PaymentsClients;
