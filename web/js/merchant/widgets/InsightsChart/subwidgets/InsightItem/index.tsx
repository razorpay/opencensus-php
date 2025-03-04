import React, { useEffect } from 'react';
import { Box, Text } from '@razorpay/blade/components';

import { SubwidgetSkeleton } from 'merchant/widgets/InsightsChart/Loader';
import { InsightItemChartWrapper } from 'merchant/widgets/InsightsChart/styled';
import { CTAText } from 'merchant/widgets/InsightsChart/subwidgets/InsightItem/CTAText';
import LineChart from 'merchant/widgets/InsightsChart/subwidgets/InsightItem/LineChart';
import { InsightItemProps } from 'merchant/widgets/InsightsChart/subwidgets/InsightItem/types';
import { durationOptionsSubtextMap } from 'merchant/widgets/InsightsChart/utils';
import { Change } from 'merchant/widgets/common/Change';
import { ErrorState } from 'merchant/widgets/common/ErrorState';
import { TooltipWidget } from 'merchant/widgets/common/Tooltip';
import { convertToNumber, getCommonWidget } from 'merchant/widgets/common/utils';
import { useRetryWidget } from 'merchant/widgets/hooks';
import { getUcsAliasFromQueryKey, track } from 'merchant/widgets/utils';

import { EmptyLineChart } from './utils';
import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';

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
  action,
}) => {
  const [isRetrying, retryHandler] = useRetryWidget(queryKey);

  const screen = getUcsAliasFromQueryKey(queryKey) ?? '';
  const { widgetId } = analyticsProperties;
  const subWidgetId = `${widgetId}.${type}.${id}`;

  const { abExperiments } = useSplitzService();

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
        borderColor="surface.border.gray.subtle"
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
  const changeValue = convertToNumber(change);
  const isChartData =
    chart_data &&
    chart_data.data &&
    chart_data.data.length > 0 &&
    chart_data.data[0].points?.length > 0;

  const variant = changeValue > 0 ? 'increase' : 'decrease';
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
      borderColor="surface.border.gray.muted"
      gap="spacing.4"
      alignItems="end"
      flexDirection="column"
    >
      <Box width="100%" display="flex" justifyContent="space-between" alignItems="flex-end">
        <Box display="flex" flexDirection="column" flex="1">
          <Box display="flex">
            <Text marginRight="spacing.2" size="large" weight="semibold">
              {title}
            </Text>
            {tooltip_text && <TooltipWidget tooltip_text={tooltip_text} />}
          </Box>
          <Text marginBottom="spacing.6" color="surface.text.gray.muted">
            {durationOptionsSubtextMap[date]}
          </Text>
          <CTAText value={value} value_type={value_type} currency={currency} />
        </Box>
        <Box>
          <InsightItemChartWrapper>
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
          </InsightItemChartWrapper>
        </Box>
      </Box>
      <Box width="100%" display="flex" justifyContent="space-between" alignItems="center">
        <Box display="inline-flex" gap="spacing.2" flexDirection="column">
          {value > 0 ? (
            <Change
              text={sub_text}
              variant={changeValue > 0 ? 'increase' : 'decrease'}
              type={change_type}
              isInverted={isChangeBehaviourInverted}
            />
          ) : null}
        </Box>
        <Box>
          {isExperimentEnabled(abExperiments?.rtux_top_insights_details_cta) &&
          !!Object.keys(action || {}).length
            ? getCommonWidget({
                widget: action,
                analyticsProperties: {
                  ...analyticsProperties,
                  subWidgetId,
                  actionBy: subWidgetId,
                  title,
                  screen,
                  variant,
                },
              })
            : null}
        </Box>
      </Box>
    </Box>
  );
};

export default InsightItem;
