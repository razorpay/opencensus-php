import React from 'react';
import Datetime from 'react-datetime';

function DateTimePicker(props) {
  const { value, placeholder, onChange, isOutsideRange, dateTimeProps, name } = props;

  const handleOnDateChange = (date) => onChange({ date, name });

  return (
    <div data-testid={`sr-dashboard-custom-date-input-${name}`}>
      <Datetime
        {...dateTimeProps}
        className="datetime-picker__input"
        value={value}
        onChange={handleOnDateChange}
        dateFormat="DD-MM-YYYY |"
        timeFormat="h A"
        viewMode="days"
        isValidDate={isOutsideRange}
        disableFutureDates={true}
        inputProps={{ placeholder, readOnly: true }}
      />
    </div>
  );
}

export default DateTimePicker;
