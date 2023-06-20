import React from 'react';
import FeeBearerSelfserve from 'merchant/views/Settings/Configuration/FeeBearerSelfserve';
import { getInitialReduxState } from 'merchant/views/mocks/fixtures';
import { render, screen, waitFor, userEvent } from 'test-utils';
import * as utils from 'common/utils/rzp-utils';

const defaultInitialReduxState = getInitialReduxState({
  isQRCodeProductEnabled: true,
  isVirtualAccountsEnabled: true,
  isMarketplaceEnabled: true,
});

describe('Fee bearer', () => {
  it('should call mockSupportTicket when clicking on "support ticket" link button when QR, SC & Route enabled', async () => {
    const openModalSpy = jest.spyOn(utils, 'openTicketModal');

    render(<FeeBearerSelfserve />, {
      initialState: defaultInitialReduxState,
    });
    expect(screen.getByText('Customer pays the fee')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'support ticket.' })).toBeInTheDocument();
    userEvent.click(screen.getByRole('button', { name: 'support ticket.' }));
    await waitFor(() => expect(openModalSpy).toHaveBeenCalled());
  });

  it('should render proper not supported case for customer fee bearer with QR, SC & Route enabled', () => {
    render(<FeeBearerSelfserve />, {
      initialState: defaultInitialReduxState,
    });
    expect(screen.getByText('Customer pays the fee')).toBeInTheDocument();
    expect(screen.getByText('NOT SUPPORTED')).toBeInTheDocument();
    expect(
      screen.getByText(
        'This feature is not supported for merchants using QR, Smart Collect and Route',
        { exact: false }, //substring match
      ),
    ).toBeInTheDocument();
  });

  it('should render proper not supported case for customer fee bearer with QR, SC disabled & Route enabled', () => {
    const initialReduxState = getInitialReduxState({
      isQRCodeProductEnabled: false,
      isVirtualAccountsEnabled: false,
      isMarketplaceEnabled: true,
    });
    render(<FeeBearerSelfserve />, {
      initialState: initialReduxState,
    });
    expect(screen.getByText('Customer pays the fee')).toBeInTheDocument();
    expect(screen.getByText('NOT SUPPORTED')).toBeInTheDocument();
    expect(
      screen.getByText(
        'This feature is not supported for merchants using Route',
        { exact: false }, //substring match
      ),
    ).toBeInTheDocument();
  });

  it('should render proper not supported case for customer fee bearer with QR disabled, SC enabled & Route disabled', () => {
    const initialReduxState = getInitialReduxState({
      isQRCodeProductEnabled: false,
      isVirtualAccountsEnabled: true,
      isMarketplaceEnabled: false,
    });
    render(<FeeBearerSelfserve />, {
      initialState: initialReduxState,
    });
    expect(screen.getByText('Customer pays the fee')).toBeInTheDocument();
    expect(screen.getByText('NOT SUPPORTED')).toBeInTheDocument();
    expect(
      screen.getByText(
        'This feature is not supported for merchants using Smart collect',
        { exact: false }, //substring match
      ),
    ).toBeInTheDocument();
  });

  it('should render proper not supported case for customer fee bearer with QR enabled, SC & Route disabled', () => {
    const initialReduxState = getInitialReduxState({
      isQRCodeProductEnabled: true,
      isVirtualAccountsEnabled: false,
      isMarketplaceEnabled: false,
    });
    render(<FeeBearerSelfserve />, {
      initialState: initialReduxState,
    });
    expect(screen.getByText('Customer pays the fee')).toBeInTheDocument();
    expect(screen.getByText('NOT SUPPORTED')).toBeInTheDocument();
    expect(
      screen.getByText(
        'This feature is not supported for merchants using QR code',
        { exact: false }, //substring match
      ),
    ).toBeInTheDocument();
  });
});
