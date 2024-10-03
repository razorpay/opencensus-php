import React from 'react';

import LanguageSettings from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/LanguageSettings/LanguageSettings';
import RightChildren from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/LanguageSettings/RightChildren';
import {
  LANGUAGE_SETTINGS_DEFAULT_VALUE,
  LANGUAGE_OPTIONS,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/constants/DefaultValue';
import { CHECKOUT_EDITOR_FIELDS } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';
import LineItems from 'merchant/views/Settings/Configuration/components/Configuration/LineItems';
import { render, fireEvent, screen, waitFor } from 'test-utils';

jest.mock('merchant/views/Settings/Configuration/components/Configuration/LineItems');
jest.mock('merchant/views/Settings/Configuration/CheckoutEditor/context/index', () => ({
  useCheckoutEditor: jest.fn(),
}));

describe('LanguageSettings and RightChildren', () => {
  const handleLocaleChange = jest.fn();

  afterEach(() => {
    jest.clearAllMocks();
  });

  test('renders LanguageSettings component correctly', () => {
    LineItems.mockImplementation(() => <div>LineItems Mock</div>);

    useCheckoutEditor.mockReturnValue({
      values: { [CHECKOUT_EDITOR_FIELDS.LOCALE]: { languageCode: LANGUAGE_OPTIONS[0].code } },
      handleLocaleChange,
    });

    render(<LanguageSettings />);

    expect(LineItems).toHaveBeenCalledWith(
      expect.objectContaining({
        title: LANGUAGE_SETTINGS_DEFAULT_VALUE.title,
        subTitle: LANGUAGE_SETTINGS_DEFAULT_VALUE.subTitle,
        rightChildren: expect.anything(),
      }),
      {},
    );
  });

  test('renders RightChildren with correct language name', () => {
    useCheckoutEditor.mockReturnValue({
      values: { [CHECKOUT_EDITOR_FIELDS.LOCALE]: { languageCode: LANGUAGE_OPTIONS[0].code } },
      handleLocaleChange,
    });

    const languageName = LANGUAGE_OPTIONS[0].name;
    render(<RightChildren />);

    const elements = screen.getAllByText(languageName);
    expect(elements.length).toBeGreaterThan(0);
  });

  test('calls handleLocaleChange on selecting a language', async () => {
    useCheckoutEditor.mockReturnValue({
      values: { locale: { languageCode: LANGUAGE_OPTIONS[0].code } },
      handleLocaleChange,
    });

    render(<RightChildren />);

    fireEvent.click(screen.getByTestId('language-settings-checkout-button'));
    fireEvent.click(screen.getByTestId(`language-settings-checkout-${LANGUAGE_OPTIONS[1].name}`));

    await waitFor(() => {
      expect(handleLocaleChange).toHaveBeenCalledWith(LANGUAGE_OPTIONS[1].code);
    });
  });
});
