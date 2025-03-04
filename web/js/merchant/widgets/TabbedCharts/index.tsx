import React, { useEffect, useState } from 'react';
import { Box, Heading } from '@razorpay/blade/components';
import moment from 'moment';

import { useSplitzService } from 'common/splitz';
import { isDateRangeForInsightChartsEnabled } from 'merchant/containers/Home/RTUX/utils';
import { ErrorState } from 'merchant/widgets/common/ErrorState';
import { DateRangeValues } from 'merchant/widgets/common/Select/types';
import { renderInput } from 'merchant/widgets/common/utils';
import { useRetryWidget } from 'merchant/widgets/hooks';
import { getUcsAliasFromQueryKey, track } from 'merchant/widgets/utils';

import LoadingSkeleton from './LoadingSkeleton';
import Tab from './Tab';
import TabsWrapper from './TabsWrapper';
import { TabbedChartsProps } from './types';
import { durationOptionKeys } from '../common/types';

export const TabbedCharts: React.FC<TabbedChartsProps> = ({
  isLoading = false,
  error,
  queryKey,
  id,
  title,
  components,
  inputs,
  type,
  variables,
}) => {
  const [isRetrying, retryHandler] = useRetryWidget(queryKey);
  const splitz = useSplitzService();

  const isDatePickerEnabled = isDateRangeForInsightChartsEnabled(splitz?.abExperiments);
  const input = inputs.find((input) => input.type === 'select');
  const defaultValue =
    variables?.date_time?.quick ||
    ((variables?.date_time?.custom
      ? durationOptionKeys.CUSTOM
      : input?.default_value) as DateRangeValues);

  const [date, setDate] = useState<DateRangeValues | ''>(defaultValue);

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

  return (
    <Box
      key={`widget-${id}`}
      testID={`widget-${title}`}
      minWidth="spacing.0"
      marginX={{ base: 'spacing.0', m: 'spacing.6' }}
      flexGrow="1"
      display="flex"
      flexDirection="column"
      gap="spacing.5"
      padding={['spacing.6', 'spacing.0', 'spacing.6', 'spacing.0']}
      backgroundColor="surface.background.gray.intense"
      borderRadius="large"
      elevation="lowRaised"
    >
      <Box
        display="flex"
        gap="spacing.5"
        flexDirection="row"
        justifyContent="space-between"
        margin={['spacing.0', 'spacing.5']}
        alignItems="center"
      >
        <Heading size="medium">{title}</Heading>
        <Box display="flex" gap="12px" flexWrap="wrap" justifyContent="flex-end">
          {input &&
            renderInput({
              widget: input,
              value: date,
              variables,
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
