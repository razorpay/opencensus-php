import React, { useEffect, useState } from 'react';
import { Box, Heading } from '@razorpay/blade/components';

import { ErrorState } from 'merchant/widgets/common/ErrorState';
import { DateRangeValues } from 'merchant/widgets/common/Select/types';
import { renderInput } from 'merchant/widgets/common/utils';
import { useRetryWidget } from 'merchant/widgets/hooks';

import LoadingSkeleton from './LoadingSkeleton';
import Tab from './Tab';
import TabsWrapper from './TabsWrapper';
import { TabbedChartsProps } from './types';
import { getUcsAliasFromQueryKey, track } from 'merchant/widgets/utils';

export const TabbedCharts: React.FC<TabbedChartsProps> = ({
  isLoading = false,
  error,
  queryKey,
  id,
  title,
  components,
  inputs,
  type,
}) => {
  const [isRetrying, retryHandler] = useRetryWidget(queryKey);
  const [date, setDate] = useState<DateRangeValues | ''>('');

  const input = inputs.find((input) => input.type === 'select');
  const defaultValue = input?.default_value;

  useEffect(() => {
    // if default_value is truthy and date is empty (initial mount scenario)
    if (defaultValue && date === '') {
      setDate(defaultValue);
    }
  }, [defaultValue, date]);

  const screen = getUcsAliasFromQueryKey(queryKey) ?? '';
  const widgetId = `merchantDashboard.${screen}.${type}.${id}`;

  useEffect(() => {
    if (!isLoading && !isRetrying) {
      const properties = {
        title,
        widgetId,
        actionBy: widgetId,
        date,
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

  const handleDateChange = (value: Array<DateRangeValues>) => {
    setDate(value[0]);
    retryHandler({ id, date_time: { quick: value[0] } });
  };

  return (
    <Box
      key={`widget-${id}`}
      display="flex"
      flexDirection="column"
      gap="spacing.5"
      padding={['spacing.6', 'spacing.0', 'spacing.6', 'spacing.0']}
      marginX={{ base: 'spacing.0', m: 'spacing.6' }}
      backgroundColor="surface.background.level2.lowContrast"
      borderRadius="large"
    >
      <Box
        display="flex"
        flexDirection="row"
        justifyContent="space-between"
        margin={['spacing.0', 'spacing.5']}
        alignItems="center"
      >
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
      </Box>
      {isLoading || isRetrying ? (
        <LoadingSkeleton count={components.length} />
      ) : error ? (
        <ErrorState
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
        <TabsWrapper
          analyticsProperties={{
            screen,
            widgetId,
            date,
          }}
        >
          {components.map((componentData) => (
            <Tab
              key={componentData.id}
              id={componentData.id}
              tabData={componentData}
              date={date as DateRangeValues}
              analyticsProperties={{
                screen,
                widgetId,
              }}
            />
          ))}
        </TabsWrapper>
      )}
    </Box>
  );
};
