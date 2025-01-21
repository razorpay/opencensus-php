import React, { Suspense } from 'react';
import { Route, Routes } from 'react-router-dom';
import { Spinner, Box } from '@razorpay/blade/components';

import withOnboardingRedirect from '@apps/digital-bills/src/bootstrap/Hoc/WithOnboardingRedirect';

const OverviewDashboard = React.lazy(
  () =>
    import(
      /* webpackChunkName: "OverviewDashboard" */ '@apps/digital-bills/src/views/OverviewDashboard'
    ),
);
const BillDetails = React.lazy(
  () => import(/* webpackChunkName: "BillDetails" */ '@apps/digital-bills/src/views/BillDetails'),
);
const BillsView = React.lazy(
  () => import(/* webpackChunkName: "BillsView" */ '@apps/digital-bills/src/views/BillsView'),
);
const Feedback = React.lazy(
  () => import(/* webpackChunkName: "Feedback" */ '@apps/digital-bills/src/views/Feedback'),
);
const ConsumerProfiling = React.lazy(
  () =>
    import(
      /* webpackChunkName: "ConsumerProfiling" */ '@apps/digital-bills/src/views/ConsumerProfiling'
    ),
);
const UsageAndInvoices = React.lazy(
  () =>
    import(
      /* webpackChunkName: "UsageAndInvoices" */ '@apps/digital-bills/src/views/UsageAndInvoices'
    ),
);
const BillCampaigns = React.lazy(
  () =>
    import(/* webpackChunkName: "BillCampaigns" */ '@apps/digital-bills/src/views/BillCampaigns'),
);
const CommunicationCampaigns = React.lazy(
  () =>
    import(
      /* webpackChunkName: "CommunicationCampaigns" */ '@apps/digital-bills/src/views/CommunicationCampaigns'
    ),
);
const AutoEngagement = React.lazy(
  () =>
    import(/* webpackChunkName: "AutoEngagement" */ '@apps/digital-bills/src/views/AutoEngagement'),
);
const MediaBank = React.lazy(
  () => import(/* webpackChunkName: "MediaBank" */ '@apps/digital-bills/src/views/MediaBank'),
);
const Settings = React.lazy(
  () => import(/* webpackChunkName: "Settings" */ '@apps/digital-bills/src/views/Settings'),
);
const CouponManagement = React.lazy(
  () =>
    import(
      /* webpackChunkName: "CouponManagement" */ '@apps/digital-bills/src/views/CouponManagement'
    ),
);
const SurveyManagement = React.lazy(
  () =>
    import(
      /* webpackChunkName: "SurveyManagement" */ '@apps/digital-bills/src/views/SurveyManagement'
    ),
);
const CustomerSegmentation = React.lazy(
  () =>
    import(
      /* webpackChunkName: "CustomerSegmentation" */ '@apps/digital-bills/src/views/CustomerSegmentation'
    ),
);

const renderLoader = () => (
  <Box display="flex" justifyContent="center" height="100%">
    <Spinner accessibilityLabel="BillMe Application loading" />
  </Box>
);

function DigitalBillsRouter(): React.ReactElement {
  return (
    <Suspense fallback={renderLoader()}>
      <Routes>
        <Route path="" element={<OverviewDashboard />} />
        <Route path="bills" element={<BillsView />} />
        <Route path="bills/:id" element={<BillDetails />} />
        <Route path="feedback" element={<Feedback />} />
        <Route path="consumer-profiling" element={<ConsumerProfiling />} />
        <Route path="coupon-management" element={<CouponManagement />} />
        <Route path="survey-management" element={<SurveyManagement />} />
        <Route path="consumer-segmentation" element={<CustomerSegmentation />} />
        <Route path="usage-and-invoices" element={<UsageAndInvoices />} />
        <Route path="bill-campaigns" element={<BillCampaigns />} />
        <Route path="communication-campaigns" element={<CommunicationCampaigns />} />
        <Route path="auto-engagement" element={<AutoEngagement />} />
        <Route path="media-bank" element={<MediaBank />} />
        <Route path="settings" element={<Settings />} />
      </Routes>
    </Suspense>
  );
}

export default withOnboardingRedirect(DigitalBillsRouter);
