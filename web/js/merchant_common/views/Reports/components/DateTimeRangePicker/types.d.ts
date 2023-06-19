import * as moment from 'moment';
import { ReactChild } from 'react';
import { NecessityIndicatorType } from 'merchant_common/views/Reports/components/types';

export enum ViewMode {
  date = 'date',
  month = 'month',
  year = 'year',
}

export type WeekIndex = 0 | 1 | 2 | 3 | 4 | 5 | 6;

interface BaseDateType {
  day: moment.Moment | null;
  daySize: number;
}

interface BaseDateRangePickerChildType {
  daySize: number;
  refDayMoment: moment.Moment;
  disableFuture?: boolean;
}

export interface CalendarHeadingPropsType {
  children: string;
  disabled?: boolean;
  onClick?: () => void;
  customCalendarHeading?: (x: string) => JSX.Element;
}

export interface CustomDateComponentPropsType extends BaseDateType {
  disabled: boolean;
  inBetween: boolean;
  isStartDate: boolean;
  isEndDate: boolean;
  isExtremeEnds: boolean;
  onClick: (x: MouseEvent) => void;
  isHovered: boolean;
  isSingleDaySelected: boolean;
}

export interface DatePropsType extends BaseDateType {
  customDateComponent?: (x: CustomDateComponentPropsType) => JSX.Element;
  onDayClick: (x: null | moment.Moment) => void;
  disableFuture?: boolean;
  showToday?: boolean;
  disablePast?: boolean;
  allowSingleDateSelection?: boolean;
  refDayMoment: moment.Moment;
  maxDate?: moment.Moment;
  minDate?: moment.Moment;
}

export interface DatesContextType {
  dateHoveringOn: null | moment.Moment;
  focused: string;
  setFocused: React.Dispatch<React.SetStateAction<string>>;
  setDateHoveringOn: React.Dispatch<React.SetStateAction<null | moment.Moment>>;
}

export interface DateTimeRangePickerContextType {
  startDate: null | moment.Moment;
  endDate: null | moment.Moment;
  validationError: string;
  setValidationError: React.Dispatch<React.SetStateAction<string>>;
  setStartDate: React.Dispatch<React.SetStateAction<null | moment.Moment>>;
  setEndDate: React.Dispatch<React.SetStateAction<null | moment.Moment>>;
}

export interface DateRangePickerPropsType {
  daySize: number;
  disableFuture?: boolean;
  showToday?: boolean;
  allowSingleDateSelection?: boolean;
  disablePast?: boolean;
  maxDate?: moment.Moment;
  minDate?: moment.Moment;
  initialVisibleRange?: VisibleRangeType;
}

export interface CustomDayPropsType {
  weekIndex: WeekIndex;
  weekDay: string;
}

export interface DayProps {
  weekIndex: WeekIndex;
  daySize: number;
  customDayComponent?: (x: CustomDayPropsType) => JSX.Element;
}

export interface SelectedRangeType {
  startDate: moment.Moment;
  endDate: moment.Moment;
}

export interface SelectedRangeInfoInputPropsType {
  children: ReactChild;
  value: SelectedRangeType | null | undefined;
  onChange: (x: SelectedRangeType) => void;
  placeHolder?: string;
  label?: string;
  selectedRangeFormat?: string;
  isValidatedField: boolean;
  disableTimeSelection: boolean;
  validateRange?: (date: SelectedRangeType) => ValidationConfigType | true;
}

export interface MonthGridPropsType extends BaseDateRangePickerChildType {
  customCalendarHeading?: () => JSX.Element;
  handleVisibleRange: (value: moment.MomentSetObject[], index?: number) => void;
  setViewMode: React.Dispatch<React.SetStateAction<keyof typeof ViewMode>>;
  disablePast?: boolean;
  isCompactView: boolean;
}

export interface YearGridPropsType extends BaseDateRangePickerChildType {
  customCalendarHeading?: () => JSX.Element;
  handleVisibleRange: (value: moment.MomentSetObject[], index?: number) => void;
  setViewMode: React.Dispatch<React.SetStateAction<keyof typeof ViewMode>>;
  setVisibleYearsRangeIndex: React.Dispatch<React.SetStateAction<number>>;
  visibleYearsRangeIndex: number;
  disablePast?: boolean;
  isCompactView: boolean;
}

export interface DayGridPropsType extends BaseDateRangePickerChildType {
  onCalendarHeadingClick?: () => void;
  onDayClick?: (x: null | moment.Moment) => void;
  showToday?: boolean;
  allowSingleDateSelection?: boolean;
  disablePast?: boolean;
  maxDate?: moment.Moment;
  minDate?: moment.Moment;
}

export type ValidationConfigType = {
  error: string;
};

export type ModifierType = {
  INFO_WHEN_FUTURE_DISABLED?: string;
  INFO_WHEN_PAST_DISABLED?: string;
  DEFAULT_INFO?: string;
};

export type FooterProps = {
  disablePast?: boolean;
  disableFuture?: boolean;
  modifiers?: ModifierType;
};

export interface DateTimeRangePickerPropsType {
  /**
   * Disable all the dates after today (exclusive).
   */
  disableFuture?: boolean;
  errorText?: string;
  helpText?: string;
  label?: string;
  onChange: (date: SelectedRangeType) => void;
  showToday?: boolean;
  value: SelectedRangeType | null | undefined;
  placeHolder?: string;
  /**
   * Disabled by default, if enabled double click on a date to select it.
   */
  allowSingleDateSelection?: boolean;
  /**
   * Disable all the dates upto today (exclusive).
   */
  disablePast?: boolean;
  /**
   * Disables time selection for the picker.  Note: [startDate] at 12:00 AM to [endDate] at 11:59 PM.
   */
  disableTimeSelection?: boolean;
  /**
   * Disables all the dates after the provided date.
   */
  maxDate?: moment.Moment;
  /**
   * Disables all the dates before the provided date.
   */
  minDate?: moment.Moment;
  /**
   * A string to format the selected range in input.
   */
  selectedRangeFormat?: string;
  /**
   * A validator fn to validate the selected range when range picker is open, shows errors passed via modifiers inside the opened picker itself.
   */
  validateRange?: (date: SelectedRangeType) => ValidationConfigType | true;
  /**
   * A boolean to be used for validating field when picker is closed, only to be used for validating if a range is selected or not.
   */
  validationState?: boolean;
  ariaLabel?: string;
  /**
   * An object to show info in calendar footer.
   */
  modifiers?: ModifierType;
  necessityIndicator?: NecessityIndicatorType;
}

export type VisibleRangeType = moment.Moment[] | null;
