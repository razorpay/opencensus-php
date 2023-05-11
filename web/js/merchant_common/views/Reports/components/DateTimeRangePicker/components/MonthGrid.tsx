import React, { useMemo } from 'react';
import moment from 'moment';
import { CalendarHeading } from './';
import {
  MONTHS_RANGE_FULL,
  MONTH_RANGE_COMPACT,
} from 'merchant_common/views/Reports/components/DateTimeRangePicker/constants';
import { MonthGridPropsType } from 'merchant_common/views/Reports/components/DateTimeRangePicker/types';
import { Table, Td } from 'merchant_common/views/Reports/components/DateTimeRangePicker/styled';
import { Text } from 'merchant_common/views/Reports/components';
import { useTheme } from 'merchant_common/views/Reports/hooks';
import { chunk } from 'lodash';

export const MonthGrid = ({
  customCalendarHeading,
  daySize,
  disableFuture = true,
  handleVisibleRange = () => {},
  refDayMoment,
  setViewMode = () => {},
  isCompactView,
  disablePast = false,
}: MonthGridPropsType): JSX.Element => {
  const { theme } = useTheme();

  const checkIfMonthIsAllowed = (m) => {
    if (!refDayMoment) return false;
    if (disableFuture) {
      return refDayMoment
        .clone()
        .set({
          month: m,
        })
        .isSameOrBefore(moment(), 'month');
    } else if (disablePast) {
      return refDayMoment
        .clone()
        .set({
          month: m,
        })
        .isSameOrAfter(moment(), 'month');
    } else {
      return true;
    }
  };

  const onMonthSelect = (monthRange) => {
    if (typeof monthRange === 'number') {
      const isAllowed = checkIfMonthIsAllowed(monthRange);
      if (isAllowed) {
        handleVisibleRange([{ month: monthRange, year: refDayMoment.get('year') }]);
        setViewMode(`date`);
      }
    } else {
      const isAllowed = checkIfMonthIsAllowed(monthRange[0]);
      if (isAllowed) {
        handleVisibleRange([
          { month: monthRange[0], year: refDayMoment.get('year') },
          { month: monthRange[1], year: refDayMoment.get('year') },
        ]);
        setViewMode(`date`);
      }
    }
  };

  const monthRangeArr = useMemo(() => chunk(MONTHS_RANGE_FULL, 4), []);
  const monthArr = useMemo(() => chunk(MONTH_RANGE_COMPACT, 4), []);

  const renderGrid = ({ data, renderedMonthElement, isDisabled }) => {
    const baseTableSize = daySize * (isCompactView ? 7 : 14);

    return (
      <Table size={baseTableSize + 20}>
        <thead />
        <tbody>
          {data.map((tds, vIndex) => (
            <tr key={vIndex}>
              {tds.map((monthRange, index) => (
                <Td
                  key={index}
                  onClick={() => onMonthSelect(monthRange)}
                  theme={theme}
                  disabled={isDisabled(monthRange)}
                  aria-label={`Months Range -> ${renderedMonthElement(monthRange)}`}
                  style={{
                    height: daySize * 2,
                    width: baseTableSize / 3,
                    borderRadius: 8,
                  }}
                >
                  <Text
                    size="medium"
                    weight="regular"
                    type="normal"
                    variant="body"
                    color={
                      isDisabled(monthRange)
                        ? 'surface.text.muted.lowContrast'
                        : 'surface.text.normal.lowContrast'
                    }
                  >
                    {renderedMonthElement(monthRange)}
                  </Text>
                </Td>
              ))}
            </tr>
          ))}
        </tbody>
      </Table>
    );
  };

  const renderMonths = () => {
    if (isCompactView) {
      return renderGrid({
        data: monthArr,
        renderedMonthElement: (monthRange) => moment().month(monthRange).format('MMM'),
        isDisabled: (monthRange) => !checkIfMonthIsAllowed(monthRange),
      });
    } else {
      return renderGrid({
        data: monthRangeArr,
        renderedMonthElement: (monthRange) =>
          `${moment().month(monthRange[0]).format('MMM')} - ${moment()
            .month(monthRange[1])
            .format('MMM')}`,
        isDisabled: (monthRange) => !checkIfMonthIsAllowed(monthRange[0]),
      });
    }
  };

  return (
    <div>
      <CalendarHeading
        customCalendarHeading={customCalendarHeading}
        onClick={() => setViewMode(`year`)}
      >
        {Boolean(refDayMoment) ? refDayMoment.format('YYYY') : ''}
      </CalendarHeading>
      {renderMonths()}
    </div>
  );
};
