import React, { useState } from 'react';
import { render, screen, userEvent } from 'test-utils';
import { YearPicker } from 'merchant_common/views/Reports/components';
import { TODAY } from 'merchant_common/views/Reports/constants';

describe('Year Picker', () => {
  const App = () => {
    const [year, setYear] = useState<number>();
    return (
      <YearPicker
        label="Year Field"
        helpText="Select a year for this test"
        placeHolder="Select A Year Test"
        value={year}
        onChange={(value) => setYear(value)}
      />
    );
  };

  test('should render children without error', () => {
    render(<App />);
    expect(screen.getByText('Select A Year Test')).toBeInTheDocument();
    expect(screen.getByText('Year Field')).toBeInTheDocument();
    expect(screen.getByLabelText('Selected Year Field')).toBeInTheDocument();
  });

  test('should open picker on input click', async () => {
    render(<App />);
    const labelBtn = screen.getByLabelText('Selected Year Field');
    await userEvent.click(labelBtn);
    expect(screen.getByLabelText(`Select ${TODAY.clone().format('YYYY')}`)).toBeInTheDocument();
    await userEvent.click(screen.getByText('Year Field'));
    expect(
      screen.queryByLabelText(`Select ${TODAY.clone().format('YYYY')}`),
    ).not.toBeInTheDocument();
  });

  test('should update selected month in field', async () => {
    render(<App />);
    const labelBtn = screen.getByLabelText('Selected Year Field');
    await userEvent.click(labelBtn);
    const refMonth = screen.getByLabelText(`Select ${TODAY.clone().format('YYYY')}`);
    await userEvent.click(refMonth);
    expect(
      screen.queryByLabelText(`Select ${TODAY.clone().format('YYYY')}`),
    ).not.toBeInTheDocument();
    expect(screen.queryByLabelText(`Selected Year Field`)).toHaveTextContent(
      TODAY.clone().format('YYYY'),
    );
  });
});
