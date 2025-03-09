import React from 'react';
import { render, screen, waitFor, userEvent, act } from 'test-utils';

import FilterModal from 'merchant/views/Reconciliations/SplitScreen/FilterModal';

const mockSetIsFilterModalOpen = jest.fn();
const mockSetReconFilter = jest.fn();

const filterModalProps = {
  isFilterModalOpen: true,
  setIsFilterModalOpen: mockSetIsFilterModalOpen,
  reconFilter: { 'Recon Status': '', 'Recon Remarks': '' },
  setReconFilter: mockSetReconFilter,
};

const renderFilterModal = (props) => {
  return render(<FilterModal {...props} />);
};

describe('FilterModal', () => {
  test('should render the FilterModal without throwing errors', async () => {
    await waitFor(() => {
      expect(() => renderFilterModal(filterModalProps)).not.toThrow();
    });
  });

  test('should display the correct title in the modal', async () => {
    renderFilterModal(filterModalProps);
    await waitFor(() => {
      expect(screen.getByText(/advance filters/i)).toBeInTheDocument();
    });
  });

  test('should close the modal on Cancel button click', async () => {
    renderFilterModal(filterModalProps);

    const cancelButton = screen.getByRole('button', { name: /cancel/i });
    act(() => {
      userEvent.click(cancelButton);
    });

    await waitFor(() => {
      expect(mockSetIsFilterModalOpen).toHaveBeenCalledWith(false);
    });
  });

  test('should apply the filters and close the modal on Apply button click', async () => {
    renderFilterModal(filterModalProps);

    const applyButton = screen.getByRole('button', { name: /apply/i });
    act(() => {
      userEvent.click(applyButton);
    });

    await waitFor(() => {
      expect(mockSetReconFilter).toHaveBeenCalledWith(expect.any(Function));
      expect(mockSetIsFilterModalOpen).toHaveBeenCalledWith(false);
    });
  });
});
