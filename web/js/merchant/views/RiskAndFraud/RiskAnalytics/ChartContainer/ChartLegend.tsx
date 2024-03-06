import React from 'react';
import { Box, Text, TrendingUpIcon } from '@razorpay/blade/components';
import styled from 'styled-components';
import { getCurrencySymbol as i18nifyGetCurrencySymbol } from '@razorpay/i18nify-js/currency';

import { ENTITY_RATIO, METRIC_VALUE } from 'merchant/views/RiskAndFraud/RiskAnalytics/constants';
import { AnalyticsEntity, MetricOptions } from 'merchant/views/RiskAndFraud/RiskAnalytics/types';

import { CHART_OPTIONS_MAPPING, LEGEND_COLORS_MAPPING } from './constants';

type ChartLegendProps = {
  entity: AnalyticsEntity;
  metric: MetricOptions;
};

const StyledLegendIndicator = styled.div(
  ({ theme, color }) => `
  width: 12px;
  height: 12px;
  margin-right: ${theme.spacing[2]}px;
  background-color: ${color};
`,
);

const ChartLegend = (props: ChartLegendProps) => {
  const { entity, metric } = props;
  const legends = CHART_OPTIONS_MAPPING[entity][metric];

  return (
    <Box
      testID="chart-legends"
      display="flex"
      flexDirection="row"
      justifyContent="center"
      gap="spacing.3"
    >
      {legends.map(({ label, value }) => {
        const color = LEGEND_COLORS_MAPPING[value];

        return (
          <Box
            key={value}
            display="flex"
            flexDirection="row"
            alignItems="center"
            padding="spacing.3"
            borderColor="surface.border.normal.lowContrast"
            backgroundColor="surface.background.level3.lowContrast"
          >
            {value === ENTITY_RATIO ? (
              <>
                <TrendingUpIcon
                  data-testid="legend-indicator"
                  size="small"
                  color={color}
                  marginRight="spacing.2"
                />
                <Text size="small">{`${label} (%)`}</Text>
              </>
            ) : (
              <>
                <StyledLegendIndicator data-testid="legend-indicator" color={color} />
                <Text size="small">
                  {metric === METRIC_VALUE
                    ? `${label} (in ${i18nifyGetCurrencySymbol('INR')})`
                    : label}
                </Text>
              </>
            )}
          </Box>
        );
      })}
    </Box>
  );
};

export default React.memo(ChartLegend, (prevProps, nextProps) => {
  return prevProps.metric === nextProps.metric && prevProps.entity === nextProps.entity;
});
