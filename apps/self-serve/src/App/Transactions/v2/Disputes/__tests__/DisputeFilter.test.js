import React from 'react';
import DisputeFilter from 'apps/self-serve/src/App/Transactions/v2/Disputes/components/DisputeFilter';
import {
  FILTER_TITLE,
  disputePhaseMap,
  paymentTypeMap,
} from 'apps/self-serve/src/App/Transactions/v2/Disputes/constants';
import { screen, waitFor, userEvent, render } from 'apps/self-serve/src/services/test/test-utils';
import 'jest-location-mock';

describe('DisputeFilter', () => {
  const mockOnDismiss = jest.fn();
  const mockOnApply = jest.fn();

  const defaultProps = {
    isOpen: true,
    onDismiss: mockOnDismiss,
    onFilterApply: mockOnApply,
    selectedFilters: {},
  };

  const selectedPhaseFilter = Object.keys(disputePhaseMap)[0];

  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('renders modal when isOpen is true', () => {
    render(<DisputeFilter {...defaultProps} />);
    expect(screen.getByText('Filter')).toBeInTheDocument();
  });

  test('does not render modal when isOpen is false', () => {
    render(<DisputeFilter {...{ ...defaultProps, isOpen: false }} />);
    expect(screen.queryByText('Filter')).not.toBeInTheDocument();
  });

  test('displays initial selected filters correctly', () => {
    const initialFilters = { [FILTER_TITLE.PHASES_OF_DISPUTE]: selectedPhaseFilter };
    render(<DisputeFilter {...{ ...defaultProps, selectedFilters: initialFilters }} />);
    expect(screen.getByText(selectedPhaseFilter)).toBeInTheDocument();
  });

  test('calls onFilterApply with selected filters', async () => {
    const selectedFilters = {
      [FILTER_TITLE.PHASES_OF_DISPUTE]: selectedPhaseFilter,
      [FILTER_TITLE.PAYMENT_TYPE]: Object.keys(paymentTypeMap)[0],
    };
    render(<DisputeFilter {...{ ...defaultProps, selectedFilters }} />);
    await userEvent.click(screen.getByText('Apply'));
    await waitFor(() => {
      expect(mockOnApply).toHaveBeenCalledWith(selectedFilters);
    });
  });

  test('calls onDismiss and resets filters on Cancel', async () => {
    const initialFilters = { [FILTER_TITLE.PHASES_OF_DISPUTE]: selectedPhaseFilter };

    render(<DisputeFilter {...{ ...defaultProps, selectedFilters: initialFilters }} />);
    await userEvent.click(screen.getByText('Cancel'));
    await waitFor(() => {
      expect(mockOnDismiss).toHaveBeenCalled();
      expect(mockOnApply).not.toHaveBeenCalled();
    });
  });
});
