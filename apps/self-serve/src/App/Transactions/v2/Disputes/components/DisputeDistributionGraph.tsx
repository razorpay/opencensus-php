import React from 'react';
import { useStore } from 'shell/commonStore';
import { Box, Tooltip } from '@razorpay/blade/components';
import { DisputeDataResponse } from 'apps/self-serve/src/App/Transactions/v2/Analytics/types';
import { GraphSection } from 'apps/self-serve/src/App/Transactions/v2/Disputes/styled';
import { getGraphValues } from 'apps/self-serve/src/App/Transactions/v2/Disputes/utils';
import { Currency } from 'apps/self-serve/src/App/Transactions/v2/Payments/types';
import { COMMON_Z_INDEX } from '@dashboard/shared-utils/constants';

type DisputeDistributionGraphProps = {
  data: DisputeDataResponse;
};

const DisputeDistributionGraph = ({ data }: DisputeDistributionGraphProps): JSX.Element => {
  const { openDisputes, underReviewDisputes, lostDisputes, wonDisputes } = data;

  const session = useStore((state) => state.session);
  const user = session.user;

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

export default DisputeDistributionGraph;
