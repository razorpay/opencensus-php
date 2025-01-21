import React from 'react';

import IframePageWrapper from '@apps/digital-bills/src/common/components/IframePageWrapper';
import { PAGE_BREADCRUMBS } from '@apps/digital-bills/src/views/ConsumerProfiling/constants';

const ConsumerProfiling = (): React.ReactElement => (
  <IframePageWrapper breadcrumbs={PAGE_BREADCRUMBS} pathname="/consumer-profiling" />
);

export default ConsumerProfiling;
