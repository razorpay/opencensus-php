import React from 'react';
import { ArrowRightIcon, Box, Link, useTheme } from '@razorpay/blade/components';

import { NavLink } from 'react-router-dom';
import { ROUTES } from 'merchant/containers/Home/RTUX/MerchantOverview/constants';
import { useBreakpoint } from '@razorpay/blade/utils';

const ViewSettlements = () => {
  const { theme } = useTheme();
  const { matchedBreakpoint } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isSmallScreen =
    matchedBreakpoint === 's' || matchedBreakpoint === 'xs' || matchedBreakpoint === 'm';
  return (
    <Box marginTop={isSmallScreen ? 'spacing.4' : 'spacing.0'}>
      <NavLink to={ROUTES.SETTLEMENT}>
        <Link icon={ArrowRightIcon} iconPosition="right" size="small">
          View all settlements
        </Link>
      </NavLink>
    </Box>
  );
};

export default ViewSettlements;
