import React from 'react';

import IframePageWrapper from '@apps/digital-bills/src/common/components/IframePageWrapper';
import { PAGE_BREADCRUMBS } from '@apps/digital-bills/src/views/CouponManagement/constants';

const CouponManagement = (): React.ReactElement => (
  <IframePageWrapper breadcrumbs={PAGE_BREADCRUMBS} pathname="/auto-engage/coupons" />
);

export default CouponManagement;
