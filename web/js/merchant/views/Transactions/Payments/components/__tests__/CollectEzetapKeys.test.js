import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';
import { createMemoryHistory } from 'history';
import { storeWithInitialState } from 'merchant/store';
import * as Ajax from 'merchant/utils/ajax';
import EzetapDetailsForm from 'merchant/views/Transactions/Payments/components/CollectEzetapKeys';
import React from 'react';
import { Provider } from 'react-redux';
import { Router } from 'react-router-dom';
import { fireEvent, render, screen, waitFor } from 'test-utils';

const openRefundModalMock = jest.fn();

const renderApp = () => {
  const history = createMemoryHistory();

  return render(
    <Provider store={storeWithInitialState({})}>
      <BladeProvider themeTokens={paymentTheme}>
        <Router history={history}>
          <EzetapDetailsForm openRefundModal={openRefundModalMock} />
        </Router>
      </BladeProvider>
    </Provider>,
  );
};

describe('EzetapDetailsForm', () => {
  const merchantFetchSpyOn = jest.spyOn(Ajax, 'merchantFetch');

  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('renders the component', () => {
    renderApp();

    const usernameInput = screen.getByLabelText('UserName');
    const appKeyInput = screen.getByLabelText('App Key');
    const submitButton = screen.getByText('Submit');

    expect(usernameInput).toBeInTheDocument();
    expect(appKeyInput).toBeInTheDocument();
    expect(submitButton).toBeInTheDocument();
  });

  test('updates data when inputs change', () => {
    renderApp();

    const usernameInput = screen.getByLabelText('UserName');
    const appKeyInput = screen.getByLabelText('App Key');

    fireEvent.change(usernameInput, { target: { value: 'testUser' } });
    fireEvent.change(appKeyInput, { target: { value: 'testAppKey' } });

    expect(usernameInput.value).toBe('testUser');
    expect(appKeyInput.value).toBe('testAppKey');
  });

  test('calls postEzetapData and open refund modal when keys are submitted', async () => {
    merchantFetchSpyOn.mockImplementation(() => Promise.resolve({ data: { success: true } }));
    renderApp();
    const usernameInput = screen.getByLabelText('UserName');
    const appKeyInput = screen.getByLabelText('App Key');

    fireEvent.change(usernameInput, { target: { value: 'testUser' } });
    fireEvent.change(appKeyInput, { target: { value: 'testAppKey' } });

    const submitButton = screen.getByText('Submit');
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(merchantFetchSpyOn).toHaveBeenCalled();
      expect(openRefundModalMock).toHaveBeenCalledTimes(1);
    });
  });
  test('should not open refund modal if saving keys gets failed', async () => {
    merchantFetchSpyOn.mockImplementation(() => Promise.reject());
    renderApp();
    const submitButton = screen.getByText('Submit');
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(merchantFetchSpyOn).toHaveBeenCalled();
      expect(openRefundModalMock).not.toHaveBeenCalled();
    });
  });
});
