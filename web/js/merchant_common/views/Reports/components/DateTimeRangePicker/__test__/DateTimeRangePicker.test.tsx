import React, { useState } from 'react';
import { render, screen, userEvent, within } from 'test-utils';
import { DateTimeRangePicker } from 'merchant_common/views/Reports/components';
import {
  defineMatchMedia,
  getRefMonthRangeLabelText,
  initialState,
  mainPickerContainerAL,
  pickerAriaLabel,
  pickerFieldLabel,
  pickerHelpText,
  pickerInputFieldAL,
  pickerInputFormat,
  refRangeFirstMonth,
  selectedDateInfoBadgeFormat,
} from './fixtures';
import { TODAY } from 'merchant_common/views/Reports/constants';

defineMatchMedia(false);

const App = (props) => {
  const [state, setState] = useState(initialState);
  return (
    <div>
      <button aria-label="btn-outside">Test Outside Click</button>
      <DateTimeRangePicker
        ariaLabel={pickerAriaLabel}
        helpText={pickerHelpText}
        label={pickerFieldLabel}
        onChange={(date) => setState(date)}
        value={state}
        showToday
        disableFuture={false}
        disablePast={false}
        selectedRangeFormat={pickerInputFormat}
        allowSingleDateSelection
        {...props}
      />
    </div>
  );
};

describe('Date time calendar component when picker is inactive', () => {
  it('should render calendar field without error', () => {
    render(
      <App
        validate={() => [
          {
            condition: true,
            error: '',
          },
        ]}
      />,
    );
    const pickerInput = screen.getByLabelText(pickerInputFieldAL);
    expect(screen.getByText(pickerHelpText)).toBeInTheDocument();
    expect(screen.getByText(pickerFieldLabel)).toBeInTheDocument();
    expect(pickerInput).toBeInTheDocument();
    expect(screen.getByLabelText(mainPickerContainerAL).childNodes.length).toBe(0);
    expect(
      screen.getByText(
        `${initialState.startDate.clone().format(pickerInputFormat)} - ${initialState.endDate
          .clone()
          .format(pickerInputFormat)}`,
      ),
    ).toBeInTheDocument();
  });
});

describe('Date time calendar component when picker is active', () => {
  beforeEach(async () => {
    render(<App />);
    const rangeInput = screen.getByLabelText(pickerInputFieldAL);
    await userEvent.click(rangeInput);
  });

  it('should open picker if user clicks on input', () => {
    expect(screen.getByLabelText(mainPickerContainerAL).childNodes.length).not.toBe(0);
    expect(screen.getByLabelText(pickerAriaLabel)).toBeInTheDocument();
  });

  it('should show picker header as expected', () => {
    // assertion to check basic header.
    expect(screen.getByText('Selected Date Range:')).toBeInTheDocument();
    expect(screen.getByText('Include Time')).toBeInTheDocument();

    // expecting desired dates to show up.
    expect(
      screen.getByText(`${initialState.startDate.clone().format(selectedDateInfoBadgeFormat)}`),
    ).toBeInTheDocument();
    expect(
      screen.getByText(`${initialState.endDate.clone().format(selectedDateInfoBadgeFormat)}`),
    ).toBeInTheDocument();

    // here initial date and final date has same time, so time shown must be same for both, hence length 2
    expect(screen.getAllByText(`${initialState.endDate.clone().format('h:mm A')}`).length).toBe(2);

    const includeTimeSwitch = screen.getByLabelText('Include Time Switch');
    expect(includeTimeSwitch).toBeInTheDocument();
    expect(includeTimeSwitch.children[0]).toHaveAttribute('value', 'true');

    expect(
      screen.getAllByLabelText(
        `Selected Time Is ${initialState.startDate.clone().format('h:mm A')}`,
      ).length,
    ).toEqual(2);
  });

  it('should show navigation buttons when picker is active', () => {
    expect(screen.getByLabelText('Previous Range')).toBeInTheDocument();
    expect(screen.getByLabelText('Next Range')).toBeInTheDocument();
  });

  it('should show calendar grids months, year', () => {
    const mainCalendarRangeContainer = screen.getByLabelText('Main Calendar Range');
    expect(mainCalendarRangeContainer).toBeInTheDocument();
    const calendarHeaders = screen.getAllByLabelText('Calendar Month, Year');

    // default value of matched is false, so it will show full calendar. In that range will have 2 calendar grids.
    expect(calendarHeaders.length).toBe(2);

    expect(calendarHeaders.map((e) => e.children.item(0)?.childNodes.item(0).textContent)).toEqual([
      initialState.startDate.clone().format('MMMM, YYYY'),
      initialState.startDate.clone().add(1, 'month').format('MMMM, YYYY'),
    ]);
  });

  it('should show weekdays in grids', () => {
    const data = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    data.forEach((day) => {
      expect(screen.getAllByLabelText(`${day}day Column`).length).toBe(2);
    });
  });

  it('should render start date in visible calendar grid', () => {
    expect(
      screen.getByLabelText(`Date is ${initialState.startDate.clone().format('DD MMMM YYYY')}`),
    );
  });

  // fix added, need a complete fix.
  it('should change date if clicked on a ref date', async () => {
    const prevBtn = screen.getByLabelText('Previous Range');
    await userEvent.click(prevBtn);

    // select a date before start date;
    const refDateMoment = initialState.startDate.clone().subtract(1, 'month').set('date', 4);
    const refDate = screen.getByLabelText(`Date is ${refDateMoment.format('DD MMMM YYYY')}`);
    await userEvent.click(refDate);
    expect(
      screen.getByText(`${refDateMoment.clone().format(selectedDateInfoBadgeFormat)}`),
    ).toBeInTheDocument();

    // select a date between start and end date - 1
    const refDateMoment2 = initialState.startDate.clone().subtract(1, 'month').set('date', 8);
    const refDate2 = screen.getByLabelText(`Date is ${refDateMoment2.format('DD MMMM YYYY')}`);
    await userEvent.click(refDate2);
    expect(
      screen.getByText(`${refDateMoment2.clone().format(selectedDateInfoBadgeFormat)}`),
    ).toBeInTheDocument();

    // select a date between start and end date - 2
    const refDateMoment3 = initialState.startDate.clone().subtract(1, 'month').set('date', 5);
    const refDate3 = screen.getByLabelText(`Date is ${refDateMoment3.format('DD MMMM YYYY')}`);
    await userEvent.click(refDate3);
    expect(
      screen.getByText(`${refDateMoment3.clone().format(selectedDateInfoBadgeFormat)}`),
    ).toBeInTheDocument();

    // select a date after end date
    const refDateMoment4 = initialState.endDate.clone().subtract(1, 'month').set('date', 12);
    const refDate4 = screen.getByLabelText(`Date is ${refDateMoment4.format('DD MMMM YYYY')}`);
    await userEvent.click(refDate4);
    expect(
      screen.getByText(`${refDateMoment4.clone().format(selectedDateInfoBadgeFormat)}`),
    ).toBeInTheDocument();

    // double click emic - 1
    await userEvent.click(refDate4);
    // start and end date to be same
    expect(
      screen.getAllByText(`${refDateMoment4.clone().format(selectedDateInfoBadgeFormat)}`).length,
    ).toEqual(2);

    // double click emic - 2
    await userEvent.click(refDate4);
    // start and end date to be same
    expect(
      screen.getAllByText(`${refDateMoment4.clone().format(selectedDateInfoBadgeFormat)}`).length,
    ).toEqual(2);
  });

  it('should handle visible cal range when clicked on prev button', async () => {
    const prevBtn = screen.getByLabelText('Previous Range');

    await userEvent.click(prevBtn);

    const calendarHeaders = screen.getAllByLabelText('Calendar Month, Year');
    expect(calendarHeaders.map((e) => e.children.item(0)?.childNodes.item(0).textContent)).toEqual([
      initialState.startDate.clone().subtract(1, 'month').format('MMMM, YYYY'),
      initialState.startDate.clone().format('MMMM, YYYY'),
    ]);
  });

  it('should handle visible cal range when clicked on next button', async () => {
    const nextBtn = screen.getByLabelText('Next Range');

    await userEvent.click(nextBtn);

    const calendarHeaders = screen.getAllByLabelText('Calendar Month, Year');
    expect(calendarHeaders.map((e) => e.children.item(0)?.childNodes.item(0).textContent)).toEqual([
      initialState.startDate.clone().add(1, 'month').format('MMMM, YYYY'),
      initialState.startDate.clone().add(2, 'month').format('MMMM, YYYY'),
    ]);
  });

  it('should switch to month range view when clicked on month header', async () => {
    const calendarHeader = screen.getAllByLabelText('Calendar Month, Year')[0];
    await userEvent.click(calendarHeader);
    expect(screen.getByLabelText(getRefMonthRangeLabelText())).toBeInTheDocument();
  });

  it('should switch back to date range view when clicked on a month range', async () => {
    await userEvent.click(screen.getAllByLabelText('Calendar Month, Year')[0]);
    const monthRange = screen.getByLabelText(getRefMonthRangeLabelText());
    await userEvent.click(monthRange);

    const calendarHeaders = screen.getAllByLabelText('Calendar Month, Year');

    expect(calendarHeaders.map((e) => e.children.item(0)?.childNodes.item(0).textContent)).toEqual([
      initialState.startDate.clone().set('month', refRangeFirstMonth).format('MMMM, YYYY'),
      initialState.startDate
        .clone()
        .set('month', refRangeFirstMonth)
        .add(1, 'month')
        .format('MMMM, YYYY'),
    ]);
  });

  it('should switch to year range view when clicked on month header', async () => {
    // open month view
    await userEvent.click(screen.getAllByLabelText('Calendar Month, Year')[0]);
    // open year view
    const calendarHeader = screen.getByLabelText('Calendar Month, Year');
    await userEvent.click(calendarHeader);
    expect(screen.getByText('Select Year')).toBeInTheDocument();
  });

  it('should switch back to monthly view if clicked on ref year', async () => {
    await userEvent.click(screen.getAllByLabelText('Calendar Month, Year')[0]);
    await userEvent.click(screen.getByLabelText('Calendar Month, Year'));

    // checking next prev in btw
    await userEvent.click(screen.getByLabelText('Previous Range'));
    await userEvent.click(screen.getByLabelText('Next Range'));

    await userEvent.click(screen.getByLabelText(`Select ${TODAY.format('YYYY')}`));
    expect(
      screen.getByLabelText('Calendar Month, Year').children.item(0)?.childNodes.item(0)
        .textContent,
    ).toEqual(TODAY.format('YYYY'));
  });

  it('should be able to toggle include time switch', async () => {
    const switchToggle = within(screen.getByLabelText('Include Time Switch')).queryByRole('button');
    if (switchToggle) {
      expect(
        screen.queryAllByLabelText(
          `Selected Time Is ${initialState.startDate.clone().format('h:mm A')}`,
        ).length,
      ).toEqual(2);

      await userEvent.click(switchToggle);

      expect(
        screen.queryAllByLabelText(
          `Selected Time Is ${initialState.startDate.clone().format('h:mm A')}`,
        ).length,
      ).toEqual(0);
    }
  });

  it('should validate and update main input field when clicked outside, when validated', async () => {
    const prevBtn = screen.getByLabelText('Previous Range');
    await userEvent.click(prevBtn);

    const refDateMoment1 = initialState.startDate.clone().subtract(2, 'day');
    const refDate1 = screen.getByLabelText(`Date is ${refDateMoment1.format('DD MMMM YYYY')}`);
    await userEvent.click(refDate1);

    const refDateMoment2 = initialState.startDate.clone().subtract(1, 'day');
    const refDate2 = screen.getByLabelText(`Date is ${refDateMoment2.format('DD MMMM YYYY')}`);
    await userEvent.click(refDate2);
    await userEvent.click(screen.getByLabelText('btn-outside'));

    expect(
      screen.queryByText(
        `${refDateMoment1.format(pickerInputFormat)} - ${refDateMoment2.format(pickerInputFormat)}`,
      ),
    ).toBeInTheDocument();
  });
});

describe('Date time calendar component when specific prop is passed', () => {
  it('should not update date when clicked on future date, if disable future is enabled', async () => {
    render(<App disableFuture={true} />);
    const rangeInput = screen.getByLabelText(pickerInputFieldAL);
    await userEvent.click(rangeInput);

    const refDateMoment1 = TODAY.clone().add(1, 'day');
    const refDate1 = screen.getByLabelText(`Date is ${refDateMoment1.format('DD MMMM YYYY')}`);
    await userEvent.click(refDate1);

    expect(
      screen.queryByText(`${refDateMoment1.format(selectedDateInfoBadgeFormat)}`),
    ).not.toBeInTheDocument();
  });
});
