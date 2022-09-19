import React from 'react';
import Datetime from 'react-datetime';

function DateTimePicker(props) {
  const {
    value = null,
    placeholder = 'Select Date',
    onChange = () => {},
    isOutsideRange = () => true,
    dateTimeProps = {},
    name = '',
  } = props;

  return (
    <Datetime
      {...dateTimeProps}
      className="datetime-picker__input"
      value={value}
      onChange={(date) => onChange({ date, name })}
      dateFormat="DD-MM-YYYY |"
      timeFormat="h A"
      viewMode="days"
      isValidDate={isOutsideRange}
      disableFutureDates={true}
      inputProps={{ placeholder, readOnly: true }}
    />
  );
}

export default DateTimePicker;
