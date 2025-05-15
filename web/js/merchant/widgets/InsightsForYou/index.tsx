import { useMobile } from '@libs/shared-utils';
import { Box, Button, FilterIcon, Heading } from '@razorpay/blade/components';
import moment from 'moment';
import React, { useState } from 'react';

import { useSplitzService } from 'common/splitz';
import {
  isDateRangeForInsightChartsEnabled,
} from 'merchant/containers/Home/RTUX/utils';
import { ErrorState } from 'merchant/widgets/common/ErrorState';
import { DateRangeValues } from 'merchant/widgets/common/Select/types';
import { durationOptionKeys } from 'merchant/widgets/common/types';
import { renderInput } from 'merchant/widgets/common/utils';
import { useRetryWidget } from 'merchant/widgets/hooks';
import { widgetKeyToComponentMapping } from 'merchant/widgets/mapping';
import { CommonWidgetProps, DateTime } from 'merchant/widgets/types';
import { getUcsAliasFromQueryKey } from 'merchant/widgets/utils';
import { isOmniChannelMerchant } from 'merchant/utils/omniUtils';
import { getUser } from '@federated/apps/shell/commonStore';
import AllFiltersModal from './AllFiltersModal';
import { FiltersWidget } from './FiltersWidget';
import { InsightsForYouWidgetLoader } from './Loader';
import { InsightsForYouWidgetProps } from './types';

export const InsightsForYou = (props: InsightsForYouWidgetProps & CommonWidgetProps) => {
  const {
    isLoading = false,
    error,
    queryKey,
    id,
    title,
    type,
    inputs,
    variables,
    components,
  } = props;
  const [isRetrying, retryHandler] = useRetryWidget(queryKey);
  const splitz = useSplitzService();
  const isMobile = useMobile();
  const user = getUser();

  const isDatePickerEnabled = isDateRangeForInsightChartsEnabled(splitz?.abExperiments);
  const isOmniMerchant = isOmniChannelMerchant(user);
  const input = inputs.find((input) => input.type === 'select');
  const allFilters = inputs.find((input) => input.type === 'all_filters_button');
  const defaultValue =
    variables?.date_time?.quick ||
    ((variables?.date_time?.custom
      ? durationOptionKeys.CUSTOM
      : input && 'default_value' in input
        ? input.default_value
        : '') as DateRangeValues);

  const [date, setDate] = useState<DateRangeValues | ''>(defaultValue);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [storeId, setStoreId] = useState<string[]>([]);
  const [sourceChannel, setSourceChannel] = useState<string>('');
  const [filters, setFilters] = useState<{
    date_time: DateTime;
    store_ids: string[];
    payment_source: string;
  }>({
    date_time: { quick: defaultValue },
    store_ids: [],
    payment_source: '',
  });

  const screen = getUcsAliasFromQueryKey(queryKey) ?? '';
  const widgetId = `merchantDashboard.${screen}.${type}.${id}`;

  const handleDateChange = (value: Array<DateRangeValues>, custom_range: [number, number]) => {
    setDate(value[0]);
    if (value[0] !== durationOptionKeys.CUSTOM) {
      setFilters({ ...filters, date_time: { quick: value[0] } });
      retryHandler({ id, ...(isOmniMerchant && filters), date_time: { quick: value[0] } });
    } else if (value[0] === durationOptionKeys.CUSTOM && custom_range) {
      const [fromDate, toDate] = custom_range;
      const dateTime = {
        custom: {
          from: moment(fromDate).startOf('day').unix(), // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
          to: moment(toDate).endOf('day').unix(), // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
        },
      };
      setFilters({ ...filters, date_time: dateTime });
      retryHandler({
        id,
        ...(isOmniMerchant && filters),
        date_time: dateTime,
      });
    }
  };

  const handleFilterApply = ({ stores, source }: { stores: string[]; source: string }) => {
    setStoreId(stores);
    setSourceChannel(source);
    setFilters({ ...filters, store_ids: stores, payment_source: source });
    const { date_time } = filters;
    const requestPayload = {
      date_time,
      ...(stores.length > 0 && { store_ids: stores }),
      ...(source && { payment_source: source }),
    };
    retryHandler({ id, ...requestPayload });
  };

  return (
    <Box
      key={`widget-${id}`}
      testID={`widget-${title}`}
      minWidth="spacing.0"
      marginY={'spacing.4'}
      flexGrow="1"
      display="flex"
      flexDirection="column"
      gap="spacing.5"
      borderRadius="large"
    >
      <Box>
        <Box
          display="flex"
          gap="spacing.5"
          flexDirection="row"
          justifyContent="space-between"
          alignItems="center"
          marginX={{ base: 'spacing.4', m: 'spacing.6' }}
        >
          <Heading size="medium">{title}</Heading>
          <Box display="flex" gap="12px" flexWrap="wrap" justifyContent="flex-end">
            {input &&
              !(isMobile && isOmniMerchant) &&
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
            {allFilters?.type === 'all_filters_button' && isOmniMerchant && (
              <Button
                variant="tertiary"
                icon={FilterIcon}
                iconPosition="left"
                onClick={() => setIsModalOpen(true)}
              >
                {allFilters?.values[0]}
              </Button>
            )}
          </Box>
        </Box>
        {isLoading || isRetrying ? (
          <InsightsForYouWidgetLoader />
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
          <Box>
            <Box display="flex" gap="16px" flexWrap="wrap" marginTop="spacing.4" marginX={{ base: 'spacing.4', m: 'spacing.6' }}>
              <FiltersWidget
                component={components}
                sourceChannel={sourceChannel}
                storeId={storeId}
              />
            </Box>
          </Box>
        )}
      </Box>
      <Box marginX={{ base: 'spacing.0', m: 'spacing.6' }}>
        {/* Maps through child components and renders their widgets, skipping hierarchy and payment source filter components since it will be rendered in the parent component */}
        {components.map((childComponent) => {
          if (
            childComponent.type === 'hierarchy_level' ||
            childComponent.type === 'payment_source_filter'
          ) {
            return null;
          } else {
            return childComponent?.components?.map((widget) => {
              const widgetComponent = widgetKeyToComponentMapping[widget.type];
              return widgetComponent ? (
                <React.Fragment key={widget.id}>
                  {widgetComponent({ ...props, ...widget, isLoading })}
                </React.Fragment>
              ) : null;
            });
          }
        })}
      </Box>
      {allFilters?.type === 'all_filters_button' && !isLoading ? (
        <AllFiltersModal
          isOpen={isModalOpen}
          onClose={() => setIsModalOpen(false)}
          input={input}
          date={date}
          variables={variables}
          screen={screen}
          widgetId={widgetId}
          title={title}
          isMobile={isMobile}
          isDatePickerEnabled={isDatePickerEnabled}
          paymentSource={sourceChannel}
          storeIds={storeId}
          renderInput={renderInput}
          componentWidget={components}
          onFilterApply={handleFilterApply}
          modalTitle={allFilters?.values[0]}
          handleDateChange={handleDateChange}
        />
      ) : null}
    </Box>
  );
};
