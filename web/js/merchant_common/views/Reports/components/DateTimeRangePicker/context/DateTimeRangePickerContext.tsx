import React, { createContext, useContext, useEffect, useState } from 'react';
import moment from 'moment';
import { DEFAULT_DATES } from 'merchant_common/views/Reports/components/DateTimeRangePicker/constants';
import { DateTimeRangePickerContextType } from 'merchant_common/views/Reports/components/DateTimeRangePicker/types';

export const DateTimeRangePickerContext = createContext<DateTimeRangePickerContextType>({
  startDate: null,
  endDate: null,
  validationError: '',
  setValidationError: () => {},
  setStartDate: () => {},
  setEndDate: () => {},
});

// using diff export, otherwise will have to import useContext and
// DateTimeRangePickerContext in every file (Default Boilerplate)
export const useDateTimeRangeContext = () => useContext(DateTimeRangePickerContext);

export const DateTimeRangePickerProvider = ({ children, value, onChange }): JSX.Element | null => {
  const [startDate, setStartDate] = useState<null | moment.Moment>(null);
  const [endDate, setEndDate] = useState<null | moment.Moment>(null);
  const [isLoading, setLoading] = useState<boolean>(true);
  const [validationError, setValidationError] = useState('');

  useEffect(() => {
    if (value && value.startDate && value.endDate) {
      setStartDate(value.startDate);
      setEndDate(value.endDate);
    } else {
      setStartDate(DEFAULT_DATES[0]);
      setEndDate(DEFAULT_DATES[1]);
      onChange({ startDate: DEFAULT_DATES[0], endDate: DEFAULT_DATES[1] });
    }
    setLoading(false);
  }, []);

  const isLoaded = moment.isMoment(startDate) && moment.isMoment(endDate) && !isLoading && value;

  return isLoaded ? (
    <DateTimeRangePickerContext.Provider
      value={{
        startDate,
        endDate,
        validationError,
        setValidationError,
        setStartDate,
        setEndDate,
      }}
    >
      {children}
    </DateTimeRangePickerContext.Provider>
  ) : null;
};
