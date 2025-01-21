import React from 'react';

import IframePageWrapper from '@apps/digital-bills/src/common/components/IframePageWrapper';
import { PAGE_BREADCRUMBS } from '@apps/digital-bills/src/views/AutoEngagement/constants';

const AutoEngagement = (): React.ReactElement => (
  <IframePageWrapper breadcrumbs={PAGE_BREADCRUMBS} pathname="/journey" />
);

export default AutoEngagement;
