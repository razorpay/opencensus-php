import React from 'react';
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

import {
  CHART_OPTIONS_MAPPING,
  CHART_ORDER,
} from 'merchant/views/RiskAndFraud/RiskAnalytics/ChartContainer/constants';
import {
  RISK_DECLINED,
  ENTITY_PRESETS,
  METRIC_OPTIONS,
} from 'merchant/views/RiskAndFraud/RiskAnalytics/constants';

const EntityFilters = (props) => {
  const {
    entity,
    dateRange,
    metric,
    graphOptions,
    handleDurationChange,
    handleMetricChange,
    handleGraphOptions,
  } = props;
  const presetOptions = ENTITY_PRESETS[entity];
  const metricOptions = METRIC_OPTIONS;
  const chartOptions = CHART_OPTIONS_MAPPING[entity][metric] ?? [];

  const onDurationChange = ({ values }) => {
    const selectedOption = presetOptions.find((preset) => preset.value === values[0]);
    const { duration, unit } = selectedOption;
    const currentDate = moment().subtract(1, 'day');
    const endDate = moment(currentDate).startOf('day');
    const startDate = endDate.clone().subtract(duration, unit).startOf('day');
    if (handleDurationChange) {
      handleDurationChange({
        startDate: startDate.unix(),
        endDate: endDate.unix(),
        preset: selectedOption,
      });
    }
  };

  const onMetricChange = ({ values }) => handleMetricChange(values[0]);

  const onGraphOptionsChange = ({ values }) => {
    // Sort the selected values based on their index in CHART_ORDER
    const sortedValues = values
      .slice()
      .sort((a, b) => CHART_ORDER.indexOf(a) - CHART_ORDER.indexOf(b));
    handleGraphOptions(sortedValues);
  };

  const { preset } = dateRange;

  return (
    <Box
      display="flex"
      flexDirection="row"
      paddingTop="spacing.5"
      paddingBottom="spacing.7"
      borderBottomColor="surface.border.gray.muted"
    >
      <Box marginRight="spacing.7" minWidth="180px">
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
