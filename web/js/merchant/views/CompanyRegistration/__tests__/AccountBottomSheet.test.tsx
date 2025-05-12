import React from 'react';
import { render, userEvent, screen } from 'test-utils';
import AccountBottomSheet from '../components/AccountBottomSheet';
import {
  MULTI_ACCOUNT_TITLE,
  MULTI_ACCOUNT_SUBTITLE,
} from 'merchant/views/CompanyRegistration/constant';
import * as analytics from '../analytics';

// Mock subcomponents
jest.mock('../analytics', () => ({
  trackEventOnCreateAccountPageView: jest.fn(),
}));

jest.mock('../components/MultiAccountBodyFooter', () => ({
  MultiAccountBody: ({ selected, setSelected }: any) => (
    <div data-testid="multi-account-body">Body - {selected}</div>
  ),
  MultiAccountFooter: ({ isLoading, handleUserAction }: any) => (
    <button onClick={handleUserAction} disabled={isLoading} data-testid="multi-account-footer">
      Submit
    </button>
  ),
}));

describe('AccountBottomSheet Component', () => {
  const mockCloseModal = jest.fn();
  const mockSetSelected = jest.fn();
  const mockHandleUserAction = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });
  const renderBottomSheet = () => {
    return render(
        <AccountBottomSheet
          isOpen={true}
          closeModal={mockCloseModal}
          selected="option-1"
          setSelected={mockSetSelected}
          isLoading={false}
          handleUserAction={mockHandleUserAction}
        />
    );
  };

  test('renders bottom sheet when open and tracks analytics', () => {
    renderBottomSheet();

    expect(screen.getByText(MULTI_ACCOUNT_TITLE)).toBeInTheDocument();
    expect(screen.getByText(MULTI_ACCOUNT_SUBTITLE)).toBeInTheDocument();
    expect(screen.getByTestId('multi-account-body')).toBeInTheDocument();
    expect(screen.getByTestId('multi-account-footer')).toBeInTheDocument();
    expect(analytics.trackEventOnCreateAccountPageView).toHaveBeenCalledTimes(1);
  });

  test('calls handleUserAction on submit click', async () => {
    renderBottomSheet();
    await userEvent.click(screen.getByTestId('multi-account-footer'));
    expect(mockHandleUserAction).toHaveBeenCalled();
  });
});
