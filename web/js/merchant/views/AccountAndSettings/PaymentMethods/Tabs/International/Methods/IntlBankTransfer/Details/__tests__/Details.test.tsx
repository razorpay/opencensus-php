import React from 'react';

import copyToClipboard from 'common/utils/copyToClipboard';
import { INTL_BANK_TRANSFER_LEAF } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/Details/__tests__/mocks/fixtures';
import {
  renderWithQueryClient,
  server,
  queryClient,
  virtualAccountsHandlers,
} from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/Details/__tests__/mocks/handlers';
import { screen, waitFor, userEvent } from 'test-utils';

import Details from '../index';

jest.mock('common/utils/copyToClipboard', () => jest.fn());

const onAccountActivate = jest.fn();

const render = (props = { isLoading: false }) =>
  renderWithQueryClient(
    <Details
      item={INTL_BANK_TRANSFER_LEAF[0]}
      isLoading={props.isLoading}
      onAccountActivate={onAccountActivate}
    />,
  );

describe('Test Details', () => {
  beforeAll(() => server.listen());
  beforeEach(() => {
    server.use(virtualAccountsHandlers.success());
  });
  afterEach(() => {
    server.resetHandlers();
    queryClient.clear();
  });
  afterAll(() => server.close());

  it('should render Details', () => {
    renderWithQueryClient(
      // @ts-expect-error testing null item
      <Details item={null} isLoading={false} onAccountActivate={onAccountActivate} />,
    );
    expect(screen.getByTestId('component-wrapper')).toBeInTheDocument();
    expect(screen.queryByText('Local Currency Bank Transfer')).not.toBeInTheDocument();
  });

  it('should render international bank transfer leaf list item', () => {
    render();

    expect(screen.getByText('Local Currency Bank Transfer')).toBeInTheDocument();
    INTL_BANK_TRANSFER_LEAF[0].list.forEach((item) => {
      expect(screen.getByTestId(item.slug)).toBeInTheDocument();
    });
  });

  it('should show account loaders', () => {
    render({ isLoading: true });

    expect(screen.getAllByTestId('account-loader')).toHaveLength(
      INTL_BANK_TRANSFER_LEAF[0].list.length,
    );
  });

  it('should render international bank transfer leaf list item with accounts', async () => {
    server.use(virtualAccountsHandlers.successAccounts());
    render();
    expect(screen.getByText('Local Currency Bank Transfer')).toBeInTheDocument();
    await waitFor(() => expect(screen.getByText('routing_code')).toBeInTheDocument());
  });

  it('should show alert component if account is deactivated', async () => {
    server.use(virtualAccountsHandlers.successAccountDeactivated());
    render();

    await waitFor(() => expect(screen.getByText(/To activate your account/)).toBeInTheDocument());
  });

  it('should request activation for single account', async () => {
    server.use(virtualAccountsHandlers.successAccounts());
    render();

    await waitFor(() =>
      expect(screen.getAllByTestId('request-activation')).toHaveLength(
        INTL_BANK_TRANSFER_LEAF[0].list.length - 1,
      ),
    );

    await userEvent.click(screen.getAllByTestId('request-activation')[0]);
    expect(onAccountActivate).toHaveBeenCalledWith(INTL_BANK_TRANSFER_LEAF[0].list[1].vaCurrency);
  });

  it('should copy account details to clipboard', async () => {
    server.use(virtualAccountsHandlers.successAccounts());
    render();

    await waitFor(() => expect(screen.getAllByTestId('copy-details')).toHaveLength(1));

    await userEvent.click(screen.getAllByTestId('copy-details')[0]);
    expect(copyToClipboard).toHaveBeenCalledWith(
      [
        'Routing Code = routing_code',
        'Routing Type = routing_type',
        'Account Number = account_number',
        'Beneficiary Name = beneficiary_name',
        'Beneficiary Bank Name = bank_name',
        'Beneficiary Address = bank_address',
      ].join('\n'),
    );
  });
});
