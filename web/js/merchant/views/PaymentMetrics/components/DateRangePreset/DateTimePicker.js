import React from 'react';
import Datetime from 'react-datetime';
import { DateTimeContainer } from 'merchant/views/PaymentMetrics/components/styled';
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
    <DateTimeContainer>
      <Datetime
        {...dateTimeProps}
        value={value}
        onChange={(date) => onChange({ date, name })}
        dateFormat="DD-MM-YYYY |"
        timeFormat="h A"
        viewMode="days"
        isValidDate={isOutsideRange}
        disableFutureDates={true}
        inputProps={{ placeholder, readOnly: true }}
      />
    </DateTimeContainer>
  );
}

export default DateTimePicker;
