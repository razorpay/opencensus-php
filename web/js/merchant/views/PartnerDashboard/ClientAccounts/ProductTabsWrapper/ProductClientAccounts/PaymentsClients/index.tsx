import React from 'react';
import { Route, Routes } from 'react-router-dom';

import InviteNavLinks from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/InviteNavLinks';
import {
  trackAcceptedInvitesClick,
  trackAllInvitesClick,
} from 'merchant/views/PartnerDashboard/SubMerchant/analytics';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import usePartnerDashboardExperiments from 'merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments';
import { RouteGuard } from 'merchant_common/components/RouteGuard';

import AcceptedInvites from './AcceptedInvites';
import AllInvites from './AllInvites';

const PaymentsClients = (): JSX.Element => {
  const { isPartnershipsInviteFlowEnabled, isPlatformPartnerInviteFlowEnabled } =
    usePartnerDashboardExperiments();
  // Note: productType is implicit here.
  const shouldShowAllInvites =
    isPartnershipsInviteFlowEnabled || isPlatformPartnerInviteFlowEnabled;
  return (
    <>
      {shouldShowAllInvites ? (
        <InviteNavLinks
          productType={PRODUCT_TYPE.PG}
          onAcceptedInvitesClick={trackAcceptedInvitesClick}
          onAllInvitesClick={trackAllInvitesClick}
        />
      ) : null}
      <Routes>
        <Route
          path="all"
          element={
            <RouteGuard additionalCondition={() => shouldShowAllInvites}>
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
