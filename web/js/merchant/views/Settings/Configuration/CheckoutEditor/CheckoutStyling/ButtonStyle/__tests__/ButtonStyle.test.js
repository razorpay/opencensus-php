import React from 'react';

import ButtonStyle from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/ButtonStyle/ButtonStyle';
import { AVAILABLE_BORDER_STYLE } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/constants/DefaultValue';
import {
  useCheckoutEditor,
  CHECKOUT_EDITOR_FIELDS,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';
import { render, screen, fireEvent } from 'test-utils';

jest.mock('merchant/views/Settings/Configuration/CheckoutEditor/context', () => ({
  useCheckoutEditor: jest.fn(),
  CHECKOUT_EDITOR_FIELDS: {
    BORDER_STYLE: 'borderRadius',
  },
}));

describe('ButtonStyle', () => {
  const handleButtonStyleChange = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders the ButtonStyle component with the correct title', () => {
    useCheckoutEditor.mockReturnValue({
      values: { [CHECKOUT_EDITOR_FIELDS.BORDER_STYLE]: AVAILABLE_BORDER_STYLE.ROUNDED },
      handleButtonStyleChange,
    });

    render(<ButtonStyle />);

    expect(screen.getByText('Border style')).toBeInTheDocument();
  });

  it('renders the available button styles', () => {
    useCheckoutEditor.mockReturnValue({
      values: { [CHECKOUT_EDITOR_FIELDS.BORDER_STYLE]: AVAILABLE_BORDER_STYLE.ROUNDED },
      handleButtonStyleChange,
    });

    render(<ButtonStyle />);
    const sharpButtonRadio = screen.getByDisplayValue(AVAILABLE_BORDER_STYLE.SHARP);
    const roundButtonRadio = screen.getByDisplayValue(AVAILABLE_BORDER_STYLE.ROUNDED);
    expect(sharpButtonRadio).toBeInTheDocument();
    expect(roundButtonRadio).toBeInTheDocument();
  });

  it('marks the selected button style', () => {
    const mockValues = { [CHECKOUT_EDITOR_FIELDS.BORDER_STYLE]: AVAILABLE_BORDER_STYLE.SHARP };
    useCheckoutEditor.mockReturnValue({
      values: mockValues,
      handleButtonStyleChange,
    });

    render(<ButtonStyle />);
    const sharpButtonRadio = screen.getByDisplayValue(AVAILABLE_BORDER_STYLE.SHARP);
    expect(sharpButtonRadio).toHaveAttribute('aria-checked', 'true');
  });

  it('calls handleButtonStyleChange when a button is clicked', () => {
    useCheckoutEditor.mockReturnValue({
      values: { [CHECKOUT_EDITOR_FIELDS.BORDER_STYLE]: AVAILABLE_BORDER_STYLE.ROUNDED },
      handleButtonStyleChange,
    });

    render(<ButtonStyle />);

    const pillButton = screen.getByText(AVAILABLE_BORDER_STYLE.SHARP);
    fireEvent.click(pillButton);

    expect(handleButtonStyleChange).toHaveBeenCalledWith(AVAILABLE_BORDER_STYLE.SHARP);
  });
});
