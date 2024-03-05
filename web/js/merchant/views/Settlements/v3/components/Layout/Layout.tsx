import React from 'react';
import { ChevronLeftIcon, ExternalLinkIcon, Heading, Link, Box } from '@razorpay/blade/components';
import { useLocation, useNavigate } from 'react-router-dom';

import { LayoutPropsInterface } from 'merchant/views/Settlements/v3/typings';

const Layout = ({ children, settlementId }: LayoutPropsInterface): JSX.Element => {
  const location = useLocation();
  const navigate = useNavigate();

  const { state } = location ?? {};
  const { prevPath = '' } = state ?? {};

  const handleGoBack = (): void => {
    if (prevPath) {
      navigate(-1);
    }

    navigate('/settlements');
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

export default Layout;
