import { Box, Heading, Text } from '@razorpay/blade/components';
import { TooltipWidget } from 'merchant/widgets/common/Tooltip';
import React, { useEffect } from 'react';
import { InsightItemProps } from 'merchant/widgets/InsightsChart/subwidgets/InsightItem/types';
import { useRetryWidget } from 'merchant/widgets/hooks';
import { SubwidgetSkeleton } from 'merchant/widgets/InsightsChart/Loader';
import { ErrorState } from 'merchant/widgets/common/ErrorState';
import LineChart from 'merchant/widgets/InsightsChart/subwidgets/InsightItem/LineChart';
import { Change } from 'merchant/widgets/common/Change';
import { durationOptionsSubtextMap } from 'merchant/widgets/InsightsChart/utils';
import { CTAText } from 'merchant/widgets/InsightsChart/subwidgets/InsightItem/CTAText';
import { ChartWrapper } from 'merchant/widgets/InsightsChart/subwidgets/InsightItem/styled';
import { EmptyLineChart } from './utils';
import { getUcsAliasFromQueryKey, track } from 'merchant/widgets/utils';

const InsightItem: React.FC<InsightItemProps> = ({
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
    if (!isLoading && !isRetrying && id !== '144') {
      const properties = {
        title,
        subWidgetId,
        actionBy: subWidgetId,
        date,
        ...(error ? { error: `${error.message}` } : {}),
      };
      track({
        objectName: `widget`,
        actionName: error ? 'error' : 'loaded',
        screen,
        properties: {
          ...analyticsProperties,
          ...properties,
        },
      });
    }
  }, [isLoading, isRetrying, error]);

  // TODO: 🔴 Dispute will not be shown. Uncomment once BE adds refunds instead.
  if (id === '144') return null;

  if (isLoading || isRetrying) {
    return <SubwidgetSkeleton />;
  }

  if (error)
    return (
      <ErrorState
        borderWidth="thinner"
        borderColor="surface.border.subtle.lowContrast"
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

  const {
    value,
    value_type,
    currency,
    change,
    change_type,
    change_behavior_inverted: isChangeBehaviourInverted,
    sub_text,
    chart_data,
  } = data;

  const isChartData = chart_data && chart_data.data && chart_data.data.length > 0;

  const variant = change > 0 ? 'increase' : 'decrease';
  const finalVariant = isChangeBehaviourInverted
    ? variant === 'increase'
      ? 'decrease'
      : 'increase'
    : variant;

  return (
    <Box
      display="flex"
      borderRadius="large"
      padding="spacing.6"
      borderWidth="thin"
      borderColor="surface.border.normal.lowContrast"
      gap="spacing.4"
      alignItems={{ base: 'center', m: 'initial' }}
    >
      <Box display="flex" flexDirection="column" flex="1" marginBottom="spacing.2">
        <Box display="flex">
          <Heading marginRight="spacing.2">{title}</Heading>
          {tooltip_text && <TooltipWidget tooltip_text={tooltip_text} />}
        </Box>
        <Text marginBottom="spacing.6" type="subdued">
          {durationOptionsSubtextMap[date]}
        </Text>
        <Box
          display="inline-flex"
          gap={{ base: 'spacing.2', xl: 'spacing.4' }}
          flexDirection={{ base: 'column', xl: 'row' }}
        >
          <CTAText value={value} value_type={value_type} currency={currency} />
          {value > 0 ? (
            <Change
              text={sub_text}
              variant={change > 0 ? 'increase' : 'decrease'}
              type={change_type}
              isInverted={isChangeBehaviourInverted}
            />
          ) : null}
        </Box>
      </Box>
      <ChartWrapper>
        <Box
          width={{ base: '80px', m: '140px' }}
          height={{ base: '80px', m: '80px' }}
          position="relative"
          backgroundColor="transparent"
        >
          {isChartData ? (
            <LineChart
              chartData={chart_data}
              isChangePositive={finalVariant === 'increase'}
              unit={date}
            />
          ) : (
            <EmptyLineChart />
          )}
        </Box>
      </ChartWrapper>
    </Box>
  );
};

export default InsightItem;
