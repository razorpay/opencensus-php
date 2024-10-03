import React from 'react';

import MandatorySummaryPage from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/MandatorySummaryPage';
import { MANDATORT_SUMMARY_DEFAULT_VALUE } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/constants/DefaultValue';
import { CHECKOUT_EDITOR_FIELDS } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';
import { render, screen, fireEvent } from 'test-utils';

jest.mock('merchant/views/Settings/Configuration/CheckoutEditor/context/index', () => ({
  useCheckoutEditor: jest.fn(),
}));

describe('MandatorySummaryPage', () => {
  const handleMandatorySummaryPageToggle = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders the MandatorySummaryPage component with the correct title and subtitle', () => {
    useCheckoutEditor.mockReturnValue({
      values: {
        [CHECKOUT_EDITOR_FIELDS.MANDATORY_SUMMARY_PAGE]: false,
      },
      handleMandatorySummaryPageToggle,
    });

    render(<MandatorySummaryPage />);

    expect(screen.getByText(MANDATORT_SUMMARY_DEFAULT_VALUE.title)).toBeInTheDocument();
    expect(screen.getByText(MANDATORT_SUMMARY_DEFAULT_VALUE.subTitle)).toBeInTheDocument();
  });

  it('renders the toggle with the correct checked state', () => {
    useCheckoutEditor.mockReturnValue({
      values: {
        [CHECKOUT_EDITOR_FIELDS.MANDATORY_SUMMARY_PAGE]: true,
      },
      handleMandatorySummaryPageToggle,
    });

    render(<MandatorySummaryPage />);

    const toggle = screen.getByLabelText(`enable-${CHECKOUT_EDITOR_FIELDS.MANDATORY_SUMMARY_PAGE}`);
    expect(toggle).toBeChecked();
  });

  it('calls handleMandatorySummaryPageToggle when the toggle is clicked', () => {
    useCheckoutEditor.mockReturnValue({
      values: {
        [CHECKOUT_EDITOR_FIELDS.MANDATORY_SUMMARY_PAGE]: false,
      },
      handleMandatorySummaryPageToggle,
    });

    render(<MandatorySummaryPage />);

    const toggle = screen.getByLabelText(`enable-${CHECKOUT_EDITOR_FIELDS.MANDATORY_SUMMARY_PAGE}`);
    fireEvent.click(toggle);
    expect(handleMandatorySummaryPageToggle).toHaveBeenCalledWith(true);
  });
});
