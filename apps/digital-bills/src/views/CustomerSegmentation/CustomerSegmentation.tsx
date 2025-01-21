import React from 'react';

import IframePageWrapper from '@apps/digital-bills/src/common/components/IframePageWrapper';
import { PAGE_BREADCRUMBS } from '@apps/digital-bills/src/views/CustomerSegmentation/constants';

const CustomerSegmentation = (): React.ReactElement => (
  <IframePageWrapper breadcrumbs={PAGE_BREADCRUMBS} pathname="/segment" />
);

export default CustomerSegmentation;
