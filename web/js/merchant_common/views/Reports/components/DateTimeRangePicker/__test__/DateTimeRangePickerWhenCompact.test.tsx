import React, { useState } from 'react';
import { render, screen, userEvent } from 'test-utils';
import { DateTimeRangePicker } from 'merchant_common/views/Reports/components';
import {
  defineMatchMedia,
  getRefMonthRangeLabelText,
  initialState,
  pickerAriaLabel,
  pickerFieldLabel,
  pickerHelpText,
  pickerInputFieldAL,
  pickerInputFormat,
  refRangeFirstMonth,
} from './fixtures';
import { TODAY } from 'merchant_common/views/Reports/constants';

defineMatchMedia(true);

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
        validateRange={() => ({
          error: 'Range is invalid',
        })}
        {...props}
      />
    </div>
  );
};

describe('Date time calendar component in case of small screen devices', () => {
  beforeEach(async () => {
    render(<App allowSingleDateSelection={false} />);
    const rangeInput = screen.getByLabelText(pickerInputFieldAL);
    await userEvent.click(rangeInput);
  });

  it('should show calendar grids months, year', () => {
    const mainCalendarRangeContainer = screen.getByLabelText('Main Calendar Range');
    expect(mainCalendarRangeContainer).toBeInTheDocument();
    const calendarHeaders = screen.getAllByLabelText('Calendar Month, Year');

    // default value of matched is false, so it will show full calendar. In that range will have 2 calendar grids.
    expect(calendarHeaders.length).toBe(1);

    expect(calendarHeaders.map((e) => e.children.item(0)?.childNodes.item(0).textContent)).toEqual([
      initialState.startDate.clone().format('MMMM, YYYY'),
    ]);
  });

  it('should handle visible cal range when clicked on prev button', async () => {
    const prevBtn = screen.getByLabelText('Previous Range');

    await userEvent.click(prevBtn);

    const calendarHeaders = screen.getByLabelText('Calendar Month, Year');
    expect(calendarHeaders.children.item(0)?.childNodes.item(0).textContent).toEqual(
      initialState.startDate.clone().subtract(1, 'month').format('MMMM, YYYY'),
    );
  });

  it('should handle visible cal range when clicked on next button', async () => {
    const nextBtn = screen.getByLabelText('Next Range');

    await userEvent.click(nextBtn);

    const calendarHeaders = screen.getByLabelText('Calendar Month, Year');
    expect(calendarHeaders.children.item(0)?.childNodes.item(0).textContent).toEqual(
      initialState.startDate.clone().add(1, 'month').format('MMMM, YYYY'),
    );
  });

  it('should show weekdays in grids', () => {
    const data = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    data.forEach((day) => {
      expect(screen.getAllByLabelText(`${day}day Column`).length).toBe(1);
    });
  });

  it('should switch to month range view when clicked on month header', async () => {
    const calendarHeader = screen.getByLabelText('Calendar Month, Year');
    await userEvent.click(calendarHeader);
    expect(screen.getByLabelText('Months Range -> Jan')).toBeInTheDocument();
  });

  it('should switch back to date range view when clicked on a month range', async () => {
    await userEvent.click(screen.getAllByLabelText('Calendar Month, Year')[0]);
    const monthRange = screen.getByLabelText(getRefMonthRangeLabelText(true));
    await userEvent.click(monthRange);

    const calendarHeaders = screen.getByLabelText('Calendar Month, Year');

    expect(calendarHeaders.children.item(0)?.childNodes.item(0).textContent).toEqual(
      initialState.startDate.clone().set('month', refRangeFirstMonth).format('MMMM, YYYY'),
    );
  });

  it('should switch back to monthly view if clicked on ref year', async () => {
    await userEvent.click(screen.getAllByLabelText('Calendar Month, Year')[0]);
    await userEvent.click(screen.getByLabelText('Calendar Month, Year'));
    await userEvent.click(screen.getByLabelText(`Select ${TODAY.format('YYYY')}`));
    expect(
      screen.getByLabelText('Calendar Month, Year').children.item(0)?.childNodes.item(0)
        .textContent,
    ).toEqual(TODAY.format('YYYY'));
  });

  it('should not update main input field when clicked outside, if not validated', async () => {
    const refDateMoment1 = initialState.startDate.clone().set('date', 12);
    const refDate1 = screen.getByLabelText(`Date is ${refDateMoment1.format('DD MMMM YYYY')}`);
    await userEvent.click(refDate1);

    const refDateMoment2 = initialState.startDate.clone().set('date', 21);
    const refDate2 = screen.getByLabelText(`Date is ${refDateMoment2.format('DD MMMM YYYY')}`);
    await userEvent.click(refDate2);

    await userEvent.click(screen.getByLabelText('btn-outside'));

    expect(
      screen.queryByText(
        `${refDateMoment1.format(pickerInputFormat)} - ${refDateMoment2.format(pickerInputFormat)}`,
      ),
    ).not.toBeInTheDocument();
  });
});
