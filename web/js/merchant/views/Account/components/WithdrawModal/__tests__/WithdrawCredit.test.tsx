import React from 'react';
import { render, screen, fireEvent, waitFor, userEvent } from 'common/services/test/test-utils';
import WithdrawCredit from '../WithdrawCredit';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import * as Ajax from 'merchant/utils/ajax';
import * as profile from 'merchant/reducers/profile';
import store from 'merchant/store';
import { useStore } from 'shell/commonStore';

let merchantFetchSpy = jest.spyOn(Ajax, 'merchantFetch');
let fetchBankAccountSpy;
let closeModalSpy;
let submitSpy;
let inputEl;
let btn;
let filter;
let props;

const stateSpy = jest.spyOn(store, 'getState');
jest.mock('shell/commonStore', () => ({
  ...(jest.requireActual('shell/commonStore') as any),
  useStore: jest.fn(),
}));

const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');

describe('Withdraw Self Serve Balance', () => {
  beforeEach(() => {
    fetchBankAccountSpy = jest.spyOn(profile, 'fetchBankAccount');
    closeModalSpy = jest.fn();
    submitSpy = jest.fn();
    props = {
      title: 'Fee Credits',
      type: 'fee',
      credits: 50000,
      closeModal: closeModalSpy,
      open: true,
      submitHandler: submitSpy,
    };
    stateSpy.mockClear();
    (useStore as jest.MockedFunction<any>).mockReturnValue({
      session: {
        user: {
          merchant: {
            currency: 'INR',
          },
        },
      },
      showNotification: showNotificationSpy,
    });
    render(<WithdrawCredit {...props} />);
    inputEl = screen.getByPlaceholderText('00000');
    filter = screen.getByText('50%');
    btn = screen.getByRole('button', { name: 'Withdraw' });
  });
  test('should render without crashing', () => {
    const el = screen.getByText(/Withdraw Funds/i);
    const credits = screen.getByText(/500/);
    expect(el).toBeInTheDocument();
    expect(credits).toBeInTheDocument();
    expect(fetchBankAccountSpy).toHaveBeenCalled();
  });

  test('submit button is disabled by default', () => {
    expect(btn).toBeDisabled();
  });

  test('input sanitaion is working as expected', async () => {
    userEvent.clear(inputEl);
    userEvent.type(inputEl, '1234');
    await waitFor(() => expect(inputEl).toHaveValue('1234'));

    userEvent.clear(inputEl);
    userEvent.type(inputEl, '1234..3');
    await waitFor(() => expect(inputEl).toHaveValue('1234.3'));

    userEvent.clear(inputEl);
    userEvent.type(inputEl, 'abc12345');
    await waitFor(() => expect(inputEl).toHaveValue('12345'));
  });

  test('error shows if user inputs value more than their balance', async () => {
    userEvent.clear(inputEl);
    userEvent.type(inputEl, '1000');
    await waitFor(() => {
      const err = screen.getByText('Withdrawal must be within your current balance');
      expect(err).toBeInTheDocument();
    });
  });

  test('submit button is disabled if the input value is more than the user balance', async () => {
    userEvent.clear(inputEl);
    userEvent.type(inputEl, '1000');
    await waitFor(() => expect(btn).toBeDisabled());
  });

  test('error disappears when user clears the input or enters a value less than or equal to their balance', async () => {
    userEvent.clear(inputEl);
    userEvent.type(inputEl, '1000');
    await waitFor(() => {
      const err = screen.getByText('Withdrawal must be within your current balance');
      expect(err).toBeInTheDocument();
    });

    userEvent.clear(inputEl);
    userEvent.type(inputEl, '500');
    await waitFor(() => {
      const err = screen.queryByText('Withdrawal must be within your current balance');
      expect(err).toBeNull();
    });

    userEvent.clear(inputEl);
    await waitFor(() => {
      const err = screen.queryByText('Withdrawal must be within your current balance');
      expect(err).toBeNull();
    });
  });

  test('on withdraw success, the current modal should change to a success modal', async () => {
    merchantFetchSpy.mockImplementation(() => {
      return new Promise((resolve) => {
        setTimeout(() => {
          resolve({ success: true, data: { id: '123' } });
        }, 300);
      });
    });
    fireEvent.change(inputEl, { target: { value: '500' } });
    fireEvent.click(btn);
    fireEvent.click(screen.getByRole('button', { name: 'Cancel' }));

    await waitFor(() => {
      expect(merchantFetchSpy).toHaveBeenCalledWith(
        expect.objectContaining({
          data: {
            amount: 50000,
            type: 'fee',
          },
        }),
      );

      const successModal = screen.getByText('Withdrawal in Progress');
      expect(successModal).toBeInTheDocument();

      const doneBtn = screen.getByText('Okay, Got It!');
      fireEvent.click(doneBtn);

      expect(submitSpy).toHaveBeenCalled();
      expect(closeModalSpy).toHaveBeenCalled();
    });
  });

  test('shows error on a failed withraw API call', async () => {
    merchantFetchSpy.mockImplementation(() => {
      return Promise.reject({ success: false, errors: ['Something went wrong'] });
    });
    fireEvent.change(inputEl, { target: { value: '500' } });
    fireEvent.click(btn);

    await waitFor(() => {
      const errorNotification = screen.queryByText('Something went wrong');
      expect(errorNotification).toBeInTheDocument();
    });
  });

  test('change input value as per balance fraction selected', () => {
    fireEvent.click(filter);
    expect(inputEl).toHaveValue('250.00');
  });
});
