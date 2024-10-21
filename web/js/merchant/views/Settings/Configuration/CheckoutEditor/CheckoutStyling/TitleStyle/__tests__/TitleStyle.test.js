import React from 'react';
import {
  AVAILABLE_TITLE_STYLE,
  DEFAULT_TITLE_TYPE,
  TITLE_DEFAULT_VALUE,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/constants/DefaultValue';
import LineItems from 'merchant/views/Settings/Configuration/components/Configuration/LineItems';
import { render, fireEvent, screen, waitFor } from 'test-utils';
import TitleStyle from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/TitleStyle/TitleStyle';
import RightChildren from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/TitleStyle/RightChildren';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context';

jest.mock('merchant/views/Settings/Configuration/components/Configuration/LineItems');
jest.mock('merchant/views/Settings/Configuration/CheckoutEditor/context', () => ({
  useCheckoutEditor: jest.fn(),
  CHECKOUT_EDITOR_FIELDS: {
    TITLE_STYLE: 'titleStyle',
  },
}));

describe('Title Style', () => {
  const handleTitleStyleChange = jest.fn();
  afterEach(() => {
    jest.clearAllMocks();
  });

  test('should renders TitleStyle component correctly', () => {
    LineItems.mockImplementation(() => <div>LineItems Mock</div>);

    render(<TitleStyle />);
    expect(LineItems).toHaveBeenCalledWith(
      expect.objectContaining({
        title: TITLE_DEFAULT_VALUE.title,
        subTitle: TITLE_DEFAULT_VALUE.subTitle,
        rightChildren: expect.anything(),
      }),
      {},
    );
  });

  it('should show ChooseTitleType modal when "Select" button is clicked', () => {
    render(<RightChildren />);

    useCheckoutEditor.mockReturnValue({
      values: { [CHECKOUT_EDITOR_FIELDS.TITLE_STYLE]: AVAILABLE_TITLE_STYLE.LOGO_ONLY },
      handleTitleStyleChange,
    });

    const selectButton = screen.getByTestId('title-style-select-button');
    expect(selectButton).toBeInTheDocument();
    fireEvent.click(selectButton);

    expect(screen.getByText('Choose a title style')).toBeInTheDocument();
    expect(
      screen.getByText('Select how you want your logo to be displayed in checkout'),
    ).toBeInTheDocument();
  });

  it('should render all type of title styles', () => {
    useCheckoutEditor.mockReturnValue({
      values: { [CHECKOUT_EDITOR_FIELDS.TITLE_STYLE]: AVAILABLE_TITLE_STYLE.WORDMARK },
      handleTitleStyleChange,
    });

    render(<RightChildren />);

    const selectButton = screen.getByTestId('title-style-select-button');
    expect(selectButton).toBeInTheDocument();
    fireEvent.click(selectButton);

    expect(screen.getByText(DEFAULT_TITLE_TYPE[2]?.title)).toBeInTheDocument();
    expect(screen.getByText(DEFAULT_TITLE_TYPE[2]?.description)).toBeInTheDocument();
  });

  it('should select the rendered title style', async () => {
    useCheckoutEditor.mockReturnValue({
      values: {
        [CHECKOUT_EDITOR_FIELDS.BORDER_STYLE]: AVAILABLE_TITLE_STYLE.SHARP,
        [CHECKOUT_EDITOR_FIELDS.TITLE_STYLE]: AVAILABLE_TITLE_STYLE.WORDMARK,
      },
      handleTitleStyleChange,
    });

    render(<RightChildren />);

    const selectButton = screen.getByTestId('title-style-select-button');
    expect(selectButton).toBeInTheDocument();
    fireEvent.click(selectButton);

    const wordWrapTitle = screen.getByText(DEFAULT_TITLE_TYPE[2]?.title).closest('div');
    fireEvent.click(wordWrapTitle);

    await waitFor(() => {
      expect(handleTitleStyleChange).toHaveBeenCalledWith(DEFAULT_TITLE_TYPE[2]?.value);
    });
  });
});
