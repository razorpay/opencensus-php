import React from 'react';
import { screen, waitFor } from '@testing-library/react';
import InputSelector from 'merchant/views/Wallet/BatchActions/components/InputSelector';
import { userEvent } from '@testing-library/user-event';
import { render } from 'test-utils';

describe('InputSelector tests', () => {
  test('should render InputSelector as expected', async () => {
    const options = [
      {
        label: 'Option 1',
        name: 'option_1',
      },
      {
        label: 'Option 2',
        name: 'option_2',
      },
    ];
    render(<InputSelector options={options} setInput={jest.fn()} />);

    const combobox = screen.getByRole('combobox', { name: 'Load Type' });
    await userEvent.click(combobox);

    await waitFor(() =>
      expect(screen.getByRole('option', { name: options[0].label })).toBeInTheDocument(),
    );
    expect(screen.getByRole('option', { name: options[1].label })).toBeInTheDocument();
  });
});
