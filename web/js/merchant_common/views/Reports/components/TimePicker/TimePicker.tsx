import React, { useEffect, useRef, useState } from 'react';
import moment from 'moment';
import {
  SelectedRangeInfoBadge,
  TimePicker as TimePi,
  TimePickerContainer,
  TimePickerRow,
  TimePickerWrapper,
} from './styled';
import { ChevronDownIcon, Text } from 'merchant_common/views/Reports/components';
import { ScrollSafeMargin, FlexCentered } from 'merchant_common/views/Reports/components/styled';
import { TimeInfo } from './Components/TimeInfo';
import { TimePickerPropsType } from './types';
import { useClickOutSide, useTheme } from 'merchant_common/views/Reports/hooks';

const getInitialStates = (date) => {
  return date.format('h:m:A').split(':');
};

export const TimePicker = ({
  date = moment(),
  onChange,
  disableInput = false,
  onClose = () => {},
}: TimePickerPropsType): JSX.Element => {
  // in h
  const [hour, setHour] = useState<number>(+getInitialStates(date)[0]);

  // in m
  const [minutes, setMinutes] = useState<number>(+getInitialStates(date)[1]);

  // in A
  const [meridiem, setMeridiem] = useState<'AM' | 'PM'>(getInitialStates(date)[2]);

  const { theme } = useTheme();
  const timePickerRef = useRef<HTMLDivElement>(null);

  // if input is disabled, picker will show up by default. to toggle use external state
  const [shouldShowPicker, setShowPicker] = useState(disableInput);

  useEffect(() => {
    onChange({
      // since moment object is just mutated, making sure state updates using clone
      date: moment(date).set({
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
      {!disableInput && (
        <SelectedRangeInfoBadge
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
            <ChevronDownIcon color="feedback.icon.neutral.lowContrast" size="medium" />
          </FlexCentered>
        </SelectedRangeInfoBadge>
      )}
      {shouldShowPicker && (
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
                chevUpClick={() => setMinutes(minutes === 0 ? 59 : minutes - 1)}
                chevDownClick={() => setMinutes(minutes === 59 ? 0 : minutes + 1)}
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
      )}
    </TimePickerContainer>
  );
};
