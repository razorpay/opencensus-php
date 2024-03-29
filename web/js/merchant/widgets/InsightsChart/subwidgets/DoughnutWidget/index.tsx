import React, { useEffect } from 'react';
import DoughnutChart from './DoughnutChart';
import { Box, Text } from '@razorpay/blade/components';
import { durationOptionsSubtextMap } from 'merchant/widgets/InsightsChart/utils';
import { useRetryWidget } from 'merchant/widgets/hooks';
import { SubwidgetSkeleton } from 'merchant/widgets/InsightsChart/Loader';
import { ErrorState } from 'merchant/widgets/common/ErrorState';
import { TooltipWidget } from 'merchant/widgets/common/Tooltip';
import { DoughnutWidgetProps } from 'merchant/widgets/InsightsChart/subwidgets/DoughnutWidget/types';
import ChartTable from 'merchant/widgets/InsightsChart/subwidgets/DoughnutWidget/ChartTable';
import { DoughnutWrapper } from 'merchant/widgets/InsightsChart/subwidgets/DoughnutWidget/styled';
import { EmptyDoughnutChart, emptyChartTableData } from './utils';
import { getUcsAliasFromQueryKey, track } from 'merchant/widgets/utils';

const DoughnutWidget: React.FC<DoughnutWidgetProps> = ({
  id,
  title,
  tooltip_text,
  error,
  queryKey,
  data,
  date,
  isLoading,
  analyticsProperties = {},
  type,
}) => {
  const [isRetrying, retryHandler] = useRetryWidget(queryKey);

  const screen = getUcsAliasFromQueryKey(queryKey) ?? '';
  const { widgetId } = analyticsProperties;
  const subWidgetId = `${widgetId}.${type}.${id}`;

  useEffect(() => {
    if (!isLoading && !isRetrying) {
      const properties = {
        title,
        subWidgetId,
        actionBy: subWidgetId,
        date,
        ...(error ? { error: `${error.message}` } : {}),
      };
      track({
        objectName: 'widget',
        actionName: error ? 'error' : 'loaded',
        screen,
        properties: { ...analyticsProperties, ...properties },
      });
    }
  }, [isLoading, isRetrying, error]);

  if (isLoading || isRetrying) {
    return <SubwidgetSkeleton />;
  }

  if (error)
    return (
      <ErrorState
        borderWidth="thinner"
        borderColor="surface.border.gray.subtle"
        backgroundColor="transparent"
        text={`${title} couldn't be loaded`}
        retryHandler={() => retryHandler({ id, date_time: { quick: date } })}
        marginX="spacing.0"
        analyticsProperties={{
          ...analyticsProperties,
          screen,
          error: `${error.message}`,
          subWidgetId,
          actionBy: subWidgetId,
          title,
          date,
        }}
      />
    );

  const { chart_data } = data;
  const isChartData = chart_data && chart_data.data && chart_data.data.length > 0;

  return (
    <Box
      display="flex"
      borderRadius="large"
      flexDirection="column"
      padding="spacing.6"
      borderWidth="thin"
      borderColor="surface.border.gray.muted"
      gap="spacing.4"
    >
      <Box display="flex" flexDirection="column" flex="1" marginBottom="spacing.2">
        <Box display="flex">
          <Text marginRight="spacing.2" size="large" weight="semibold">
            {title}
          </Text>
          {tooltip_text && <TooltipWidget tooltip_text={tooltip_text} />}
        </Box>
        <Text marginBottom="spacing.6" color="surface.text.gray.muted">
          {durationOptionsSubtextMap[date]}
        </Text>
      </Box>
      <Box display="flex" flexDirection="row" justifyContent="space-between" gap="spacing.8">
        {isChartData ? (
          <ChartTable chartData={chart_data} />
        ) : (
          <ChartTable chartData={emptyChartTableData} />
        )}
        <DoughnutWrapper>
          <Box
            width={{ base: '80px', m: '130px' }}
            height={{ base: '80px', m: '130px' }}
            position="relative"
            backgroundColor="transparent"
          >
            {isChartData ? (
              <DoughnutChart chartData={chart_data} unit={date} />
            ) : (
              <EmptyDoughnutChart />
            )}
          </Box>
        </DoughnutWrapper>
      </Box>
    </Box>
  );
};

export default DoughnutWidget;
