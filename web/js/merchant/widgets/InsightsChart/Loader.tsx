import React, { Fragment } from 'react';
import { Box, Heading, Skeleton } from '@razorpay/blade/components';
import { renderInput } from 'merchant/widgets/common/utils';
import { InsightsChartLoadingProps } from 'merchant/widgets/InsightsChart/types';
import {
  getSubWidget,
  InsightsChartContentWrapper,
  InsightsChartHeaderWrapper,
  InsightsChartWrapper,
} from 'merchant/widgets/InsightsChart/utils';

const InsightsChartWidgetLoader: React.FC<InsightsChartLoadingProps> = ({
  title,
  components,
  inputs,
}): JSX.Element => {
  const input = inputs.find((input) => input.type === 'select');
  return (
    <InsightsChartWrapper title={title}>
      <InsightsChartHeaderWrapper>
        <Heading size="medium">{title}</Heading>
        {input && renderInput({ widget: input })}
      </InsightsChartHeaderWrapper>
      <InsightsChartContentWrapper>
        {Array.from({ length: components.length ? components.length : 6 }, (v, k) => (
          <Fragment key={k}>
            {getSubWidget({
              widget: components.length
                ? components[k]
                : {
                    type: 'insight_item',
                  },
              isLoading: true,
            })}
          </Fragment>
        ))}
      </InsightsChartContentWrapper>
    </InsightsChartWrapper>
  );
};

export function SubwidgetSkeleton() {
  return (
    <Box
      display="flex"
      justifyContent="space-between"
      borderRadius="large"
      padding="spacing.6"
      borderWidth="thinner"
      borderColor="surface.border.gray.subtle"
      gap="spacing.4"
      testID="insights-chart-loader"
    >
      <Box display="flex" flexDirection="column">
        <Skeleton width="180px" height="16px" borderRadius="max" marginBottom="spacing.3" />
        <Skeleton width="80px" height="16px" borderRadius="max" marginBottom="spacing.5" />
        <Skeleton width="100px" height="32px" borderRadius="max" />
      </Box>
      <Box>
        <Skeleton width="148px" height="92px" borderRadius="medium" />
      </Box>
    </Box>
  );
}

export default InsightsChartWidgetLoader;
