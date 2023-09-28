import React from 'react';
import { withRouter } from 'common/deprecated/withRouter';
import { ChevronLeftIcon, ExternalLinkIcon, Heading, Link, Box } from '@razorpay/blade/components';

import { LayoutPropsInterface } from 'merchant/views/Settlements/v3/typings';

const Layout = ({
  children,
  history,
  settlementId,
  location,
}: LayoutPropsInterface): JSX.Element => {
  const { state } = location ?? {};
  const { prevPath = '' } = state ?? {};

  const handleGoBack = (): void => {
    if (prevPath) {
      history.goBack();
    }
    history.push('/settlements');
  };

  return (
    <Box
      display="flex"
      flexDirection="column"
      gap={{ base: 'spacing.4', m: '14px' }}
      padding={{ base: ['25px', 'spacing.4'], m: ['spacing.6', 'spacing.7'] }}
    >
      <Box display="flex" flexDirection="column" gap="spacing.3">
        <Link variant="button" icon={ChevronLeftIcon} iconPosition="left" onClick={handleGoBack}>
          Go back
        </Link>
        <Box
          display="flex"
          justifyContent="space-between"
          alignItems={{ base: 'center', m: 'flex-start' }}
        >
          <Heading size="medium" weight="bold">
            Settlement details - {settlementId}
          </Heading>
          <Link
            href="https://razorpay.com/settlement/"
            icon={ExternalLinkIcon}
            iconPosition="right"
            size="medium"
            rel="noreferrer noopener"
            target=" _blank"
          >
            Know more
          </Link>
        </Box>
      </Box>
      <Box display="flex" flexDirection="column" gap={{ base: 'spacing.4', m: 'spacing.5' }}>
        {children}
      </Box>
    </Box>
  );
};

export default withRouter(Layout);
