/* eslint-disable @typescript-eslint/no-unused-vars */
import React, { Dispatch, useState } from 'react';

import { Button, Box, CalendarIcon } from '@razorpay/blade/components';

type DatePickerPropType = {
  currentDate: Date;
  setCurrentDate: Dispatch<Date>;
};

const DatePicker: React.FC<DatePickerPropType> = ({ currentDate, setCurrentDate }) => {
  const [isCalendarShown, setIsCalendarShown] = useState(false);

  const onButtonClick = () => {
    setIsCalendarShown((oldState) => !oldState);
  };

  return (
    <Box>
      <Button variant="tertiary" icon={CalendarIcon} onClick={onButtonClick}>
        {currentDate.toDateString()}
      </Button>
    </Box>
  );
};

export default DatePicker;
