import React, { useMemo } from 'react';
import { Heading, Text, Box } from '@razorpay/blade/components';
import { connect } from 'react-redux';

import { useMobile } from 'common/hooks/useMobile';
import { IMerchantOverview } from 'merchant/containers/Home/RTUX/MerchantOverview/types';
import { CommonWidgetProps } from 'merchant/widgets/types';

import MerchantOverviewData from './MerchantOverviewData';
import { MerchantOverviewDataWrapper, MerchantOverviewWrapper } from './styled';
import { getGreetingAndDate } from './utils';

const MerchantOverviewComponent: React.FC<IMerchantOverview & CommonWidgetProps> = (props) => {
  const isMobile = useMobile();
  const { user } = props;
  const username = user.user?.name || user.name;
  const { formattedDate, greeting } = useMemo(() => getGreetingAndDate(), []);
  return (
    <MerchantOverviewWrapper isMobile={isMobile}>
      <Box
        display="flex"
        flexDirection="column"
        gap="spacing.7"
        padding="spacing.7"
        testID="merchant-overview"
      >
        <Box display="flex" flexDirection="column" gap="spacing.2" alignItems="center">
          <Heading color="interactive.text.staticWhite.normal" size="xlarge">
            {greeting}, {username}!
          </Heading>
          <Text color="interactive.text.staticWhite.normal" size="large">
            {formattedDate}
          </Text>
        </Box>
        <MerchantOverviewDataWrapper>
          <MerchantOverviewData {...props} />
        </MerchantOverviewDataWrapper>
      </Box>
    </MerchantOverviewWrapper>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export const MerchantOverview = connect(mapStateToProps, null)(MerchantOverviewComponent);
