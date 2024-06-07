import React from 'react';
import { screen, waitFor, fireEvent } from '@testing-library/react';

import * as Ajax from 'merchant/utils/ajax';
import { render } from 'test-utils';

import NoCostEmiMethods from '../NoCostEmiMethods';

describe('NoCostEmiMethods Component', () => {
  const mockProps = {
    issuer: null,
    emiDurations: [3, 6, 9],
    minAmount: 1000,
    isLoading: false,
    issuers: ['issuer1', 'issuer2'],
    getFormOnChangeHandler: jest.fn(() => jest.fn()),
    onSelectIssuer: jest.fn(),
    onClose: jest.fn(),
  };
  beforeEach(() => {
    jest.clearAllMocks();
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  test('fetches emi plans on mount', async () => {
    const merchantFetchSpyOn = jest.spyOn(Ajax, 'merchantFetch');
    render(<NoCostEmiMethods {...mockProps} />);
    await waitFor(() => {
      expect(merchantFetchSpyOn).toHaveBeenCalledWith('merchant/methods');
    });
  });

  test('fetches EMI options and updates state', async () => {
    render(<NoCostEmiMethods {...mockProps} />);
    await waitFor(() => {
      expect(screen.getByText('Issuer')).toBeInTheDocument();
      expect(screen.getByText('Select Issuer')).toBeInTheDocument();
      expect(screen.getByText('Please fill out this field')).toBeInTheDocument();
    });
  });

  test('renders loader when isLoading is true', async () => {
    const customProps = { ...mockProps, isLoading: true };
    render(<NoCostEmiMethods {...customProps} />);
    await waitFor(() => {
      expect(
        screen.getByTestId('component-wrapper').querySelector('.no-cost-emi-loader'),
      ).toBeInTheDocument();
    });
  });

  test('renders footnote with correct text', async () => {
    render(<NoCostEmiMethods {...mockProps} />);
    await waitFor(() => {
      const footnote = screen.getByText(/Only banks with minimum EMI order amount of/);
      expect(footnote).toBeInTheDocument();
      expect(footnote.closest('ul')).toBeInTheDocument();
    });
  });

  test('renders DocLink with correct href', async () => {
    render(<NoCostEmiMethods {...mockProps} />);
    await waitFor(() => {
      expect(screen.getByText('here')).toBeInTheDocument();
      expect(screen.getByText('here').closest('a')).toHaveAttribute(
        'href',
        'https://razorpay.com/docs/offers/no-cost-emi/',
      );
    });
  });

  test('does not render EMI options if none are available', async () => {
    jest
      .spyOn(Ajax, 'merchantFetch')
      .mockResolvedValue({ data: { emi_plans: {}, emi_options: {} } });
    render(<NoCostEmiMethods {...mockProps} />);
    await waitFor(() => {
      expect(screen.queryByText('EMI Tenure')).not.toBeInTheDocument();
    });
  });
});
