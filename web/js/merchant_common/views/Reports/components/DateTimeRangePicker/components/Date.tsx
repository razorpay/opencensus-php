import React, { useCallback } from 'react';
import moment from 'moment';
import { Date as StyledDate } from 'merchant_common/views/Reports/components/DateTimeRangePicker/styled';
import { DatePropsType } from 'merchant_common/views/Reports/components/DateTimeRangePicker/types';
import { Text } from 'merchant_common/views/Reports/components';
import { useDateTimeRangeContext } from 'merchant_common/views/Reports/components/DateTimeRangePicker/context/DateTimeRangePickerContext';
import { useDatesContext } from 'merchant_common/views/Reports/components/DateTimeRangePicker/context/DatesContext';
import { useTheme } from 'merchant_common/views/Reports/hooks';

export const Date = ({
  customDateComponent,
  day,
  daySize,
  disableFuture,
  onDayClick = () => {},
  showToday,
  disablePast,
  maxDate,
  minDate,
  allowSingleDateSelection,
}: DatePropsType): JSX.Element => {
  const { theme } = useTheme();
  const { startDate, endDate, setStartDate, setEndDate } = useDateTimeRangeContext();
  const { dateHoveringOn, focused, setDateHoveringOn, setFocused } = useDatesContext();

  const checkIfHovered = useCallback(() => {
    if (!day) return false;

    switch (true) {
      case startDate && endDate && moment?.isMoment(dateHoveringOn):
        if (focused === 'startDate') {
          return day.isBetween(dateHoveringOn, startDate, 'day', '[)');
        } else {
          return day.isBetween(endDate, dateHoveringOn, 'day', '(]');
        }

      case !startDate && !endDate:
        return dateHoveringOn?.isSame(day, 'day');

      case !endDate:
        return day.isBetween(startDate, dateHoveringOn, 'day', '(]');

      default:
        return false;
    }
  }, [dateHoveringOn, day, endDate, focused, startDate]);

  if (!day) {
    return customDateComponent ? (
      customDateComponent({
        day: null,
        daySize,
        disabled: true,
        inBetween: false,
        isStartDate: false,
        isEndDate: false,
        isExtremeEnds: false,
        onClick: () => {},
        isHovered: false,
        isSingleDaySelected: false,
      })
    ) : (
      <StyledDate size={daySize} theme={theme} />
    );
  }

  const isDisabledFutureOrPast = disableFuture
    ? day.isAfter(moment(), 'day')
    : disablePast
    ? day.isBefore(moment(), 'day')
    : false;

  const isDayAfterMaxDate = Boolean(maxDate) && day.isAfter(maxDate, 'day');
  const isDayBeforeMinDate = Boolean(minDate) && day.isBefore(minDate, 'day');

  const isDisabled = isDisabledFutureOrPast || isDayAfterMaxDate || isDayBeforeMinDate;
  const isBetween = Boolean(startDate && day.isBetween(startDate, endDate, 'day', '()'));
  const isStartDate = Boolean(startDate && day.isSame(startDate, 'day'));
  const isEndDate = Boolean(endDate && day.isSame(endDate, 'day'));
  const isExtremeEnds = isStartDate || isEndDate;
  const isSingleDaySelected = isStartDate && isEndDate;
  const isToday = day.isSame(moment(), 'day');

  const updateMomentWithCurrTime = (newDate, currDate) => {
    const updatedDate = moment(newDate).set({
      minute: currDate.get('minute'),
      hour: currDate.get('hour'),
    });
    return updatedDate;
  };

  const onClick = (event) => {
    if (event) event?.preventDefault();
    if (isDisabled) return;

    switch (true) {
      case !startDate && !endDate:
        if (allowSingleDateSelection) {
          setStartDate(day.clone().startOf('day'));
          setEndDate(day.clone().endOf('day'));
        } else {
          setStartDate(day.clone().startOf('day'));
          setFocused('endDate');
        }
        break;
      case allowSingleDateSelection
        ? day.isBefore(startDate, 'day')
        : day.isSameOrBefore(startDate, 'day'):
        setStartDate(updateMomentWithCurrTime(day, startDate));
        setFocused('endDate');
        break;
      case startDate && !endDate:
        setEndDate(day.clone().endOf('day'));
        setFocused('startDate');
        break;
      case day.isBetween(startDate, endDate, 'day', allowSingleDateSelection ? '[]' : '()'):
        if (allowSingleDateSelection && day.isSame(startDate, 'day')) {
          setEndDate(updateMomentWithCurrTime(day, endDate));
          setFocused('startDate');
        } else if (allowSingleDateSelection && day.isSame(endDate, 'day')) {
          setStartDate(updateMomentWithCurrTime(day, startDate));
          setFocused('endDate');
        } else if (focused === 'startDate') {
          setStartDate(updateMomentWithCurrTime(day, startDate));
          setFocused('endDate');
        } else {
          setEndDate(updateMomentWithCurrTime(day, endDate));
          setFocused('startDate');
        }
        break;
      default:
        setEndDate(updateMomentWithCurrTime(day, endDate));
        setFocused('startDate');
    }

    onDayClick(day);
  };

  const handleHoveringState = () => {
    if (
      !dateHoveringOn ||
      (moment.isMoment(dateHoveringOn) && !moment(dateHoveringOn).isSame(day, 'day'))
    ) {
      setDateHoveringOn(day);
    }
  };

  // handle focused state
  const handleFocusedRef = (mouseEvent) => {
    if (mouseEvent) mouseEvent.preventDefault();
    if (isDisabled) return;

    if (day.isBefore(startDate, 'day')) {
      if (focused === 'endDate') setFocused('startDate');
      handleHoveringState();
    } else if (day.isBetween(startDate, endDate, 'day', '[]')) {
      // empty
      // in TODO
    } else {
      if (focused === 'startDate') setFocused('endDate');
      handleHoveringState();
    }
  };

  const isInTrackOfHighlight = checkIfHovered();

  return customDateComponent ? (
    customDateComponent({
      day,
      daySize,
      disabled: isDisabled,
      inBetween: isBetween,
      isStartDate,
      isEndDate,
      isExtremeEnds,
      isSingleDaySelected,
      onClick,
      isHovered: Boolean(isInTrackOfHighlight),
    })
  ) : (
    <StyledDate
      size={daySize}
      theme={theme}
      inBetween={isBetween}
      isFocused={isExtremeEnds}
      isStartDate={isStartDate}
      isEndDate={isEndDate}
      isHovered={isInTrackOfHighlight}
      isSingleDaySelected={isSingleDaySelected}
      disabled={isDisabled}
      onClick={onClick}
      onMouseEnter={handleFocusedRef}
      onMouseLeave={() => setDateHoveringOn(null)}
      aria-label={`Date is ${day.clone().format('DD MMMM YYYY')}`}
    >
      <Text
        size="medium"
        weight="regular"
        type="normal"
        variant="body"
        color={
          isExtremeEnds
            ? 'surface.text.subtle.highContrast'
            : isDisabled
            ? 'surface.text.muted.lowContrast'
            : isToday && showToday
            ? 'action.text.secondary.focus'
            : 'surface.text.normal.lowContrast'
        }
      >
        {day.format('D')}
      </Text>
    </StyledDate>
  );
};
