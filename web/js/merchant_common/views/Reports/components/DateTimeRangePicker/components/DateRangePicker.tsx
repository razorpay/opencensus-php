import React, { useEffect, useState } from 'react';
import moment from 'moment';
import {
  DateRangePickerPropsType,
  ViewMode,
  VisibleRangeType,
} from 'merchant_common/views/Reports/components/DateTimeRangePicker/types';
import { DayGrid, MonthGrid, YearGrid } from './';
import { useMediaQuery } from 'merchant_common/views/Reports/hooks/useMediaQuery';
import {
  DateGridWrapper,
  DateRangeContainer,
} from 'merchant_common/views/Reports/components/DateTimeRangePicker/styled';
import {
  MAX_YEAR_WINDOW_INDEX_INCLUSIVE,
  MIN_YEAR_WINDOW_INDEX_INCLUSIVE,
} from 'merchant_common/views/Reports/components/DateTimeRangePicker/constants';
import { PickerNavigation } from './PickerNavigation';
import { useTheme } from 'merchant_common/views/Reports/hooks';

export const DateRangePicker = ({
  allowSingleDateSelection,
  daySize,
  disableFuture,
  disablePast,
  maxDate,
  minDate,
  showToday,
  initialVisibleRange,
}: DateRangePickerPropsType): JSX.Element => {
  const isCompactView = useMediaQuery(`(max-width: 550px)`).breakpointMatched;
  const [visibleYearsRangeIndex, setVisibleYearsRangeIndex] = useState<number>(0);
  const [viewMode, setViewMode] = useState<keyof typeof ViewMode>('date');
  const [calendarIndex, setCalendarIndex] = useState<0 | 1>(0);
  const { theme } = useTheme();
  const [visibleRange, setVisibleRange] = useState<VisibleRangeType>(
    initialVisibleRange ?? (isCompactView ? [moment()] : [moment(), moment().add(1, 'month')]),
  );

  const onNextClick = () => {
    if (viewMode === 'year') {
      setVisibleYearsRangeIndex(visibleYearsRangeIndex + 1);
    } else if (visibleRange) {
      if (isCompactView) {
        const [refMoment] = visibleRange;
        const newRefMoment = refMoment.clone().add(1, 'month');
        setVisibleRange([newRefMoment]);
      } else {
        const currRef2 = visibleRange[1];
        if (currRef2) {
          const newCurrRef2 = currRef2.clone().add(1, 'month');
          setVisibleRange([currRef2, newCurrRef2]);
        }
      }
    }
  };

  const onPrevClick = () => {
    if (viewMode === 'year') {
      setVisibleYearsRangeIndex(visibleYearsRangeIndex - 1);
    } else if (visibleRange) {
      if (isCompactView) {
        const [refMoment] = visibleRange;
        const newRefMoment = refMoment.clone().subtract(1, 'month');
        setVisibleRange([newRefMoment]);
      } else {
        /* eslint-disable no-eval */
        const currRef1 = visibleRange[0];
        const newCurrRef1 = currRef1.clone().subtract(1, 'month');
        setVisibleRange([newCurrRef1, currRef1]);
      }
    }
  };

  const handleVisibleRange = (value, index) => {
    if (!isCompactView && visibleRange) {
      const [currRef1, currRef2] = visibleRange;
      if (currRef2) {
        if (typeof value === 'object') {
          const updatedRefVisibleCalMoment1 = currRef1.clone().set(value[0]);
          const updatedRefVisibleCalMoment2 = currRef2.clone().set(value[1]);
          setVisibleRange([updatedRefVisibleCalMoment1, updatedRefVisibleCalMoment2]);
        } else {
          const updatedRefVisibleCalMoment = (index === 1 ? currRef2 : currRef1).clone().set(value);
          if (index === 1) {
            setVisibleRange([currRef1, updatedRefVisibleCalMoment]);
          } else {
            setVisibleRange([updatedRefVisibleCalMoment, currRef2]);
          }
        }
      }
    } else if (visibleRange) {
      const updatedVR = visibleRange[0].clone().set(value[0]);
      setVisibleRange([updatedVR]);
    }
  };

  const renderComponent = (viewMode: keyof typeof ViewMode) => {
    if (!visibleRange) return null;

    switch (viewMode) {
      case 'date':
        return visibleRange.map((refDayMoment, index) => (
          <DateGridWrapper key={index}>
            <DayGrid
              refDayMoment={refDayMoment}
              daySize={daySize}
              showToday={showToday}
              disableFuture={disableFuture}
              disablePast={disablePast}
              allowSingleDateSelection={allowSingleDateSelection}
              maxDate={maxDate}
              minDate={minDate}
              onCalendarHeadingClick={() => {
                if (index === 0 || index === 1) {
                  if (!isCompactView) setCalendarIndex(index);
                  setViewMode(`month`);
                }
              }}
            />
          </DateGridWrapper>
        ));

      case 'month':
        return (
          <DateGridWrapper>
            <MonthGrid
              daySize={daySize}
              refDayMoment={visibleRange[isCompactView ? 0 : calendarIndex]!}
              handleVisibleRange={handleVisibleRange}
              setViewMode={setViewMode}
              disableFuture={disableFuture}
              disablePast={disablePast}
              isCompactView={isCompactView}
            />
          </DateGridWrapper>
        );
      case 'year':
        return (
          <DateGridWrapper>
            <YearGrid
              refDayMoment={visibleRange[isCompactView ? 0 : calendarIndex]!}
              handleVisibleRange={handleVisibleRange}
              setViewMode={setViewMode}
              visibleYearsRangeIndex={visibleYearsRangeIndex}
              setVisibleYearsRangeIndex={setVisibleYearsRangeIndex}
              daySize={daySize}
              disableFuture={disableFuture}
              disablePast={disablePast}
              isCompactView={isCompactView}
            />
          </DateGridWrapper>
        );
      default:
        return null;
    }
  };

  useEffect(() => {
    if (visibleRange) {
      const range = isCompactView
        ? [visibleRange[0]]
        : [visibleRange[0], visibleRange[0].clone().add(1, 'month')];
      setVisibleRange(range);
    } else {
      const range = isCompactView ? [moment()] : [moment(), moment().add(1, 'month')];
      setVisibleRange(range);
    }
  }, [isCompactView]);

  const isPrevClickAllowed =
    viewMode === 'year' ? visibleYearsRangeIndex >= MIN_YEAR_WINDOW_INDEX_INCLUSIVE : true;

  const isNextClickAllowed =
    viewMode === 'year'
      ? disableFuture
        ? visibleYearsRangeIndex < 0
        : visibleYearsRangeIndex <= MAX_YEAR_WINDOW_INDEX_INCLUSIVE
      : true;

  return (
    <div style={{ position: 'relative', margin: 10 }}>
      <PickerNavigation
        isNextClickAllowed={isNextClickAllowed}
        isPrevClickAllowed={isPrevClickAllowed}
        onNextClick={onNextClick}
        onPrevClick={onPrevClick}
        viewMode={viewMode}
      />
      <DateRangeContainer theme={theme} aria-label="Main Calendar Range">
        {visibleRange && renderComponent(viewMode)}
      </DateRangeContainer>
    </div>
  );
};
