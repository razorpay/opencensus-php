import React, { lazy, useRef, useState } from 'react';
import {
  Box,
  SelectInput,
  Dropdown as BladeDropdown,
  DropdownOverlay,
  ActionList,
  ActionListSection,
  ActionListItem,
} from '@razorpay/blade/components';
import moment from 'moment';

import { useMobile } from 'common/hooks/useMobile';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import {
  CHART_OPTIONS_MAPPING,
  CHART_ORDER,
} from 'merchant/views/RiskAndFraud/RiskAnalytics/ChartContainer/constants';
import {
  RISK_DECLINED,
  ENTITY_PRESETS,
  METRIC_OPTIONS,
  CUSTOM,
  MOBILE_BREAKPOINTS,
  MOBILE_CALENDAR_NUMBER_OF_MONTHS,
  DESKTOP_CALENDAR_NUMBER_OF_MONTHS,
} from 'merchant/views/RiskAndFraud/RiskAnalytics/constants';
import { StyledDateRangePicker } from 'merchant/views/RiskAndFraud/components/styled';

const DateRangePicker = lazy(() =>
  import(/* webpackChunkName: 'DateRangePicker' */ 'common/ui/Forms/DateRangePickerField'),
);

const EntityFilters = (props) => {
  const {
    entity,
    dateRange,
    metric,
    graphOptions,
    isLoading,
    handleDurationChange,
    handleMetricChange,
    handleGraphOptions,
  } = props;
  const isMobile = useMobile();
  const isMediumDesktopAndMobile = useMobile(MOBILE_BREAKPOINTS);
  const defaultFocusedInput = useRef(null);
  const [shouldShowDateRangePicker, setShowDateRangePicker] = useState(false);

  const presetOptions = ENTITY_PRESETS[entity];
  const metricOptions = METRIC_OPTIONS;
  const chartOptions = CHART_OPTIONS_MAPPING[entity][metric] ?? [];
  const numberOfMonths = isMediumDesktopAndMobile
    ? MOBILE_CALENDAR_NUMBER_OF_MONTHS
    : DESKTOP_CALENDAR_NUMBER_OF_MONTHS;

  const onDurationChange = ({ values }) => {
    const selectedOption = presetOptions.find((preset) => preset.value === values[0]);
    const { value, duration, unit } = selectedOption;
    const currentDate = moment().subtract(1, 'day');
    const endDate = moment(currentDate).startOf('day');
    const startDate = endDate.clone().subtract(duration, unit).startOf('day');

    if (value === CUSTOM) {
      defaultFocusedInput.current = 'startDate';
      setShowDateRangePicker(true);
      handleDurationChange({
        ...dateRange,
        preset: selectedOption,
      });
    } else {
      setShowDateRangePicker(false);
      handleDurationChange({
        startDate: startDate.unix(),
        endDate: endDate.unix(),
        preset: selectedOption,
      });
    }
  };

  const onDatesChange = ({ from, to }) => {
    const { startDate, endDate, preset } = dateRange;
    if (from === startDate && to === endDate) return;
    const startUnix = moment.unix(from).startOf('day').unix();
    const endUnix = moment.unix(to).startOf('day').unix();
    handleDurationChange({
      startDate: startUnix,
      endDate: endUnix,
      preset,
    });
  };

  const onMetricChange = ({ values }) => handleMetricChange(values[0]);

  const onGraphOptionsChange = ({ values }) => {
    // Sort the selected values based on their index in CHART_ORDER
    const sortedValues = values
      .slice()
      .sort((a, b) => CHART_ORDER.indexOf(a) - CHART_ORDER.indexOf(b));

    handleGraphOptions(sortedValues);
  };

  // Function to check if the date is before 2 years
  const isOutsideRange = (day) => {
    const currentDate = moment().subtract(1, 'day');
    const twoYearsAgo = moment(currentDate).subtract(2, 'years').startOf('day');
    return day.isBefore(twoYearsAgo) || day.isAfter(currentDate);
  };

  const { startDate, endDate, preset } = dateRange;

  return (
    <Box
      display="flex"
      flexDirection="row"
      paddingTop="spacing.5"
      paddingBottom="spacing.7"
      borderBottomColor="surface.border.gray.muted"
    >
      <StyledDateRangePicker>
        <Box minWidth="180px">
          <BladeDropdown selectionType="single">
            <SelectInput
              accessibilityLabel="Duration"
              name="Duration"
              placeholder="Select Duration"
              value={preset.value}
              onChange={onDurationChange}
            />
            <DropdownOverlay>
              <ActionList>
                <ActionListSection title="Duration">
                  {presetOptions.map(({ label, value }) => (
                    <ActionListItem key={value} title={label} value={value} />
                  ))}
                </ActionListSection>
              </ActionList>
            </DropdownOverlay>
          </BladeDropdown>
        </Box>
        {shouldShowDateRangePicker && startDate && endDate ? (
          <div className="risk-daterange-picker">
            <div className="daterange-container">
              <SuspenseWithLoader>
                <DateRangePicker
                  onDatesChange={onDatesChange}
                  startDate={moment.unix(startDate)}
                  endDate={moment.unix(endDate)}
                  disabled={isLoading}
                  numberOfMonths={numberOfMonths}
                  withPortal={isMobile}
                  defaultFocusedInput={defaultFocusedInput.current}
                  isOutsideRange={isOutsideRange}
                />
              </SuspenseWithLoader>
            </div>
          </div>
        ) : null}
      </StyledDateRangePicker>

      {/* Select Metric */}
      <Box marginRight="spacing.7" minWidth="235px">
        <BladeDropdown selectionType="single">
          <SelectInput
            accessibilityLabel="Metric"
            name="Metric"
            placeholder="Select Metric"
            value={metric}
            onChange={onMetricChange}
          />
          <DropdownOverlay>
            <ActionList>
              <ActionListSection title="In terms of">
                {metricOptions.map(({ label, value }) => (
                  <ActionListItem key={value} title={label} value={value} />
                ))}
              </ActionListSection>
            </ActionList>
          </DropdownOverlay>
        </BladeDropdown>
      </Box>

      {/* Select Graph Options */}
      {entity !== RISK_DECLINED && (
        <Box marginRight="spacing.7" minWidth="235px">
          <BladeDropdown selectionType="multiple">
            <SelectInput
              accessibilityLabel="Graph Options"
              name="Graph Options"
              placeholder="Select Chart Type"
              defaultValue={graphOptions}
              value={graphOptions}
              onChange={onGraphOptionsChange}
            />
            <DropdownOverlay>
              <ActionList>
                <ActionListSection title="Select options to display on the graph">
                  {chartOptions.map(({ label, value }) => (
                    <ActionListItem key={value} title={label} value={value} />
                  ))}
                </ActionListSection>
              </ActionList>
            </DropdownOverlay>
          </BladeDropdown>
        </Box>
      )}
    </Box>
  );
};

export default EntityFilters;
