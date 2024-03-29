import React, { useMemo } from 'react';
import { CalendarHeading } from './CalendarHeading';
import { Table, Td } from 'merchant_common/views/Reports/components/DateTimeRangePicker/styled';
import { YearGridPropsType } from 'merchant_common/views/Reports/components/DateTimeRangePicker/types';
import { getYearsArray } from 'merchant_common/views/Reports/components/DateTimeRangePicker/utils';
import { useTheme } from 'merchant_common/views/Reports/hooks';
import { Text } from 'merchant_common/views/Reports/components';
import {
  YEAR_COLUMNS_COUNT_COMPACT,
  YEAR_COLUMNS_COUNT_FULL,
  YEAR_ROWS_COUNT_COMPACT,
  YEAR_ROWS_COUNT_FULL,
} from 'merchant_common/views/Reports/components/DateTimeRangePicker/constants';
import { chunk } from 'lodash';
import moment from 'moment';

export const YearGrid = ({
  customCalendarHeading,
  daySize,
  handleVisibleRange = () => {},
  setViewMode = () => {},
  setVisibleYearsRangeIndex = () => {},
  isCompactView,
  visibleYearsRangeIndex,
  disableFuture,
  disablePast,
}: YearGridPropsType): JSX.Element => {
  const { theme } = useTheme();

  const numOfVisibleYearFull = YEAR_COLUMNS_COUNT_FULL * YEAR_ROWS_COUNT_FULL;
  const numOfVisibleYearCompact = YEAR_COLUMNS_COUNT_COMPACT * YEAR_ROWS_COUNT_COMPACT;
  const yearsInVisibleRange = useMemo(
    () =>
      chunk(
        getYearsArray(
          visibleYearsRangeIndex,
          isCompactView ? numOfVisibleYearCompact : numOfVisibleYearFull,
        ),
        isCompactView ? YEAR_ROWS_COUNT_COMPACT : YEAR_ROWS_COUNT_FULL,
      ),
    [visibleYearsRangeIndex, isCompactView],
  );

  const onYearSelect = (year) => {
    if (isCompactView) {
      handleVisibleRange([{ year }]);
    } else {
      handleVisibleRange([{ year }, { year }]);
    }
    setViewMode(`month`);
    setVisibleYearsRangeIndex(0);
  };

  return (
    <div>
      <CalendarHeading disabled={true} customCalendarHeading={customCalendarHeading}>
        Select Year
      </CalendarHeading>
      <Table size={daySize * (isCompactView ? 7 : 14) + 20}>
        <thead />
        <tbody>
          {yearsInVisibleRange.map((tds, i) => (
            <tr key={i}>
              {tds.map((year) => {
                const isYearInFutureDisabled = disableFuture
                  ? moment({ year }).isAfter(moment(), 'year')
                  : false;
                const isYearInPastDisabled = disablePast
                  ? moment({ year }).isBefore(moment(), 'year')
                  : false;
                const isDisabled = isYearInFutureDisabled || isYearInPastDisabled;
                return (
                  <Td
                    disabled={isDisabled}
                    key={year}
                    theme={theme}
                    onClick={isDisabled ? () => {} : () => onYearSelect(year)}
                    aria-label={`Select ${year}`}
                    style={{
                      height: daySize * 2,
                      width: (daySize * 7) / 4,
                      borderRadius: 8,
                    }}
                  >
                    <Text
                      size="medium"
                      weight="regular"
                      variant="body"
                      color={isDisabled ? 'surface.text.gray.muted' : 'surface.text.gray.normal'}
                    >
                      {year}
                    </Text>
                  </Td>
                );
              })}
            </tr>
          ))}
        </tbody>
      </Table>
    </div>
  );
};
