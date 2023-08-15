import React from 'react';
import moment from 'moment';
import { TimePickerContainer } from './styled';
import { TimePickerCalPropsType, TimePickerPropsType } from './types';
import { FieldFooter } from 'merchant_common/views/Reports/components/FieldFooter';
import { FieldLabel } from 'merchant_common/views/Reports/components/FieldLabel';
import { TimePickerProvider } from './context/TimePickerProvider';
import { DTRPTimeInput } from './Components/DTRPTimeInput';
import { BaseTimeInput } from './Components/BaseTimeInput';

export const TimePicker = ({
  value = moment().clone(),
  onChange,
  disableInput = false,
  minutesInterval,
}: TimePickerCalPropsType): JSX.Element => {
  return (
    <TimePickerContainer>
      <TimePickerProvider
        onChange={onChange}
        defaultValue={value}
        disableInput={disableInput}
        minutesInterval={minutesInterval}
      >
        <DTRPTimeInput disableInput={disableInput} />
      </TimePickerProvider>
    </TimePickerContainer>
  );
};

export const TimePickerField = ({
  defaultValue = moment().clone(),
  onChange,
  label,
  helpText,
  validate = () => true,
  necessityIndicator,
  errorText,
  minutesInterval,
}: Omit<TimePickerPropsType, 'onClose' | 'disableInput'>): JSX.Element => {
  const isValidated = validate();
  return (
    <TimePickerContainer>
      <TimePickerProvider
        onChange={onChange}
        defaultValue={defaultValue}
        disableInput={false}
        minutesInterval={minutesInterval}
      >
        <FieldLabel necessityIndicator={necessityIndicator} label={label} />
        <BaseTimeInput isValidated={isValidated} />
        <FieldFooter errorText={errorText} validation={isValidated} helpText={helpText} />
      </TimePickerProvider>
    </TimePickerContainer>
  );
};
