import { TODAY } from 'merchant_common/views/Reports/constants';
import moment from 'moment';

export const pickerAriaLabel = 'reports date time range picker';
export const pickerHelpText = 'picker help text';
export const pickerFieldLabel = 'picker field label';
export const pickerInputFieldAL = 'Picker Input Field';
export const mainPickerContainerAL = 'Picker Container';
export const pickerInputFormat = 'MMMM DD, YYYY ~ h:mm A';
export const initialState = {
  startDate: TODAY.clone().startOf('day').subtract(5, 'day'),
  endDate: TODAY.clone().subtract(2, 'day').endOf('day').set({
    minute: 59,
    second: 59,
  }),
};
export const selectedDateInfoBadgeFormat = 'MMM DD, YYYY';
export const refRangeFirstMonth = 2;

export const getRefMonthRangeLabelText = (isCompactView = false) => {
  if (isCompactView) {
    return `Months Range -> ${moment().month(refRangeFirstMonth).format('MMM')}`;
  } else {
    return `Months Range -> ${moment().month(refRangeFirstMonth).format('MMM')} - ${moment()
      .month(refRangeFirstMonth + 1)
      .format('MMM')}`;
  }
};

export const defineMatchMedia = (matches: boolean) =>
  Object.defineProperty(window, 'matchMedia', {
    configurable: true,
    value: jest.fn().mockImplementation((query) => ({
      matches,
      media: query,
      onchange: null,
      addListener: jest.fn(),
      removeListener: jest.fn(),
      addEventListener: jest.fn(),
      removeEventListener: jest.fn(),
      dispatchEvent: jest.fn(),
    })),
  });
