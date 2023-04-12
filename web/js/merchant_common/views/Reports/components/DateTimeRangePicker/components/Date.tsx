import React from 'react';
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
  allowSingleDateSelection,
  disablePast,
  maxDate,
  minDate,
}: DatePropsType): JSX.Element => {
  const { theme } = useTheme();
  const { startDate, endDate, setStartDate, setEndDate } = useDateTimeRangeContext();
  const { dateHoveringOn, focused, setDateHoveringOn, setFocused } = useDatesContext();

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

  const isDayAfterMaxDate = Boolean(maxDate) && day.isAfter(maxDate, 'month');
  const isDayBeforeMinDate = Boolean(minDate) && day.isBefore(minDate, 'month');

  const isDisabled = isDisabledFutureOrPast || isDayAfterMaxDate || isDayBeforeMinDate;
  const isBetween = day.isBetween(startDate, endDate, 'day', '()');
  const isStartDate = day.isSame(startDate, 'day');
  const isEndDate = day.isSame(endDate, 'day');
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

    if (
      allowSingleDateSelection
        ? day.isBefore(startDate, 'day')
        : day.isSameOrBefore(startDate, 'day')
    ) {
      setStartDate(updateMomentWithCurrTime(day, startDate));
      setFocused('endDate');
    } else if (day.isBetween(startDate, endDate, 'day', allowSingleDateSelection ? '[]' : '()')) {
      if (allowSingleDateSelection && day.isSame(startDate, 'day')) {
        // handles state when user clicks on start date
        // single day will be selected
        setEndDate(updateMomentWithCurrTime(day, endDate));
        setFocused('startDate');
      } else if (allowSingleDateSelection && day.isSame(endDate, 'day')) {
        // handles state whhen user clicks on end date
        // single day will be selected
        setStartDate(updateMomentWithCurrTime(day, startDate));
        setFocused('endDate');
      } else if (focused === 'startDate') {
        setStartDate(updateMomentWithCurrTime(day, startDate));
        setFocused('endDate');
      } else {
        setEndDate(updateMomentWithCurrTime(day, endDate));
        setFocused('startDate');
      }
    } else {
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

  const isInTrackOfHighlight =
    startDate && endDate && moment.isMoment(dateHoveringOn)
      ? focused === 'startDate'
        ? day.isBetween(dateHoveringOn.clone(), startDate.clone(), 'day', '[)')
        : day.isBetween(endDate.clone(), dateHoveringOn.clone(), 'day', '(]')
      : false;

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
      isHovered: isInTrackOfHighlight,
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
