import React, { useCallback, useState, useEffect, useRef } from 'react';
import {
  Box,
  Heading,
  Text,
  Dropdown,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  SelectInput,
} from '@razorpay/blade/components';
import moment from 'moment';
import { DateRangePicker } from 'merchant/views/Insights/DateRangePicker';
import { insightsDateRangePresets } from 'merchant/views/Insights/constants';
import { getInitialDateRange } from 'merchant/views/Insights/utils/dateUtils';

interface DateRange {
  from: number;
  to: number;
}

interface HeaderSectionProps {
  insightsDashboard: string;
  trackAnalytics: (eventName: string, properties?: Record<string, any>) => void;
  selectedDateCallback: (dateRange: DateRange) => void;
}

const HeaderSection: React.FC<HeaderSectionProps> = ({
  insightsDashboard,
  trackAnalytics,
  selectedDateCallback,
}) => {
  const [selectedPreset, setSelectedPreset] = useState<string>('0');
  const [selectedDates, setSelectedDates] = useState<DateRange>(getInitialDateRange);
  const [isLoading, setIsLoading] = useState(false);

  const previousDatesRef = useRef<DateRange | null>(null);

  const dashboardDescription =
    insightsDashboard === 'Success rate'
      ? 'Monitor payment success across methods'
      : 'Track key metrics for your checkout performance';

  useEffect(() => {
    if (
      previousDatesRef.current?.from === selectedDates.from &&
      previousDatesRef.current?.to === selectedDates.to
    ) {
      return;
    }

    const updateDateRange = async () => {
      try {
        setIsLoading(true);
        await selectedDateCallback(selectedDates);
        previousDatesRef.current = { ...selectedDates };
      } catch (error) {
        trackAnalytics('Refresh Button Error', {
          error: error instanceof Error ? error.message : 'Unknown error',
        });
      } finally {
        setIsLoading(false);
      }
    };

    updateDateRange();
  }, [selectedDates, selectedDateCallback]);

  const handleDropdownDateRangeChange = useCallback(
    ({ values }: { values: string[] }) => {
      const presetIndex = Number(values[0]);
      const preset = insightsDateRangePresets[presetIndex];

      setSelectedPreset(values[0]);
      const [from, to] = preset.value();
      const dateRange = {
        from: moment(from).unix(), // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
        to: moment(to).unix(), // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
      };
      setSelectedDates(dateRange);

      trackAnalytics('Date Range Changed via dropdown', {
        range: preset.label,
        dateRange: {
          from: dateRange.from,
          to: dateRange.to,
        },
      });
    },
    [trackAnalytics],
  );

  const handleDatePickerChange = useCallback(
    (dateRange: DateRange) => {
      setSelectedDates(dateRange);
      setSelectedPreset('0');

      trackAnalytics('Date Picker Changed', {
        dateRange: {
          from: dateRange.from,
          to: dateRange.to,
        },
      });
    },
    [trackAnalytics],
  );

  return (
    <Box
      display="flex"
      flexWrap="wrap"
      paddingY="spacing.7"
      justifyContent="space-between"
      alignItems="center"
      gap="spacing.3"
    >
      <Box>
        <Heading
          color="surface.text.gray.normal"
          display="flex"
          textAlign="left"
          size="2xlarge"
          weight="semibold"
        >
          {insightsDashboard}
        </Heading>
        <Text color="surface.text.gray.subtle" size="large">
          {dashboardDescription}
        </Text>
      </Box>
      <Box display="flex" alignItems="center" justifyContent="flex-start" gap="spacing.4">
        <Box display="flex" alignItems="center" justifyContent="flex-start">
          <DateRangePicker
            selectedDateCallback={handleDatePickerChange}
            selectedDates={selectedDates}
            isLoading={isLoading}
          />
        </Box>
        <Box>
          <Dropdown selectionType="single">
            <SelectInput
              label=""
              placeholder="Select Date Range"
              name="dateRange"
              value={selectedPreset}
              onChange={handleDropdownDateRangeChange}
              isDisabled={isLoading}
            />
            <DropdownOverlay>
              <ActionList>
                {insightsDateRangePresets.map((preset, index) => (
                  <ActionListItem key={index} title={preset.label} value={index.toString()} />
                ))}
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
        </Box>
      </Box>
    </Box>
  );
};

export default HeaderSection;
