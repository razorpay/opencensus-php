import React from 'react';
import { Box } from '@razorpay/blade/components';

import Breadcrumbs from '@apps/digital-bills/src/common/components/Breadcrumbs';
import Iframe from '@apps/digital-bills/src/common/components/Iframe';

import type { BreadCrumbType } from '@apps/digital-bills/src/common/components/Breadcrumbs/types';

type IframePageWrapperProps = {
  breadcrumbs: BreadCrumbType[];
  pathname: string;
};

const IframePageWrapper = ({
  breadcrumbs,
  pathname,
}: IframePageWrapperProps): React.ReactElement => {
  return (
    <>
      <Box marginTop={{ base: 'spacing.6', m: 'spacing.0' }} marginBottom="spacing.4">
        <Breadcrumbs items={breadcrumbs} />
      </Box>
      <Box height="70vh">
        <Iframe pathname={pathname} />
      </Box>
    </>
  );
};

export default IframePageWrapper;
