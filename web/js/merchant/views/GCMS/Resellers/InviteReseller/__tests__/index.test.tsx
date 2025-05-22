import React from 'react';
import { render, screen, waitFor, userEvent, fireEvent } from 'test-utils';
import { useMutation } from '@tanstack/react-query';
import InviteReseller from '../index';
import { inviteReseller } from '../../queries';

jest.mock('@tanstack/react-query', () => ({
  ...jest.requireActual('@tanstack/react-query'),
  useMutation: jest.fn(),
}));

jest.mock('../../queries', () => ({
  inviteReseller: jest.fn(),
}));

describe('<InviteReseller />', () => {
  const mockCloseModal = jest.fn();
  const mockRefetchResellers = jest.fn();

  const defaultProps = {
    closeModal: mockCloseModal,
    refetchResellers: mockRefetchResellers,
  };

  beforeEach(() => {
    jest.clearAllMocks();
    (useMutation as jest.Mock).mockReturnValue({
      mutateAsync: jest.fn(),
      isLoading: false,
      error: null,
      isError: false,
      reset: jest.fn(),
    });
  });

  test('should render invite reseller form', () => {
    render(<InviteReseller {...defaultProps} />);

    const submitButton = screen.getByRole('button', { name: 'Create Reseller' });

    expect(screen.getByTestId('reseller-name')).toBeInTheDocument();
    expect(screen.getByTestId('reseller-email')).toBeInTheDocument();
    expect(screen.getByTestId('phone-number')).toBeInTheDocument();
    expect(submitButton).toBeDisabled();
  });

  test('should validate form details', async () => {
    const mockMutateAsync = jest.fn().mockResolvedValue({});
    (useMutation as jest.Mock).mockReturnValue({
      mutateAsync: mockMutateAsync,
      isLoading: false,
      error: null,
      isError: false,
      reset: jest.fn(),
    });

    render(<InviteReseller {...defaultProps} />);

    await fireEvent.change(screen.getByPlaceholderText('Enter your reseller name'), {
      target: { value: 'Reseller name' },
    });
    await fireEvent.change(screen.getByPlaceholderText('Enter email to send invite link'), {
      target: { value: 'reseller@gmail.com' },
    });
    await fireEvent.change(screen.getByPlaceholderText('0000000000'), {
      target: { value: '9876543210' },
    });

    const submitButton = screen.getByRole('button', { name: 'Create Reseller' });

    expect(submitButton).not.toBeDisabled();
  });
});
