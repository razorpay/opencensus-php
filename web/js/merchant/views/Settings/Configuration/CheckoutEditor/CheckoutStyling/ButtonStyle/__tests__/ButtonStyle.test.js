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
    BORDER_STYLE: 'borderStyle',
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
    expect(screen.getByText(AVAILABLE_BORDER_STYLE.ROUNDED)).toBeInTheDocument();
    expect(screen.getByText(AVAILABLE_BORDER_STYLE.SHARP)).toBeInTheDocument();
  });

  it('marks the selected button style', () => {
    useCheckoutEditor.mockReturnValue({
      values: { [CHECKOUT_EDITOR_FIELDS.BORDER_STYLE]: AVAILABLE_BORDER_STYLE.SHARP },
      handleButtonStyleChange,
    });

    render(<ButtonStyle />);

    const sharpButton = screen.getByText(AVAILABLE_BORDER_STYLE.SHARP).closest('div');
    const expectedStyle = '1.5px solid hsla(211,20%,52%,0.18)';
    expect(sharpButton).toHaveStyle(`border: ${expectedStyle}`);
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
