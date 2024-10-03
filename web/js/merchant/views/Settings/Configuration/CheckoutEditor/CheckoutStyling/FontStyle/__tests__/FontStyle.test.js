import React from 'react';

import FontStyle from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/FontStyle/FontStyle';
import RightChildren from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/FontStyle/RightChildren';
import {
  FONT_STYLE_DEFAULT_VALUE,
  FONT_OPTIONS,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/constants/DefaultValue';
import LineItems from 'merchant/views/Settings/Configuration/components/Configuration/LineItems';
import { render, fireEvent, screen, waitFor } from 'test-utils';

import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context';
import { CHECKOUT_EDITOR_FIELDS } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';

jest.mock('merchant/views/Settings/Configuration/components/Configuration/LineItems');
jest.mock('merchant/views/Settings/Configuration/CheckoutEditor/context', () => ({
  useCheckoutEditor: jest.fn(),
  CHECKOUT_EDITOR_FIELDS: {
    FONT_STYLE: 'fontStyle',
  },
}));

describe('FontStyle and RightChildren', () => {
  const handleFontStyleChange = jest.fn();

  afterEach(() => {
    jest.clearAllMocks();
  });

  test('renders FontStyle component correctly', () => {
    LineItems.mockImplementation(() => <div>LineItems Mock</div>);

    render(<FontStyle />);
    expect(LineItems).toHaveBeenCalledWith(
      expect.objectContaining({
        title: FONT_STYLE_DEFAULT_VALUE.title,
        subTitle: FONT_STYLE_DEFAULT_VALUE.subTitle,
        rightChildren: expect.anything(),
      }),
      {},
    );
  });

  test('renders RightChildren with correct font name', () => {
    useCheckoutEditor.mockReturnValue({
      values: { [CHECKOUT_EDITOR_FIELDS.FONT_FAMILY]: FONT_OPTIONS[0].code },
    });

    const fontName = FONT_OPTIONS[0].name;

    render(<RightChildren />);

    const elements = screen.getAllByText(fontName);
    expect(elements.length).toBeGreaterThan(0);
  });

  test('calls handleFontStyleChange on selecting a font', async () => {
    useCheckoutEditor.mockReturnValue({
      values: { [CHECKOUT_EDITOR_FIELDS.FONT_FAMILY]: FONT_OPTIONS[0].code },
      handleFontStyleChange,
    });

    render(<RightChildren />);
    fireEvent.click(screen.getByTestId('font-style-checkout-button'));
    fireEvent.click(screen.getByTestId(`font-style-checkout-${FONT_OPTIONS[1].name}`));

    await waitFor(() => {
      expect(handleFontStyleChange).toHaveBeenCalledWith(FONT_OPTIONS[1].code);
    });
  });
});
