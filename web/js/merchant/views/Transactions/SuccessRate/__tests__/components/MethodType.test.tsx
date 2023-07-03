import React from 'react';
import { render, screen, userEvent, waitFor } from 'test-utils';
import MethodType from 'merchant/views/Transactions/SuccessRate/components/MethodType';
import { METHOD_TYPES_MAP } from 'merchant/views/Transactions/SuccessRate/constants';
const initProps = {
  handleCardTypeChange: jest.fn(),
};

describe('<MethodType/>', () => {
  test('should render method types of Card correctly on screen', () => {
    const currentMethodType = METHOD_TYPES_MAP.Card;
    render(<MethodType {...initProps} currentMethodType={currentMethodType} />);
    expect(screen.getByTestId('method-types')).toBeVisible();
  });
  test('renders the correct number of tabs for Card', async () => {
    const currentMethodType = METHOD_TYPES_MAP.Card;
    render(<MethodType {...initProps} currentMethodType={currentMethodType} />);
    const tabs = screen.getAllByTestId('tab');
    await waitFor(() => {
      expect(tabs.length).toBe(3);
    });
  });
  test('should fire callback and sets active tab on selection for Card', () => {
    const currentMethodType = METHOD_TYPES_MAP.Card;
    const selectedMethodType = currentMethodType.defaultType;
    render(
      <MethodType
        {...initProps}
        currentMethodType={currentMethodType}
        selectedMethodType={selectedMethodType}
      />,
    );
    const { handleCardTypeChange } = initProps;
    currentMethodType.types.forEach(async (type) => {
      const tab = screen.getByText(type.name);
      await userEvent.click(tab);
      await waitFor(() => {
        expect(handleCardTypeChange).toHaveBeenCalledWith(type.value);
      });
      expect(tab).toHaveClass('active');
    });
  });
});
