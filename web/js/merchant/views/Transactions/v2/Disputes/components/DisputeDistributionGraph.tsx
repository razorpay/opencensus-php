import React from 'react';
import { Box, Tooltip } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { compose } from 'redux';

import { COMMON_Z_INDEX } from 'common/constant';
import { Store } from 'common/typings';
import { DisputeDataResponse } from 'merchant/views/Transactions/v2/Analytics/types';
import { GraphSection } from 'merchant/views/Transactions/v2/Disputes/styled';
import { getGraphValues } from 'merchant/views/Transactions/v2/Disputes/utils';
import { Currency } from 'merchant/views/Transactions/v2/Payments/types';

type DisputeDistributionGraphProps = {
  data: DisputeDataResponse;
  user: Store['session']['user'];
};

const DisputeDistributionGraph = ({ data, user }: DisputeDistributionGraphProps): JSX.Element => {
  const { openDisputes, underReviewDisputes, lostDisputes, wonDisputes } = data;

  const graphValues = getGraphValues({
    openDisputes,
    wonDisputes,
    underReviewDisputes,
    lostDisputes,
    data,
    currency: user.merchant?.currency as Currency,
  });

  return (
    <Box
      backgroundColor="surface.background.gray.subtle"
      borderRadius="large"
      padding="spacing.1"
      maxWidth="50%"
    >
      <Box
        display="flex"
        flexDirection="row"
        alignItems="center"
        borderRadius="medium"
        gap="spacing.1"
        overflow="hidden"
      >
        {graphValues.map((graphValue) => {
          return graphValue.count ? (
            <Tooltip
              content={graphValue.content}
              placement="bottom"
              zIndex={COMMON_Z_INDEX.DROPDOWN_OVERLAY}
            >
              <GraphSection
                status={graphValue.status}
                distributionPercentage={graphValue.percent}
              />
            </Tooltip>
          ) : null;
        })}
      </Box>
    </Box>
  );
};

const mapStateToProps = (state) => ({
  mode: state.session.mode,
  user: state.session.user,
});

export default compose(connect(mapStateToProps, null)(DisputeDistributionGraph));
