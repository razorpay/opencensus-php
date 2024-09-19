import { Box, Text } from '@razorpay/blade/components';
import React from 'react';
import { BarGraphVariant } from './types';
import { BAR_GRAPH_VARIANTS, BAR_VARIANTS } from './constants';
import Bar from './Bar';
import { ProductTypeBadge } from './styled';

interface AnimatedBarGraphProps {
  variant: BarGraphVariant;
  isFullWidth?: boolean;
  barLabels: Array<string>;
  graphLabel: string;
  graphValue?: string;
}

function AnimatedBarGraph({
  variant,
  isFullWidth,
  barLabels,
  graphLabel,
  graphValue,
}: AnimatedBarGraphProps): JSX.Element {
  const getBarGraphConfiguration = () => {
    switch (variant) {
      case BAR_GRAPH_VARIANTS.NEUTRAL_POSITIVE_INCREASE:
        return (
          <>
            <Bar
              variant={BAR_VARIANTS.NEUTRAL}
              height="low"
              labelAlignment="left"
              showChangeIndicator={false}
              barLabel={barLabels[0] || null}
            />
            <Bar
              variant={BAR_VARIANTS.POSITIVE}
              height="high"
              labelAlignment="right"
              showChangeIndicator={true}
              barLabel={barLabels[1] || null}
              barValue={graphValue}
            />
          </>
        );
      case BAR_GRAPH_VARIANTS.NEUTRAL_POSITIVE_DECREASE:
        return (
          <>
            <Bar
              variant={BAR_VARIANTS.NEUTRAL}
              height="high"
              labelAlignment="left"
              showChangeIndicator={false}
              barLabel={barLabels[0] || null}
            />
            <Bar
              variant={BAR_VARIANTS.POSITIVE}
              height="low"
              labelAlignment="right"
              showChangeIndicator={true}
              barLabel={barLabels[1] || null}
              barValue={graphValue}
            />
          </>
        );
      case BAR_GRAPH_VARIANTS.NEUTRAL_NEGATIVE_INCREASE:
        return (
          <>
            <Bar
              variant={BAR_VARIANTS.NEUTRAL}
              height="low"
              labelAlignment="left"
              showChangeIndicator={false}
              barLabel={barLabels[0] || null}
            />
            <Bar
              variant={BAR_VARIANTS.NEGATIVE}
              height="high"
              labelAlignment="right"
              showChangeIndicator={true}
              barLabel={barLabels[1] || null}
              barValue={graphValue}
            />
          </>
        );
      case BAR_GRAPH_VARIANTS.NEUTRAL_NEGATIVE_DECREASE:
        return (
          <>
            <Bar
              variant={BAR_VARIANTS.NEUTRAL}
              height="high"
              labelAlignment="left"
              showChangeIndicator={false}
              barLabel={barLabels[0] || null}
            />
            <Bar
              variant={BAR_VARIANTS.NEGATIVE}
              height="low"
              labelAlignment="right"
              showChangeIndicator={true}
              barLabel={barLabels[1] || null}
              barValue={graphValue}
            />
          </>
        );
      default:
        throw new Error('Invalid graph variant');
    }
  };

  return (
    <Box
      minWidth="260px"
      width={isFullWidth ? '100%' : '33.33%'}
      flexShrink="0"
      display="flex"
      justifyContent="center"
      alignItems="flex-end"
      gap="spacing.6"
      height="100%"
      position="relative"
      testID="animated-bar-graph"
    >
      {getBarGraphConfiguration()}
      <ProductTypeBadge>
        <Text variant="body" color="feedback.text.neutral.subtle" size="small" weight="medium">
          {graphLabel}
        </Text>
      </ProductTypeBadge>
    </Box>
  );
}

export default AnimatedBarGraph;
