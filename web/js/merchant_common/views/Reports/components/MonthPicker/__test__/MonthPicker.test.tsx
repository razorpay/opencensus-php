import React, { useState } from 'react';
import { render, screen, userEvent } from 'test-utils';
import { MonthPicker } from 'merchant_common/views/Reports/components';
import { MonthIndex } from 'merchant_common/views/Reports/components/types';

describe('Month Picker', () => {
  const App = () => {
    const [month, setMonth] = useState<MonthIndex>();
    return (
      <MonthPicker
        label="Month Field"
        helpText="Select a month for this test"
        placeHolder="Select A Month"
        value={month}
        onChange={(value) => setMonth(value)}
      />
    );
  };

  test('should render children without error', () => {
    render(<App />);
    expect(screen.getByText('Select A Month')).toBeInTheDocument();
    expect(screen.getByText('Month Field')).toBeInTheDocument();
    expect(screen.getByLabelText('Selected Month Field')).toBeInTheDocument();
  });

  test('should open picker on input click', async () => {
    render(<App />);
    const labelBtn = screen.getByLabelText('Selected Month Field');
    await userEvent.click(labelBtn);
    expect(screen.getByLabelText(`Months Range -> Mar`)).toBeInTheDocument();
    await userEvent.click(screen.getByText('Month Field'));
    expect(screen.queryByLabelText(`Months Range -> Mar`)).not.toBeInTheDocument();
  });

  test('should update selected month in field', async () => {
    render(<App />);
    const labelBtn = screen.getByLabelText('Selected Month Field');
    await userEvent.click(labelBtn);
    const refMonth = screen.getByLabelText(`Months Range -> Mar`);
    await userEvent.click(refMonth);
    expect(screen.queryByLabelText(`Months Range -> Mar`)).not.toBeInTheDocument();
    expect(screen.queryByLabelText(`Selected Month Field`)).toHaveTextContent('March');
  });
});
