import React from 'react';
import { render, userEvent, screen } from 'test-utils';
import AccountModal from '../components/AccountModal';
import * as analytics from '../analytics';
import {
  MULTI_ACCOUNT_TITLE,
  MULTI_ACCOUNT_SUBTITLE,
} from 'merchant/views/CompanyRegistration/constant';

// Mock child components
jest.mock('../components/MultiAccountBodyFooter', () => ({
  MultiAccountBody: ({ selected, setSelected }: any) => (
    <div data-testid="multi-account-body" onClick={() => setSelected('option-2')}>
      Body content: {selected}
    </div>
  ),
  MultiAccountFooter: ({ isLoading, handleUserAction }: any) => (
    <button data-testid="multi-account-footer" disabled={isLoading} onClick={handleUserAction}>
      Continue
    </button>
  ),
}));

jest.mock('../analytics', () => ({
  trackEventOnCreateAccountPageView: jest.fn(),
}));

const mockCloseModal = jest.fn();
const mockSetSelected = jest.fn();
const mockHandleUserAction = jest.fn();

const renderComponent = () =>
  render(
      <AccountModal
        isOpen={true}
        closeModal={mockCloseModal}
        selected="option-1"
        setSelected={mockSetSelected}
        isLoading={false}
        handleUserAction={mockHandleUserAction}
      />
  );

describe('AccountModal Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('renders modal with correct content and tracks analytics', () => {
    renderComponent();

    expect(screen.getByText(MULTI_ACCOUNT_TITLE)).toBeInTheDocument();
    expect(screen.getByText(MULTI_ACCOUNT_SUBTITLE)).toBeInTheDocument();
    expect(screen.getByTestId('multi-account-body')).toBeInTheDocument();
    expect(screen.getByTestId('multi-account-footer')).toBeInTheDocument();

    expect(analytics.trackEventOnCreateAccountPageView).toHaveBeenCalledTimes(1);
  });

  test('handles footer button click', async () => {
    renderComponent();
    const button = screen.getByTestId('multi-account-footer');

    await userEvent.click(button);
    expect(mockHandleUserAction).toHaveBeenCalled();
  });

  test('handles body selection interaction', async () => {
    renderComponent();
    const body = screen.getByTestId('multi-account-body');

    await userEvent.click(body);
    expect(mockSetSelected).toHaveBeenCalledWith('option-2');
  });
});
