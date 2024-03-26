import React, { useEffect, useState } from 'react';
import { Heading } from '@razorpay/blade/components';

import { ErrorState } from 'merchant/widgets/common/ErrorState';
import { useRetryWidget } from 'merchant/widgets/hooks';
import InsightsChartWidgetLoader from 'merchant/widgets/InsightsChart/Loader';
import { InsightsChartProps } from 'merchant/widgets/InsightsChart/types';
import { renderInput } from 'merchant/widgets/common/utils';
import {
  InsightsChartContentWrapper,
  InsightsChartHeaderWrapper,
  InsightsChartWrapper,
  getSubWidget,
} from 'merchant/widgets/InsightsChart/utils';
import { DateRangeValues } from 'merchant/widgets/common/Select/types';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
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

  const handleDateChange = (value: Array<DateRangeValues>) => {
    setDate(value[0]);
    // TODO: add support for date range fetch
    retryHandler({ id, date_time: { quick: value[0] } });
  };

  if (isLoading || isRetrying)
    return <InsightsChartWidgetLoader title={title} components={components} inputs={inputs} />;

  if (components.length === 0) return null;

  return (
    <InsightsChartWrapper>
      <InsightsChartHeaderWrapper>
        <Heading size="large">{title}</Heading>
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
          })}
      </InsightsChartHeaderWrapper>

      {error ? (
        <ErrorState
          backgroundColor="surface.background.level2.lowContrast"
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
