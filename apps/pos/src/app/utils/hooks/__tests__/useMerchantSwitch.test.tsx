import React from 'react';
import useMerchantSwitch from '../useMerchantSwitch';
import { switchMerchantHandler } from 'apps/pos/src/services/mocks/handlers/switchMerchant';
import { render, screen, server, userEvent, waitFor } from 'apps/pos/src/services/test/test-utils';
import * as apis from 'apps/pos/src/app/apis/SalesAssistedOnboarding';

const someRandomFn = jest.fn();
const someRandomErrorFn = jest.fn();

const TestApp = ({ mid }: { mid: string }) => {
  const { isLoading, handleSwitchMerchant } = useMerchantSwitch({
    onSuccess: someRandomFn,
    onError: someRandomErrorFn,
  });
  return (
    <div>
      {isLoading ? <h1>Loading...</h1> : null}
      <button onClick={() => handleSwitchMerchant(mid)}>Switch Merchant</button>
    </div>
  );
};

describe('useMerchantSwitch', () => {
  test('should call switch merchant with correct payload when called handleSwitch with onSuccess callback on resolve', async () => {
    server.use(switchMerchantHandler('success'));
    const apiSpy = jest.spyOn(apis, 'switchMerchant');
    render(<TestApp mid="abc_123" />);
    await userEvent.click(screen.getByRole('button'));
    expect(screen.getByText('Loading...')).toBeInTheDocument();
    expect(apiSpy).toHaveBeenCalledWith({ merchantId: 'abc_123' });
    await waitFor(() => {
      expect(someRandomFn).toHaveBeenCalled();
    });
  });

  test('should call switch merchant with correct payload when called handleSwitch with onError callback on reject', async () => {
    server.use(switchMerchantHandler('failure'));
    const apiSpy = jest.spyOn(apis, 'switchMerchant');
    render(<TestApp mid="merchant_123" />);
    await userEvent.click(screen.getByRole('button'));
    expect(screen.getByText('Loading...')).toBeInTheDocument();
    expect(apiSpy).toHaveBeenCalledWith({ merchantId: 'merchant_123' });
    await waitFor(() => {
      expect(someRandomErrorFn).toHaveBeenCalled();
    });
  });
});
