import React, { useEffect, useRef, useState } from 'react';
import moment from 'moment';
import {
  PickerAbsWrapper,
  SelectedRangeInfoBadge,
  SelectedRangeInputField,
  TimePicker as TimePi,
  TimePickerContainer,
  TimePickerRow,
  TimePickerS,
  TimePickerWrapper,
} from './styled';
import { ClockIcon, Text } from 'merchant_common/views/Reports/components';
import { ScrollSafeMargin, FlexCentered } from 'merchant_common/views/Reports/components/styled';
import { TimeInfo } from './Components/TimeInfo';
import { MeridiemType, TimePickerCalPropsType, TimePickerPropsType } from './types';
import { useClickOutSide, useTheme } from 'merchant_common/views/Reports/hooks';
import { FieldFooter } from 'merchant_common/views/Reports/components/FieldFooter';
import { FieldLabel } from 'merchant_common/views/Reports/components/FieldLabel';
import { getInitialTimeStates, handleMinutesChange } from './utils';

export const TimePicker = ({
  value = moment(),
  onChange,
  disableInput = false,
  onClose = () => {},
  minutesInterval,
}: TimePickerCalPropsType): JSX.Element => {
  // in h
  const [hour, setHour] = useState<number>(getInitialTimeStates(value, minutesInterval)[0]);

  // in m
  const [minutes, setMinutes] = useState<number>(getInitialTimeStates(value, minutesInterval)[1]);

  // in A
  const [meridiem, setMeridiem] = useState<MeridiemType>(
    getInitialTimeStates(value, minutesInterval)[2],
  );

  const { theme } = useTheme();
  const timePickerRef = useRef<HTMLDivElement>(null);

  // if input is disabled, picker will show up by default. to toggle use external state
  const [shouldShowPicker, setShowPicker] = useState(disableInput);

  useEffect(() => {
    onChange({
      // since moment object is just mutated, making sure state updates using clone
      date: moment(value)
        .clone()
        .set({
          hour: moment(`${hour} ${meridiem}`, 'h A').get('hour'),
          minute: minutes,
        }),
      renderInfo: {
        hour,
        minutes,
        meridiem,
      },
    });
  }, [hour, meridiem, minutes]);

  useClickOutSide([timePickerRef], () => {
    onClose();
    if (!disableInput) setShowPicker(false);
  });

  const selectedTime = moment(`${hour}:${minutes}:${meridiem}`, `h:m:A`).format('h:mm A');

  return (
    <TimePickerContainer ref={timePickerRef}>
      {!disableInput ? (
        <SelectedRangeInfoBadge
          focused={shouldShowPicker}
          aria-label={`Selected Time Is ${selectedTime}`}
          onClick={() => setShowPicker(true)}
        >
          <Text type="normal" size="medium" weight="regular" variant="body">
            {selectedTime}
          </Text>
          <FlexCentered
            style={{
              marginLeft: 5,
            }}
          >
            <ClockIcon color="feedback.icon.neutral.lowContrast" size="medium" />
          </FlexCentered>
        </SelectedRangeInfoBadge>
      ) : null}
      {shouldShowPicker ? (
        <TimePickerWrapper aria-label="Time Picker Container" theme={theme}>
          <ScrollSafeMargin>
            <TimePi theme={theme}>
              <TimeInfo
                role="Hour"
                ariaLabel={hour.toString()}
                chevUpClick={() => setHour(hour === 1 ? 12 : hour - 1)}
                chevDownClick={() => setHour(hour === 12 ? 1 : hour + 1)}
              >
                {hour}
              </TimeInfo>

              <TimePickerRow>
                <Text variant="body" size="medium" weight="regular">
                  :
                </Text>
              </TimePickerRow>
              <TimeInfo
                role="Minute"
                ariaLabel={moment(`${minutes}`, 'm').format('mm')}
                chevUpClick={() =>
                  handleMinutesChange('decrease', minutes, setMinutes, minutesInterval)
                }
                chevDownClick={() =>
                  handleMinutesChange('increase', minutes, setMinutes, minutesInterval)
                }
              >
                {moment(`${minutes}`, 'm').format('mm')}
              </TimeInfo>
              <TimeInfo
                role="Meridiem"
                ariaLabel={meridiem}
                chevUpClick={() => setMeridiem(meridiem === 'AM' ? 'PM' : 'AM')}
                chevDownClick={() => setMeridiem(meridiem === 'AM' ? 'PM' : 'AM')}
              >
                {meridiem}
              </TimeInfo>
            </TimePi>
          </ScrollSafeMargin>
        </TimePickerWrapper>
      ) : null}
    </TimePickerContainer>
  );
};

export const TimePickerField = ({
  defaultValue = moment(),
  onChange,
  disableInput = false,
  onClose = () => {},
  label,
  helpText,
  validate = () => true,
  necessityIndicator,
  errorText,
  minutesInterval,
}: TimePickerPropsType): JSX.Element => {
  const isValidated = validate();
  // in h
  const [hour, setHour] = useState<number>(getInitialTimeStates(defaultValue, minutesInterval)[0]);

  // in m
  const [minutes, setMinutes] = useState<number>(
    getInitialTimeStates(defaultValue, minutesInterval)[1],
  );

  // in A
  const [meridiem, setMeridiem] = useState<MeridiemType>(
    getInitialTimeStates(defaultValue, minutesInterval)[2],
  );

  const { theme } = useTheme();
  const timePickerRef = useRef<HTMLDivElement>(null);

  // if input is disabled, picker will show up by default. to toggle use external state
  const [shouldShowPicker, setShowPicker] = useState(disableInput);

  useEffect(() => {
    onChange({
      // since moment object is just mutated, making sure state updates using clone
      date: moment(defaultValue).set({
        hour: moment(`${hour} ${meridiem}`, 'h A').get('hour'),
        minute: minutes,
      }),
      renderInfo: {
        hour,
        minutes,
        meridiem,
      },
    });
  }, [hour, meridiem, minutes]);

  useClickOutSide([timePickerRef], () => {
    onClose();
    if (!disableInput) setShowPicker(false);
  });

  const selectedTime = moment(`${hour}:${minutes}:${meridiem}`, `h:m:A`).format('h:mm A');

  return (
    <TimePickerContainer ref={timePickerRef}>
      <FieldLabel necessityIndicator={necessityIndicator} label={label} />

      {!disableInput ? (
        <SelectedRangeInputField
          theme={theme}
          aria-label={`Selected Time Is ${selectedTime}`}
          onClick={() => setShowPicker(true)}
          validation={isValidated}
          focused={shouldShowPicker}
        >
          <Text type="normal" size="medium" weight="regular" variant="body">
            {selectedTime}
          </Text>
          <FlexCentered
            style={{
              marginLeft: 5,
            }}
          >
            <ClockIcon color="feedback.icon.neutral.lowContrast" size="medium" />
          </FlexCentered>
        </SelectedRangeInputField>
      ) : null}
      {shouldShowPicker ? (
        <PickerAbsWrapper aria-label="Time Picker Container" theme={theme}>
          <ScrollSafeMargin>
            <TimePickerS theme={theme}>
              <TimeInfo
                role="Hour"
                ariaLabel={hour.toString()}
                chevUpClick={() => setHour(hour === 1 ? 12 : hour - 1)}
                chevDownClick={() => setHour(hour === 12 ? 1 : hour + 1)}
              >
                {hour}
              </TimeInfo>

              <TimePickerRow>
                <Text variant="body" size="medium" weight="regular">
                  :
                </Text>
              </TimePickerRow>
              <TimeInfo
                role="Minute"
                ariaLabel={moment(`${minutes}`, 'm').format('mm')}
                chevUpClick={() =>
                  handleMinutesChange('decrease', minutes, setMinutes, minutesInterval)
                }
                chevDownClick={() =>
                  handleMinutesChange('increase', minutes, setMinutes, minutesInterval)
                }
              >
                {moment(`${minutes}`, 'm').format('mm')}
              </TimeInfo>
              <TimeInfo
                role="Meridiem"
                ariaLabel={meridiem}
                chevUpClick={() => setMeridiem(meridiem === 'AM' ? 'PM' : 'AM')}
                chevDownClick={() => setMeridiem(meridiem === 'AM' ? 'PM' : 'AM')}
              >
                {meridiem}
              </TimeInfo>
            </TimePickerS>
          </ScrollSafeMargin>
        </PickerAbsWrapper>
      ) : null}
      <FieldFooter errorText={errorText} validation={isValidated} helpText={helpText} />
    </TimePickerContainer>
  );
};
