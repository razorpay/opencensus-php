import React from 'react';
import { fireEvent, getByText, render, screen } from '@testing-library/react';
import InputSelector from 'merchant/views/Wallet/BatchActions/components/InputSelector';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';

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
    const { container } = render(
      <BladeProvider themeTokens={paymentTheme}>
        <InputSelector options={options} setInput={jest.fn()} />,
      </BladeProvider>,
    );

    await fireEvent.click(screen.getByText('Select Option'));

    expect(getByText(container, options[0].label)).toBeInTheDocument();
    expect(getByText(container, options[1].label)).toBeInTheDocument();
  });
});
