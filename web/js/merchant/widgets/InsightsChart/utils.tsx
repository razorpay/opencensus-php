import React from 'react';
import { Box } from '@razorpay/blade/components';
import { renderWidgetProps } from 'merchant/widgets/types';
import { subWidgetKeyToComponentMapping } from 'merchant/widgets/InsightsChart/mapping';
import { renderWidget } from 'merchant/widgets/utils';
import { DateRangeValues } from 'merchant/widgets/common/Select/types';

export const getSubWidget = ({
  widget,
  isLoading = false,
  queryKey = [],
  props = {},
  analyticsProperties,
}: renderWidgetProps) =>
  renderWidget({
    widget,
    isLoading,
    queryKey,
    widgetMapping: subWidgetKeyToComponentMapping,
    props,
    analyticsProperties,
  });

export const durationOptionsMap: Record<DateRangeValues, string> = {
  today: 'Today',
  last_7_days: 'Last week',
  last_30_days: 'Last 30 days',
};

export const durationOptionsSubtextMap: Record<DateRangeValues, string> = {
  today: 'Today',
  last_7_days: 'Last 7 days',
  last_30_days: 'Last 30 days',
};

interface InsightsChartWrapperProps {
  children: React.ReactNode;
}

export const InsightsChartWrapper = ({ children }: InsightsChartWrapperProps) => (
  <Box
    display="flex"
    flexDirection="column"
    gap="spacing.5"
    padding="spacing.7"
    marginX={{ base: 'spacing.0', m: 'spacing.6' }}
    backgroundColor="surface.background.gray.intense"
    borderRadius="large"
  >
    {children}
  </Box>
);

export const InsightsChartHeaderWrapper = ({ children }: { children: React.ReactNode }) => (
  <Box display="flex" flexDirection="row" justifyContent="space-between" alignItems="center">
    {children}
  </Box>
);

export const InsightsChartContentWrapper = ({ children }: { children: React.ReactNode }) => (
  <Box display="grid" gridTemplateColumns={{ base: '1fr', xl: '1fr 1fr' }} gap="spacing.7">
    {children}
  </Box>
);
