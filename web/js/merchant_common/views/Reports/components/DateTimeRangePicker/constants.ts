import moment from 'moment';
import { renderNumberArray } from './utils';

export const DAY_SIZE = 36;
export const MAX_YEAR_WINDOW_INDEX_INCLUSIVE = 5;
export const MIN_YEAR_WINDOW_INDEX_INCLUSIVE = -2;
export const MONTHS = renderNumberArray(12);
export const MONTHS_RANGE_FULL = [
  [0, 1],
  [1, 2],
  [2, 3],
  [3, 4],
  [4, 5],
  [5, 6],
  [6, 7],
  [7, 8],
  [8, 9],
  [9, 10],
  [10, 11],
];
export const MONTH_RANGE_COMPACT = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
export const SELECTED_DATE_RANGE_RENDER_FORMAT = 'MMM DD, YYYY | h:mm A';
export const WEEK_DAYS = renderNumberArray(7);
export const YEAR_COLUMNS_COUNT_FULL = 3;
export const YEAR_COLUMNS_COUNT_COMPACT = 3;
export const YEAR_ROWS_COUNT_FULL = 7;
export const YEAR_ROWS_COUNT_COMPACT = 3;
export const WEEKDAYS = [0, 1, 2, 3, 4, 5, 6];
export const DEFAULT_DATES = [moment().startOf('day').subtract(5, 'day'), moment().endOf('day')];
