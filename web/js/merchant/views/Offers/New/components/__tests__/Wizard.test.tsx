import React from 'react';
import { fireEvent, waitFor } from '@testing-library/react';

import { render } from 'test-utils';

import CreateOfferWizard from '../Wizard';

describe('CreateOfferWizard Component', () => {
  const mockTabsData = [
    { name: 'Tab 1', render: () => <div>Tab 1 Content</div> },
    { name: 'Tab 2', render: () => <div>Tab 2 Content</div> },
    { name: 'Tab 3', render: () => <div>Tab 3 Content</div> },
  ];

  const mockProps = {
    title: 'Test Title',
    validTabs: [true, true, true],
    tabsData: mockTabsData,
    isLoading: false,
    error: null,
    disabled: false,
    onSubmit: jest.fn(),
    submitBtnText: 'Submit',
    onChange: jest.fn(),
  };

  test('renders wizard with correct title and initial tab content', () => {
    const { getByText } = render(<CreateOfferWizard {...mockProps} />);
    expect(getByText('Test Title')).toBeInTheDocument();
    expect(getByText('Tab 1 Content')).toBeInTheDocument();
  });

  test('navigates to next and previous tabs correctly', async () => {
    const { getByText } = render(<CreateOfferWizard {...mockProps} />);

    fireEvent.click(getByText('Next'));
    await waitFor(() => expect(getByText('Tab 2 Content')).toBeInTheDocument());

    fireEvent.click(getByText('Previous'));
    await waitFor(() => expect(getByText('Tab 1 Content')).toBeInTheDocument());
  });

  test('calls onSubmit function when last tab submit button is clicked', async () => {
    const { getByText } = render(<CreateOfferWizard {...mockProps} />);

    fireEvent.click(getByText('Next'));
    fireEvent.click(getByText('Next'));
    fireEvent.click(getByText('Submit'));
    await waitFor(() => expect(mockProps.onSubmit).toHaveBeenCalledTimes(1));
  });

  test('displays loading spinner when isLoading is true', () => {
    const propsWithLoading = { ...mockProps, isLoading: true };
    const { getByTestId } = render(<CreateOfferWizard {...propsWithLoading} />);

    expect(getByTestId('spinner')).toBeInTheDocument();
  });

  test('displays error message when error prop is present', () => {
    const propsWithError = { ...mockProps, error: 'Test Error Message' };
    const { getByText } = render(<CreateOfferWizard {...propsWithError} />);

    expect(getByText('Test Error Message')).toBeInTheDocument();
  });
});
