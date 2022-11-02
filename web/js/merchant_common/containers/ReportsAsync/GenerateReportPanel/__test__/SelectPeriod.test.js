import SelectPeriod from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/SelectPeriod';
import * as utils from 'merchant_common/containers/ReportsAsync/utils';
import { fireEvent, render, screen } from 'common/services/test/test-utils';
import * as analytics from 'common/utils/analytics';
import { DEFAULT_PERIOD_OPTIONS } from 'merchant/views/Capital/CashAdvance/constants';
import moment from 'moment';
import { render as testingLibraryRender } from '@testing-library/react';

// setSystemTime doesnt work here as
// currentMonth in disableFutureMonths is declared outside component

const actualCurrentMonth = moment().month() + 1;

describe('Select Period', () => {
  const defaultProps = {
    avlblPeriodOptions: DEFAULT_PERIOD_OPTIONS,
    defaultProps: false,
    isFormDisabled: false,
    dateRangeError: false,
    selectedConfig: null,
    onDateRangeChanges: jest.fn(),
  };

  let appRef = {};

  const App = (props = {}) => (
    <SelectPeriod
      {...defaultProps}
      {...props}
      ref={(ref) => {
        appRef = ref;
      }}
    />
  );

  const analyticsTrackSpy = jest.spyOn(analytics, 'analyticsTrack');

  const checkAndSelectDate = (
    elementName,
    dateToBeSelected = 'October 5, 2022',
    value = '05-10-2022',
  ) => {
    const selectDateElement = screen.getByRole('textbox', { name: elementName });

    fireEvent.click(selectDateElement);
    fireEvent.click(screen.getByTitle(dateToBeSelected));

    expect(selectDateElement).toHaveValue(value);

    // this is to cover user typing scenario in onDateTimeChange fn
    fireEvent.change(selectDateElement, { target: { value: '11-10-2022' } });
  };

  test('should render Select Period', () => {
    render(<App />);
    expect(screen.getByRole('combobox', { name: 'Select Period' })).toBeInTheDocument();
  });

  test('should call analytics on period change', () => {
    render(<App />);
    const selectPeriodElement = screen.getByRole('combobox', { name: 'Select Period' });

    // yesterday is default option
    expect(selectPeriodElement).toHaveValue('yesterday');

    const lastMonthOption = DEFAULT_PERIOD_OPTIONS[4];
    fireEvent.change(selectPeriodElement, { target: { value: lastMonthOption.name } });

    expect(analyticsTrackSpy).toHaveBeenCalledWith({
      objectName: 'select period',
      actionName: 'clicked',
      screen: 'reports',
      properties: {
        location: 'generate reports',
        period: lastMonthOption.name,
      },
    });
  });

  test('should not show PredefinedPeriodDurations when form is disabled', () => {
    render(<App isFormDisabled />);
    expect(screen.queryByTestId('selected-period-text')).not.toBeInTheDocument();
  });

  describe('For all date based tests', () => {
    beforeAll(() => {
      jest.useFakeTimers('modern').setSystemTime(new Date('2022-10-10'));
    });

    afterAll(() => {
      jest.useRealTimers();
    });

    test.skip('should render appropriate date periods in PredefinedPeriodDurations', () => {
      render(<App />);

      // default selectedPeriod is yesterday
      expect(screen.getByTestId('selected-period-text')).toHaveTextContent('Oct 9, 2022');
      const selectPeriodElement = screen.getByRole('combobox', { name: 'Select Period' });
      // change value to last 7 days
      fireEvent.change(selectPeriodElement, { target: { value: DEFAULT_PERIOD_OPTIONS[3].name } });
      expect(screen.getByTestId('selected-period-text')).toHaveTextContent(
        'Oct 3, 2022 to Oct 9, 2022',
      );
      // change value to last last month
      fireEvent.change(selectPeriodElement, { target: { value: DEFAULT_PERIOD_OPTIONS[4].name } });
      expect(screen.getByTestId('selected-period-text')).toHaveTextContent(
        'Sep 1, 2022 to Sep 30, 2022',
      );

      // change value to all
      fireEvent.change(selectPeriodElement, { target: { value: DEFAULT_PERIOD_OPTIONS[0].name } });
      expect(screen.queryByTestId('selected-period-text')).not.toBeInTheDocument();
    });

    test.skip('should render select date picker when selected period is daily', () => {
      const { debug } = render(<App defaultPeriod={DEFAULT_PERIOD_OPTIONS[5].name} />);
      // checkAndSelectDate('Select Date');

      const selectDateElement = screen.getByRole('textbox', { name: 'Select Date' });

      fireEvent.click(selectDateElement);
      debug(null, 1000000);
    });

    test.skip('should render select month picker when selected period is monthly', () => {
      const { container } = render(<App defaultPeriod={DEFAULT_PERIOD_OPTIONS[6].name} />);
      const monthElement = screen.getByText('Select Month');

      fireEvent.click(monthElement);
      fireEvent.click(container.querySelector("input[name='selectedMonth'"));

      const monthDisabledClassName = 'rc-calendar-month-panel-cell-disabled';

      const remainingMonthsInCurrentYear = 12 - actualCurrentMonth;
      // For testing disableFutureMonths fn
      expect(document.getElementsByClassName(monthDisabledClassName)).toHaveLength(
        remainingMonthsInCurrentYear,
      );

      // go back to previous year
      fireEvent.click(screen.getAllByTitle('Last year (Control + left)')[1]);

      expect(document.getElementsByClassName(monthDisabledClassName)).toHaveLength(0);
    });

    test.skip('should render data range picker when selected period is custom', () => {
      render(<App defaultPeriod={DEFAULT_PERIOD_OPTIONS[7].name} />);
      checkAndSelectDate('Start At');

      const specifyTimeCheckbox = screen.getByRole('checkbox', { name: 'Specify Time' });

      fireEvent.click(specifyTimeCheckbox);

      expect(specifyTimeCheckbox).toBeChecked();

      // make sure checkAndSelectDate is called after checkbox is clicked to close the calendar popup
      checkAndSelectDate('End At');

      expect(defaultProps.onDateRangeChanges).toHaveBeenCalledTimes(2);

      const startTime = '10:00 pm';
      const startAtTimeElement = screen.getByTestId('selectedStartAtTime');
      fireEvent.change(startAtTimeElement, {
        target: { value: startTime, name: 'selectedStartAtTime' },
      });
      expect(startAtTimeElement).toHaveValue(startTime);

      screen.getByTestId('selectedEndAtTime');
    });

    test.skip("should show data range error when there's a date range error exists", () => {
      const dateRangeError = 'Start date cannot be greater than end date';
      render(
        <App defaultPeriod={DEFAULT_PERIOD_OPTIONS[7].name} dateRangeError={dateRangeError} />,
      );
      expect(screen.getByText(dateRangeError)).toBeInTheDocument();
    });

    test.skip('should show monthly invoice message when selected config is Monthly Invoice Report', () => {
      render(<App selectedConfig={{ name: 'Monthly Invoice Report' }} />);
      expect(
        screen.getByText(
          'To reconcile the monthly invoice of December 20 and January 21 with the monthly invoice report, please use the custom period option as per the billing period mentioned above.',
        ),
      ).toBeInTheDocument();
    });
  });

  test('should render custom config when isCustomConfig is true', () => {
    render(<App isCustomConfig />);
    expect(screen.getByText('Select Month')).toBeInTheDocument();
  });

  test('should call onDateRangeChanges on calling reset', () => {
    // test-utils rerender doesn't trigger componentDidUpdate
    const { rerender } = testingLibraryRender(<App selectedConfig={{ id: '1' }} />);
    rerender(<App selectedConfig={{ id: '2' }} />);
    expect(defaultProps.onDateRangeChanges).toHaveBeenCalled();
  });

  test('should test getDateRange function', () => {
    render(<App />);
    const getStartAndEndUnixTimeStampsForDaysFromSpy = jest.spyOn(
      utils,
      'getStartAndEndUnixTimeStampsForDaysFrom',
    );
    // const momentSpy = jest.spyOn(moment, 'getStartAndEndUnixTimeStampsForDaysFrom')

    const selectPeriodElement = screen.getByRole('combobox', { name: 'Select Period' });

    const updatePeriodAndCallGetDateRangeFunc = (period) => {
      fireEvent.change(selectPeriodElement, { target: { value: period.name } });
      appRef.getDateRange();
    };

    // today
    updatePeriodAndCallGetDateRangeFunc(DEFAULT_PERIOD_OPTIONS[1]);
    expect(getStartAndEndUnixTimeStampsForDaysFromSpy).toHaveBeenCalledWith(0, expect.any(moment));

    // yesterday
    updatePeriodAndCallGetDateRangeFunc(DEFAULT_PERIOD_OPTIONS[2]);
    expect(getStartAndEndUnixTimeStampsForDaysFromSpy).toHaveBeenLastCalledWith(0);

    // last_7_days
    updatePeriodAndCallGetDateRangeFunc(DEFAULT_PERIOD_OPTIONS[3]);
    expect(getStartAndEndUnixTimeStampsForDaysFromSpy).toHaveBeenLastCalledWith(7);

    // last_month
    updatePeriodAndCallGetDateRangeFunc(DEFAULT_PERIOD_OPTIONS[4]);
    expect(getStartAndEndUnixTimeStampsForDaysFromSpy).toHaveLastReturnedWith([
      expect.any(Number),
      expect.any(Number),
    ]);

    // daily
    updatePeriodAndCallGetDateRangeFunc(DEFAULT_PERIOD_OPTIONS[5]);
    expect(getStartAndEndUnixTimeStampsForDaysFromSpy).toHaveBeenLastCalledWith(
      0,
      expect.any(moment),
    );

    // monthly
    updatePeriodAndCallGetDateRangeFunc(DEFAULT_PERIOD_OPTIONS[6]);
    expect(getStartAndEndUnixTimeStampsForDaysFromSpy).toHaveLastReturnedWith([
      expect.any(Number),
      expect.any(Number),
    ]);

    // dateRange
    updatePeriodAndCallGetDateRangeFunc(DEFAULT_PERIOD_OPTIONS[7]);
    expect(getStartAndEndUnixTimeStampsForDaysFromSpy).toHaveLastReturnedWith([
      expect.any(Number),
      expect.any(Number),
    ]);

    // all
    updatePeriodAndCallGetDateRangeFunc(DEFAULT_PERIOD_OPTIONS[0]);
    expect(getStartAndEndUnixTimeStampsForDaysFromSpy).toHaveLastReturnedWith(
      expect.arrayContaining([]),
    );
  });

  test('should test getCustomConfigYear function', () => {
    render(<App />);
    const customConfigYearSpy = jest.spyOn(appRef, 'getCustomConfigYear');
    appRef.getCustomConfigYear();
    expect(customConfigYearSpy).toHaveLastReturnedWith({
      year: expect.any(Number),
      month: expect.any(Number),
    });
  });
});
