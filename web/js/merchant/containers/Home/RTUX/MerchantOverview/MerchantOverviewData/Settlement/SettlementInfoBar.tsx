import React from 'react';
import { Box, useTheme } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import CurrentBalance from 'merchant/containers/Home/RTUX/MerchantOverview/MerchantOverviewData/Settlement/components/CurrentBalance';
import ViewSettlements from 'merchant/containers/Home/RTUX/MerchantOverview/MerchantOverviewData/Settlement/components/ViewSettlements';
import PreviousSettlements from 'merchant/containers/Home/RTUX/MerchantOverview/MerchantOverviewData/Settlement/components/PreviousSettlements';

const SettlementInfoBar: React.FC<any> = ({ data }) => {
  const { theme } = useTheme();
  const { matchedBreakpoint } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isLargeScreen = matchedBreakpoint === 'xl';
  const isMediumScreen = matchedBreakpoint === 'l';
  const flexDirection = isLargeScreen ? 'row' : 'column';

  return (
    <Box>
      {isMediumScreen ? (
        <Box
          display="flex"
          justifyContent={{ base: 'space-between' }}
          paddingX={{ m: 'spacing.8', base: 'spacing.3' }}
          alignItems="center"
        >
          <Box display="flex" flexDirection="column">
            <CurrentBalance data={data} />
            <PreviousSettlements data={data} />
          </Box>
          <ViewSettlements />
        </Box>
      ) : (
        <Box
          display="flex"
          justifyContent={{ base: 'space-between', sm: 'none' }}
          paddingX={{ m: 'spacing.3', base: 'spacing.3' }}
          alignItems={isLargeScreen ? 'baseline' : 'flex-start'}
          flexDirection={flexDirection}
        >
          <CurrentBalance data={data} />
          <PreviousSettlements data={data} />
          <ViewSettlements />
        </Box>
      )}
    </Box>
  );
};

export default SettlementInfoBar;
