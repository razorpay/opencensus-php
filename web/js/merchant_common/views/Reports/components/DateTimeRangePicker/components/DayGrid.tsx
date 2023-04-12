import React, { useMemo } from 'react';
import { CalendarHeading, Date, Day } from './';
import {
  DayGridPropsType,
  WeekIndex,
} from 'merchant_common/views/Reports/components/DateTimeRangePicker/types';
import { Table } from 'merchant_common/views/Reports/components/DateTimeRangePicker/styled';
import { WEEKDAYS } from 'merchant_common/views/Reports/components/DateTimeRangePicker/constants';
import { getDayGridData } from 'merchant_common/views/Reports/components/DateTimeRangePicker/utils';

export const DayGrid = ({
  daySize = 36,
  disableFuture,
  onCalendarHeadingClick = () => {},
  onDayClick = () => {},
  refDayMoment,
  showToday,
  allowSingleDateSelection,
  disablePast,
  maxDate,
  minDate,
}: DayGridPropsType): JSX.Element => {
  const weeks = useMemo(() => getDayGridData(refDayMoment), [refDayMoment]);

  return (
    <div>
      <CalendarHeading onClick={onCalendarHeadingClick}>
        {refDayMoment.format('MMMM, YYYY')}
      </CalendarHeading>
      <Table size={daySize * 7}>
        <thead>
          <tr>
            {WEEKDAYS.map((weekIndex) => (
              <Day key={weekIndex} daySize={daySize} weekIndex={weekIndex as WeekIndex} />
            ))}
          </tr>
        </thead>
        <tbody>
          {weeks.map((week, weekIndex) => {
            return (
              <tr key={weekIndex}>
                {week.map((day, key) => {
                  return (
                    <Date
                      refDayMoment={refDayMoment}
                      key={key}
                      day={day}
                      daySize={daySize}
                      disableFuture={disableFuture}
                      disablePast={disablePast}
                      onDayClick={onDayClick}
                      showToday={showToday}
                      allowSingleDateSelection={allowSingleDateSelection}
                      maxDate={maxDate}
                      minDate={minDate}
                    />
                  );
                })}
              </tr>
            );
          })}
        </tbody>
      </Table>
    </div>
  );
};
