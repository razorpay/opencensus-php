import React, { useMemo } from 'react';
import { Heading, Text, Box } from '@razorpay/blade/components';
import { connect } from 'react-redux';

import { IMerchantOverview } from 'merchant/containers/Home/RTUX/MerchantOverview/types';
import { CommonWidgetProps } from 'merchant/widgets/types';

import MerchantOverviewData from './MerchantOverviewData';
import { getGreetingAndDate } from './utils';
import { useIsSettlementHovered } from './MerchantOverviewData/store';

const MerchantOverviewComponent: React.FC<IMerchantOverview & CommonWidgetProps> = (props) => {
  const { user } = props;
  const username = user.user?.name || user.name;
  const { formattedDate, greeting } = useMemo(() => getGreetingAndDate(), []);
  const setIsHovered = useIsSettlementHovered((state) => state.setIsHovered);

  return (
    <Box
      display="flex"
      flexDirection="column"
      gap="spacing.7"
      padding="spacing.7"
      testID="merchant-overview"
      borderRadius="medium"
      overflowX="hidden"
      backgroundImage="url('/img/rtux/hero-card-bg.jpg')"
      backgroundSize="cover"
      backgroundPosition="center center"
      marginX={{ base: 'spacing.0', m: 'spacing.6' }}
    >
      <Box display="flex" flexDirection="column" gap="spacing.2" alignItems="center">
        <Heading color="interactive.text.staticWhite.normal" size="xlarge">
          {greeting}, {username}!
        </Heading>
        <Text color="interactive.text.staticWhite.normal" size="large">
          {formattedDate}
        </Text>
      </Box>
      <Box
        onMouseEnter={() => setIsHovered(true)}
        onMouseLeave={() => setIsHovered(false)}
        backgroundImage="linear-gradient(94deg, #E5EFFF 13.36%, rgba(255, 255, 255, 0.61) 93.83%)"
        borderRadius="large"
        padding={{ base: 'spacing.7', m: 'spacing.8' }}
      >
        <MerchantOverviewData {...props} />
      </Box>
    </Box>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export const MerchantOverview = connect(mapStateToProps, null)(MerchantOverviewComponent);
