import React, { createContext, useEffect, useState } from 'react';
import {
  MeridiemType,
  TimePickerPropsType,
  TimePickerContextType,
} from 'merchant_common/views/Reports/components/TimePicker/types';
import {
  getInitialTimeStates,
  handleMinutesChange,
} from 'merchant_common/views/Reports/components/TimePicker/utils';
import moment from 'moment';

export const TimePickerContext = createContext({} as TimePickerContextType);

export const TimePickerProvider = ({
  children,
  defaultValue,
  disableInput,
  minutesInterval,
  onChange,
}: Pick<TimePickerPropsType, 'defaultValue' | 'minutesInterval' | 'onChange' | 'disableInput'> & {
  children: JSX.Element | JSX.Element[];
}): JSX.Element => {
  const initialStates = getInitialTimeStates(defaultValue!, minutesInterval);

  // in h
  const [hour, setHour] = useState<number>(initialStates[0]);

  // in m
  const [minutes, setMinutes] = useState<number>(initialStates[1]);

  // in A
  const [meridiem, setMeridiem] = useState<MeridiemType>(initialStates[2]);

  // if input is disabled, picker will show up by default. to toggle use external state
  const [shouldShowPicker, setShowPicker] = useState(disableInput);

  const handleUpdates = ({ hour, minutes, meridiem }) => {
    onChange({
      // since moment object is just mutated, making sure state updates using clone
      date: moment(defaultValue)
        .clone()
        .set({
          hour: moment(`${hour} ${meridiem}`, 'h A').clone().get('hour'),
          minute: minutes,
          second: minutes === 59 ? 59 : 0,
        })
        .clone(),
      renderInfo: {
        hour,
        minutes,
        meridiem,
      },
    });
  };

  const hourChevUpClick = () => {
    const updatedHour = hour === 1 ? 12 : hour - 1;
    handleUpdates({ hour: updatedHour, minutes, meridiem });
    setHour(updatedHour);
  };

  const hourChevDownClick = () => {
    const updatedHour = hour === 12 ? 1 : hour + 1;
    handleUpdates({ hour: updatedHour, minutes, meridiem });
    setHour(updatedHour);
  };

  const minutesChevUpClick = () =>
    handleMinutesChange(
      'decrease',
      minutes,
      (updatedMins) => {
        handleUpdates({ hour, minutes: updatedMins, meridiem });
        setMinutes(updatedMins);
      },
      minutesInterval,
    );
  const minutesChevDownClick = () =>
    handleMinutesChange(
      'increase',
      minutes,
      (updatedMins) => {
        handleUpdates({ hour, minutes: updatedMins, meridiem });
        setMinutes(updatedMins);
      },
      minutesInterval,
    );

  const meridiemChevUpClick = () => {
    const updatedMeridiem = meridiem === 'AM' ? 'PM' : 'AM';
    handleUpdates({ hour, minutes, meridiem: updatedMeridiem });
    setMeridiem(updatedMeridiem);
  };

  const meridiemChevDownClick = () => {
    const updatedMeridiem = meridiem === 'AM' ? 'PM' : 'AM';
    handleUpdates({ hour, minutes, meridiem: updatedMeridiem });
    setMeridiem(updatedMeridiem);
  };

  useEffect(() => {
    onChange({
      // since moment object is just mutated, making sure state updates using clone
      date: moment(defaultValue)
        .clone()
        .set({
          hour: moment(`${hour} ${meridiem}`, 'h A').clone().get('hour'),
          minute: minutes,
          second: minutes === 59 ? 59 : 0,
        })
        .clone(),
      renderInfo: {
        hour,
        minutes,
        meridiem,
      },
    });
  }, []);

  const selectedTime = moment(
    `${hour}:${minutes}:${meridiem}:${minutes === 59 ? 59 : 0}`,
    `h:m:A:s`,
  ).clone();

  return (
    <TimePickerContext.Provider
      value={{
        hour,
        minutes,
        meridiem,
        shouldShowPicker: Boolean(shouldShowPicker),
        hourChevUpClick,
        hourChevDownClick,
        minutesChevUpClick,
        minutesChevDownClick,
        meridiemChevUpClick,
        meridiemChevDownClick,
        setShowPicker,
        selectedTime,
      }}
    >
      {children}
    </TimePickerContext.Provider>
  );
};
