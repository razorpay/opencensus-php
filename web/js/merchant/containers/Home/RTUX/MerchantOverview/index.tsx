import React, { useMemo } from 'react';
import { Heading, Text, Box } from '@razorpay/blade/components';
import { connect } from 'react-redux';

import { IMerchantOverview } from 'merchant/containers/Home/RTUX/MerchantOverview/types';
import { CommonWidgetProps } from 'merchant/widgets/types';

import MerchantOverviewData from './MerchantOverviewData';
import { getGreetingAndDate } from './utils';
import { useIsSettlementHovered } from './MerchantOverviewData/store';
import SettlementInfoBar from 'merchant/containers/Home/RTUX/MerchantOverview/MerchantOverviewData/Settlement/SettlementInfoBar';

import { useSplitzService } from 'common/splitz';

import { isSettlementSOHBlockEnabled } from 'merchant/views/Settlements/components/utils';
import {
  isBlocked,
  isRiskDisabled,
  isRiskFoh,
} from 'merchant/containers/Home/RTUX/MerchantOverview/MerchantOverviewData/Settlement/utils';

const MerchantOverviewComponent: React.FC<IMerchantOverview & CommonWidgetProps> = (props) => {
  const { user } = props;
  const username = user.user?.name || user.name;
  const settlementFeatures =
    props?.data?.hero_card_data?.settlement?.settlement_config_details?.features;
  const isCurrentBalance =
    typeof props?.data?.hero_card_data?.settlement?.current_balance === 'string';
  const blocked = settlementFeatures ? isBlocked(settlementFeatures) : false;

  const splitz = useSplitzService();
  const { formattedDate, greeting } = useMemo(() => getGreetingAndDate(), []);
  const setIsHovered = useIsSettlementHovered((state) => state.setIsHovered);
  const isEnabled = isSettlementSOHBlockEnabled(splitz) && blocked;
  const isFOHMerchant =
    isSettlementSOHBlockEnabled(splitz) && (isRiskFoh() || isRiskDisabled() || isEnabled);

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
      elevation="lowRaised"
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
        position={isEnabled ? 'relative' : 'static'}
        padding={{
          base: isEnabled ? 'spacing.4' : 'spacing.7',
          m: isFOHMerchant ? 'spacing.0' : 'spacing.8',
        }}
        paddingLeft={{ m: isEnabled ? 'spacing.7' : 'none' }}
      >
        <MerchantOverviewData {...props} isFOHMerchant={isFOHMerchant} />
      </Box>

      {isEnabled && isCurrentBalance && (
        <Box
          onMouseEnter={() => setIsHovered(true)}
          onMouseLeave={() => setIsHovered(false)}
          backgroundImage="linear-gradient(94deg, #E5EFFF 13.36%, rgba(255, 255, 255, 0.61) 93.83%)"
          borderRadius="large"
          padding={{ base: 'spacing.5', m: 'spacing.6' }}
        >
          <SettlementInfoBar {...props} />
        </Box>
      )}
    </Box>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export const MerchantOverview = connect(mapStateToProps, null)(MerchantOverviewComponent);
