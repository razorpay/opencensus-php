import React, { useEffect, useState } from 'react';
import { Heading, Box } from '@razorpay/blade/components';
import moment from 'moment';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { useSplitzService } from 'common/splitz';
import { isDateRangeForInsightChartsEnabled } from 'merchant/containers/Home/RTUX/utils';
import { InsightsChartProps } from 'merchant/widgets/InsightsChart/types';
import {
  InsightsChartContentWrapper,
  InsightsChartHeaderWrapper,
  InsightsChartWrapper,
  getSubWidget,
} from 'merchant/widgets/InsightsChart/utils';
import { ErrorState } from 'merchant/widgets/common/ErrorState';
import { DateRangeValues } from 'merchant/widgets/common/Select/types';
import { renderInput } from 'merchant/widgets/common/utils';
import { useRetryWidget } from 'merchant/widgets/hooks';

import { durationOptionKeys } from '../common/types';
import { ErrorBoundaryFallBackComponent, getUcsAliasFromQueryKey, track } from '../utils';

const InsightsChartWidget: React.FC<InsightsChartProps> = ({
  title,
  components,
  isLoading,
  error,
  id,
  queryKey,
  inputs,
  type,
}): JSX.Element | null => {
  const [isRetrying, retryHandler] = useRetryWidget(queryKey);
  const [date, setDate] = useState<DateRangeValues | ''>('');
  const splitz = useSplitzService();

  const isDatePickerEnabled = isDateRangeForInsightChartsEnabled(splitz?.abExperiments);
  const input = inputs.find((input) => input.type === 'select');
  const defaultValue = input?.default_value;

  const screen = getUcsAliasFromQueryKey(queryKey) ?? '';
  const widgetId = `merchantDashboard.${screen}.${type}.${id}`;

  useEffect(() => {
    if (!isLoading && !isRetrying) {
      const properties = {
        title,
        date,
        actionBy: widgetId,
        widgetId,
        ...(error ? { error: `${error.message}` } : { count: components.length }),
      };
      track({
        objectName: `widget`,
        actionName: error ? 'error' : 'loaded',
        screen,
        properties,
      });
    }
  }, [isLoading, isRetrying, error, date]);

  useEffect(() => {
    // if default_value is truthy and date is empty (initial mount scenario)
    if (defaultValue && date === '') {
      setDate(defaultValue);
    }
  }, [defaultValue, date]);

  const handleDateChange = (value: Array<DateRangeValues>, custom_range: [number, number]) => {
    setDate(value[0]);
    if (value[0] !== durationOptionKeys.CUSTOM) {
      retryHandler({ id, date_time: { quick: value[0] } });
    } else if (value[0] === durationOptionKeys.CUSTOM && custom_range) {
      const [fromDate, toDate] = custom_range;
      retryHandler({
        id,
        date_time: {
          custom: {
            from: moment(fromDate).startOf('day').unix(), // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
            to: moment(toDate).endOf('day').unix(), // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
          },
        },
      });
    }
  };

  if (components.length === 0 && !(isLoading || isRetrying)) return null;

  return (
    <InsightsChartWrapper title={title}>
      <InsightsChartHeaderWrapper>
        <Heading size="medium" marginRight="spacing.5">
          {title}
        </Heading>
        <Box display="flex" gap="12px" flexWrap="wrap" justifyContent="flex-end">
          {input &&
            renderInput({
              widget: input,
              value: date,
              onChange: handleDateChange,
              analyticsProperties: {
                screen,
                widgetId,
                actionBy: widgetId,
                title,
              },
              customRange: isDatePickerEnabled,
            })}
        </Box>
      </InsightsChartHeaderWrapper>
      {isLoading || isRetrying ? (
        <InsightsChartContentWrapper>
          {Array.from({ length: components.length ? components.length : 6 }, (_, k) => (
            <React.Fragment key={k}>
              {getSubWidget({
                widget: components.length
                  ? components[k]
                  : {
                      type: 'insight_item',
                    },
                isLoading: true,
              })}
            </React.Fragment>
          ))}
        </InsightsChartContentWrapper>
      ) : error ? (
        <ErrorState
          backgroundColor="surface.background.gray.intense"
          text={`${title} couldn't be loaded`}
          retryHandler={() => retryHandler({ id })}
          analyticsProperties={{
            screen,
            error: `${error.message}`,
            widgetId,
            actionBy: widgetId,
            title,
            date,
          }}
        />
      ) : (
        <InsightsChartContentWrapper>
          {components.map((componentData, index) => (
            <ErrorBoundary key={index} FallbackComponent={ErrorBoundaryFallBackComponent}>
              {getSubWidget({
                widget: componentData,
                isLoading,
                queryKey,
                props: { date },
                analyticsProperties: {
                  widgetId,
                },
              })}
            </ErrorBoundary>
          ))}
        </InsightsChartContentWrapper>
      )}
    </InsightsChartWrapper>
  );
};
export default InsightsChartWidget;
