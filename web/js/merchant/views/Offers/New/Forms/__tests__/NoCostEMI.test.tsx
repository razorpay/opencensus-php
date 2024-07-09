import React from 'react';
import { waitFor, screen } from '@testing-library/react';

import * as Ajax from 'merchant/utils/ajax';
import { render } from 'test-utils';

import NoCostEMIForm from '../NoCostEMI';

const mockAbExperiments = { Low_cost_offer: {} };

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
  withSplitzService: (Component) => (props) =>
    <Component {...props} splitz={{ abExperiments: mockAbExperiments }} />,
}));

const mockMerchantFetchResponse = {
  data: {
    emi_plans: { plan1: {}, plan2: {} },
    emi_options: { option1: {}, option2: {} },
  },
};

const renderNoCostEMIForm = (props = {}) => {
  return render(<NoCostEMIForm {...props} />);
};

describe('NoCostEMIForm Component', () => {
  const merchantFetchSpyOn = jest.spyOn(Ajax, 'merchantFetch');
  beforeEach(() => {
    merchantFetchSpyOn.mockResolvedValue(mockMerchantFetchResponse);
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  test('renders form with correct sections', async () => {
    renderNoCostEMIForm({ errors: {}, touched: {}, values: {}, setFieldValue: jest.fn() });

    await waitFor(() => {
      expect(screen.getAllByText('Description').length).toBe(2);
    });

    expect(screen.getByText('Discount type')).toBeInTheDocument();
    expect(screen.getByText('Applicable On')).toBeInTheDocument();
    expect(screen.getByText('Offer Validity')).toBeInTheDocument();
    expect(screen.getByText('Overview')).toBeInTheDocument();
  });

  test('next button should show', async () => {
    const mockOnNext = jest.fn();
    renderNoCostEMIForm({
      onNext: mockOnNext,
      errors: {},
      touched: {},
      values: {},
      setFieldValue: jest.fn(),
    });

    await waitFor(() => {
      expect(screen.getByText('Next')).toBeInTheDocument();
    });
  });

  test('handles API errors gracefully', async () => {
    const merchantFetchSpyOn = jest.spyOn(Ajax, 'merchantFetch');
    merchantFetchSpyOn.mockRejectedValue({
      errors: [{ message: 'Some network error has occurred' }],
    });

    renderNoCostEMIForm({ errors: {}, touched: {}, values: {}, setFieldValue: jest.fn() });

    await waitFor(() => {
      expect(screen.getByText(/some network error has occurred/i)).toBeInTheDocument();
    });
  });
});
