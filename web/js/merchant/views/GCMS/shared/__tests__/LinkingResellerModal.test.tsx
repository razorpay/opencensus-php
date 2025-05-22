import React from 'react';
import { render, screen, waitFor, userEvent } from 'test-utils';
import { useMutation } from '@tanstack/react-query';
import LinkingResellerModal from '../LinkingResellerModal';
import { linkResellerToProgram } from '../../Programs/queries';
import { RESELLER_SERVICE } from '../../Resellers/ResellerTable/constants';
import ResellersTable from '../../Resellers/ResellerTable';
import Resellers from '../../Resellers';

jest.mock('@tanstack/react-query', () => ({
  ...jest.requireActual('@tanstack/react-query'),
  useMutation: jest.fn(),
}));

jest.mock('../../Programs/queries', () => ({
  linkResellerToProgram: jest.fn(),
}));

jest.mock('../../Resellers/ResellerTable',() => jest.fn(({onSelection}) =>(
    <div data-testid="reseller-table">
      <button onClick={() => onSelection(['reseller1', 'reseller2'])}>Select Resellers</button>
    </div>
  )));

describe('<LinkingResellerModal />', () => {
  const mockCloseModal = jest.fn();
  const mockRefetchQuery = jest.fn();
  const defaultProps = {
    program: {
      id: 'program1',
      name: 'Test Program',
      policies: {
        min_discount_percent: 10
      }
    },
    mode: 'test',
    merchantId: 'merchant1',
    closeModal: mockCloseModal,
    refetchQuery: mockRefetchQuery
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('renders modal with correct title', () => {
    (useMutation as jest.Mock).mockReturnValue({
      mutateAsync: jest.fn(),
      isLoading: false,
      error: null,
      isError: false
    });

    render(<LinkingResellerModal {...defaultProps} />);
    
    expect(screen.getByText('Add reseller to Test Program')).toBeInTheDocument();
    expect(screen.getByTestId('reseller-table')).toBeInTheDocument();
  });

  test('Empty selection list prevents adding resellers and closing modal', async () => {
    const mockMutateAsync = jest.fn().mockResolvedValue(undefined);
    (useMutation as jest.Mock).mockReturnValue({
      mutateAsync: mockMutateAsync,
      isLoading: false,
      error: null,
      isError: false
    });

    (ResellersTable as jest.Mock).mockImplementationOnce(({ onSelection }) => (
      <div data-testid="reseller-table">
        <button onClick={() => onSelection([])}>Select Resellers</button>
      </div>
    ))

    render(<LinkingResellerModal {...defaultProps} />);
    
    // Select resellers
    await userEvent.click(screen.getByText('Select Resellers'));
    
    // Click add button
    await userEvent.click(screen.getByText('Add Reseller'));

    await waitFor(() => {
      expect(mockMutateAsync).not.toHaveBeenCalled()
      expect(mockCloseModal).not.toHaveBeenCalled();
      expect(mockRefetchQuery).not.toHaveBeenCalled();
    });
  });

  test('handles loading state', () => {
    (useMutation as jest.Mock).mockReturnValue({
      mutateAsync: jest.fn(),
      isLoading: true,
      error: null,
      isError: false
    });

    (ResellersTable as jest.Mock).mockImplementationOnce(({ onSelection }) => (
      <div data-testid="reseller-table">
        <button onClick={() => onSelection([])}>Select Resellers</button>
      </div>
    ))

    render(<LinkingResellerModal {...defaultProps} />);
    
    const addButton = screen.getByRole('button', {name:'Add Reseller'});
    expect(addButton).toBeDisabled();
  });


});