import React from 'react';

import FlashCheckout from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/FlashCheckout';
import { FLASH_CHECKOUT_DEFAULT_VALUE } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/constants/DefaultValue';
import { CHECKOUT_EDITOR_FIELDS } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/createContext';
import { render, screen, fireEvent } from 'test-utils';

jest.mock('merchant/views/Settings/Configuration/CheckoutEditor/context/createContext', () => ({
  useCheckoutEditor: jest.fn(),
}));

describe('FlashCheckout', () => {
  const handleFlashCheckoutToggle = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders the FlashCheckout component with the correct title and subtitle', () => {
    useCheckoutEditor.mockReturnValue({
      values: {
        [CHECKOUT_EDITOR_FIELDS.FLASH_CHECKOUT]: false,
      },
      handleFlashCheckoutToggle,
    });

    render(<FlashCheckout />);

    expect(screen.getByText(FLASH_CHECKOUT_DEFAULT_VALUE.title)).toBeInTheDocument();
    expect(screen.getByText(FLASH_CHECKOUT_DEFAULT_VALUE.subTitle)).toBeInTheDocument();
  });

  it('renders the toggle with the correct checked state', () => {
    useCheckoutEditor.mockReturnValue({
      values: {
        [CHECKOUT_EDITOR_FIELDS.FLASH_CHECKOUT]: true,
      },
      handleFlashCheckoutToggle,
    });

    render(<FlashCheckout />);

    const toggle = screen.getByLabelText(`enable-${CHECKOUT_EDITOR_FIELDS.FLASH_CHECKOUT}`);
    expect(toggle).toBeChecked();
  });

  it('calls handleFlashCheckoutToggle when the toggle is clicked', () => {
    useCheckoutEditor.mockReturnValue({
      values: {
        [CHECKOUT_EDITOR_FIELDS.FLASH_CHECKOUT]: false,
      },
      handleFlashCheckoutToggle,
    });

    render(<FlashCheckout />);

    const toggle = screen.getByLabelText(`enable-${CHECKOUT_EDITOR_FIELDS.FLASH_CHECKOUT}`);
    fireEvent.click(toggle);
    expect(handleFlashCheckoutToggle).toHaveBeenCalledWith(true);
  });
});
