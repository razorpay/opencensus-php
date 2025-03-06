import React, { useMemo } from 'react';
import {
  Box,
  Text,
  Dropdown,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  Heading,
  DatePicker,
  SelectInput,
  Checkbox,
  ClockIcon,
  Divider,
} from '@razorpay/blade/components';
import moment from 'moment';
import { Controller, useFormContext, useWatch } from 'react-hook-form';
import { getAvailableStartTimes, getAvailableEndTimes } from './utils';
import CampaignLimits from './CampaignLimits';

const CampaignSettings = ({ viewOnly }) => {
  const { control, setValue, resetField } = useFormContext();

  const [
    watchedStartDate,
    watchedEndDate,
    watchedStartTime,
    watchedStartImmediately,
    watchedNoEndDate,
  ] = useWatch({
    control,
    name: ['startDate', 'endDate', 'startTime', 'startImmediately', 'noEndDate'],
  });

  const START_TIMES = useMemo(() => getAvailableStartTimes(watchedStartDate), []);

  const END_TIMES = useMemo(() => {
    const isSameDay =
      moment(watchedStartDate).isSame(watchedEndDate, 'day') || watchedStartImmediately;
    return getAvailableEndTimes(watchedStartTime, isSameDay);
  }, [watchedStartDate, watchedEndDate, watchedStartTime]);

  return (
    <Box display="flex" flex={1} flexDirection="column">
      <Heading
        size="small"
        weight="semibold"
        color="surface.text.gray.normal"
        marginBottom="spacing.6"
      >
        Campaign Settings
      </Heading>
      <Text
        variant="body"
        size="medium"
        weight="semibold"
        color="surface.text.gray.subtle"
        marginBottom="spacing.4"
      >
        Schedule
      </Text>
      <Box
        display="flex"
        flexDirection="column"
        padding="spacing.4"
        borderRadius="large"
        borderColor="surface.border.gray.muted"
      >
        <Box display="flex" alignItems="flex-start" paddingBottom="spacing.4">
          <Box display="flex" flex={1}>
            <Controller
              name="startDate"
              control={control}
              render={({ field, fieldState }) => (
                <DatePicker
                  {...field}
                  defaultValue={undefined}
                  onMonthSelect={undefined}
                  onYearSelect={undefined}
                  label="Starts From"
                  isDisabled={watchedStartImmediately || viewOnly}
                  onApply={(value) => {
                    setValue(field.name, value, { shouldValidate: true });
                    resetField('endDate');
                    resetField('endTime');
                  }}
                  minDate={new Date()}
                  errorText={fieldState.error?.message}
                  validationState={fieldState.error ? 'error' : 'none'}
                />
              )}
            />
          </Box>
          <Box display="flex" flex={1} flexDirection="column" marginX="spacing.7">
            <Controller
              name="startTime"
              control={control}
              render={({ field, fieldState }) => (
                <Dropdown selectionType="single">
                  <SelectInput
                    {...field}
                    label="At"
                    placeholder="Select Time"
                    isDisabled={watchedStartImmediately || viewOnly}
                    onChange={({ values }) => {
                      setValue(field.name, values[0], { shouldValidate: true });
                      resetField('endTime');
                    }}
                    icon={ClockIcon}
                    errorText={fieldState.error?.message}
                    validationState={fieldState.error ? 'error' : 'none'}
                  />
                  <DropdownOverlay>
                    <ActionList>
                      {START_TIMES.map((time) => (
                        <ActionListItem key={time.value} value={time.value} title={time.label} />
                      ))}
                    </ActionList>
                  </DropdownOverlay>
                </Dropdown>
              )}
            />
          </Box>
          <Box display="flex" flex={1} flexDirection="column" height="100%" justifyContent="center">
            <Controller
              name="startImmediately"
              control={control}
              render={({ field }) => (
                <Checkbox
                  {...field}
                  isChecked={field.value}
                  onChange={({ isChecked }) => {
                    field.onChange(isChecked);
                    resetField('startDate');
                    resetField('startTime');
                    resetField('endDate');
                    resetField('endTime');
                  }}
                  isDisabled={viewOnly}
                >
                  Start Immediately
                </Checkbox>
              )}
            />
          </Box>
        </Box>
        <Box display="flex" alignItems="flex-start" paddingBottom="spacing.4">
          <Box display="flex" flex={1}>
            <Controller
              name="endDate"
              control={control}
              render={({ field, fieldState }) => (
                <DatePicker
                  {...field}
                  label="Ends On"
                  isDisabled={watchedNoEndDate || viewOnly}
                  onApply={(value) => {
                    setValue(field.name, value, { shouldValidate: true });
                  }}
                  defaultValue={undefined}
                  onMonthSelect={undefined}
                  onYearSelect={undefined}
                  minDate={watchedStartDate ? watchedStartDate : new Date()}
                  errorText={fieldState.error?.message}
                  validationState={fieldState.error ? 'error' : 'none'}
                />
              )}
            />
          </Box>
          <Box display="flex" flex={1} flexDirection="column" marginX="spacing.7">
            <Controller
              name="endTime"
              control={control}
              render={({ field, fieldState }) => (
                <Dropdown selectionType="single">
                  <SelectInput
                    {...field}
                    label="At"
                    isDisabled={watchedNoEndDate || viewOnly}
                    placeholder="Select Time"
                    onChange={({ values }) => {
                      setValue(field.name, values[0], { shouldValidate: true });
                    }}
                    icon={ClockIcon}
                    errorText={fieldState.error?.message}
                    validationState={fieldState.error ? 'error' : 'none'}
                  />
                  <DropdownOverlay>
                    <ActionList>
                      {END_TIMES.map((time) => (
                        <ActionListItem key={time.value} value={time.value} title={time.label} />
                      ))}
                    </ActionList>
                  </DropdownOverlay>
                </Dropdown>
              )}
            />
          </Box>
          <Box display="flex" flex={1} flexDirection="column" height="100%" justifyContent="center">
            <Controller
              name="noEndDate"
              control={control}
              render={({ field }) => (
                <Checkbox
                  {...field}
                  isChecked={field.value}
                  onChange={({ isChecked }) => {
                    field.onChange(isChecked);
                    resetField('endDate');
                    resetField('endTime');
                  }}
                  isDisabled={viewOnly}
                >
                  No End Date
                </Checkbox>
              )}
            />
          </Box>
        </Box>
      </Box>
      <CampaignLimits viewOnly={viewOnly} />
    </Box>
  );
};

export default CampaignSettings;
