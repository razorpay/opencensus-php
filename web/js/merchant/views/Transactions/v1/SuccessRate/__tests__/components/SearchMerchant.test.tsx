import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import SearchMerchant from 'merchant/views/Transactions/v1/SuccessRate/components/SearchMerchant';

const initProps = {
  onSearch: jest.fn(),
  onReset: jest.fn(),
};

describe('<SearchMerchant/>', () => {
  test('should render SearchMerchant component on screen', () => {
    render(<SearchMerchant {...initProps} />);
    expect(screen.getByTestId('admin-search')).toBeVisible();
  });
  test('should update the query value when the input changes', async () => {
    render(<SearchMerchant {...initProps} />);
    const input = screen.getByPlaceholderText('Search Merchant ID') as HTMLInputElement;
    await userEvent.type(input, 'Unit Tests');

    expect(input.value).toBe('Unit Tests');
  });
  test('should call the onSearch function with the query value when "Set Merchant" button is clicked', async () => {
    render(<SearchMerchant {...initProps} />);
    const input = screen.getByPlaceholderText('Search Merchant ID');
    const setMerchantButton = screen.getByText('Set Merchant');
    const { onSearch } = initProps;

    await userEvent.type(input, 'Unit Tests');
    await userEvent.click(setMerchantButton);

    expect(onSearch).toHaveBeenCalledTimes(1);
    expect(onSearch).toHaveBeenCalledWith('Unit Tests');
  });
  test('should call the onReset function and clear the query value when "Reset" button is clicked', async () => {
    render(<SearchMerchant {...initProps} />);
    const input = screen.getByPlaceholderText('Search Merchant ID') as HTMLInputElement;
    const resetButton = screen.getByText('Reset');
    const { onReset } = initProps;

    await userEvent.type(input, 'Unit Tests');
    await userEvent.click(resetButton);

    expect(input.value).toBe('');
    expect(onReset).toHaveBeenCalledTimes(1);
  });
});
