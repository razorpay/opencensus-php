import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import BankAccountUpdateForm from '../BankAccountUpdateForm';
import { fireEvent, render, screen, waitFor, delay } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import { BankVerificationErrorInDetailsMap } from 'merchant/views/Account/Profile/components/BankAccountDetailsChangeSteps';

const rzpUtils = jest.requireMock('common/utils/rzp-utils');
jest.mock('common/utils/rzp-utils', () => ({
  ...jest.requireActual('common/utils/rzp-utils'),
  isWebkit: false,
}));

describe('BankAccountUpdateForm', () => {
  window.BANK_DETAILS_URL = 'https://ifsc.razorpay.com';
  afterAll(() => {
    rzpUtils.isWebkit = false;
  });
  const defaultProps = {
    ifsc_code: jest.fn(),
    setStep: jest.fn(),
    setVerificationError: jest.fn(),
  };

  const App = ({ initialState, ...rest }) => {
    return (
      <Provider store={storeWithInitialState(initialState)}>
        <BankAccountUpdateForm {...defaultProps} {...rest} />
      </Provider>
    );
  };

  test('should render BankAccountUpdateForm', () => {
    render(<App />);
    expect(screen.getByText('Add your new bank account details')).toBeInTheDocument();
  });

  test('should render bank account hold status', async () => {
    const initialState = {
      settlement: {
        config: {
          data: { config: { features: { hold: { status: true, reason: 'xyz reason' } } } },
        },
      },
    };
    render(<App initialState={initialState} />);
    await waitFor(() => {
      expect(screen.getByText('Your settlements have been put on hold due to')).toBeInTheDocument();
      expect(screen.getByText('xyz reason')).toBeInTheDocument();
    });
  });

  describe('Bank account banner content', () => {
    describe('When business_type is PROPRIETORSHIP', () => {
      test('should show PROPRIETORSHIP business bank account banner content', () => {
        const initialState = {
          session: {
            user: {
              promoter_pan: 'FOEPS1199P',
              company_pan: 'GOEPS1199S',
              business_type: '1',
            },
          },
        };
        render(<App initialState={initialState} />);
        screen.getByText(
          'The bank account must belong to the business PAN holder GOxxxxx^9S or signatory PAN holder FOxxxxx^9P only.',
        );
      });
    });

    describe('When business_type is NOT_REGISTERED', () => {
      test('should show NOT_REGISTERED business bank account banner content', () => {
        const initialState = {
          session: {
            user: {
              promoter_pan: 'FOEPS1199P',
              business_type: '11',
            },
          },
        };
        render(<App initialState={initialState} />);
        screen.getByText('The bank account must belong to the PAN holder FOxxxxx^9P only.');
      });
    });

    describe('When business_type is INDIVIDUAL', () => {
      test('should show INDIVIDUAL business bank account banner content', () => {
        const initialState = {
          session: {
            user: {
              promoter_pan: 'FOEPS1199P',
              business_type: '2',
            },
          },
        };
        render(<App initialState={initialState} />);
        screen.getByText('The bank account must belong to the PAN holder FOxxxxx^9P only.');
      });
    });
  });

  describe('Webkit', () => {
    test('should set account number type to be password when isWebkit is false', () => {
      rzpUtils.isWebkit = false;
      const { container } = render(<App />);
      const accountNumber = container.querySelector('input[name="account_number"]');
      expect(accountNumber.getAttribute('type')).toBe('password');
    });

    test('should set account number type to be text when isWebkit is true', () => {
      rzpUtils.isWebkit = true;
      const { container } = render(<App />);
      const accountNumber = container.querySelector('input[name="account_number"]');
      expect(accountNumber.getAttribute('type')).toBe('text');
    });
  });

  describe('Validations', () => {
    describe('Verify account number', () => {
      test('should show account number validation error when invalid account number is entered', () => {
        const { container } = render(<App />);
        const accountNumber = container.querySelector('input[name="account_number"]');
        fireEvent.change(accountNumber, { target: { value: '123' } });
        fireEvent.blur(accountNumber);
        screen.getByText('Enter a Valid Account Number');
      });
    });

    describe('Re-verify account number', () => {
      test('should show account number confirmation validation error when invalid confirm account number is entered', () => {
        const { container } = render(<App />);
        const accountNumber = container.querySelector('input[name="account_number"]');
        const accountNumberConfirmation = container.querySelector(
          'input[name="account_number_confirmation"]',
        );
        fireEvent.change(accountNumber, { target: { value: '123456778' } });
        fireEvent.change(accountNumberConfirmation, { target: { value: '123456' } });
        fireEvent.blur(accountNumberConfirmation);
        screen.getByText('Account number does not match');
      });
    });

    describe('Verify IFSC Code', () => {
      test('should show IFSC Code validation error when invalid IFSC Code is entered', () => {
        const { container } = render(<App />);
        const ifscCode = container.querySelector('input[name="ifsc_code"]');
        fireEvent.change(ifscCode, { target: { value: 'ICIC000' } });
        fireEvent.blur(ifscCode);
        screen.getByText('Incorrect IFSC code. Try again');
      });
    });

    describe('Verify benificiary name', () => {
      test('should show benificiary name validation error when invalid benificiary name is entered', () => {
        const { container } = render(<App />);
        const beneficiaryName = container.querySelector('input[name="beneficiary_name"]');
        fireEvent.change(beneficiaryName, { target: { value: 'abc' } });
        fireEvent.blur(beneficiaryName);
        screen.getByText("Enter a Valid Account Holder's Name");
      });
    });
  });

  describe('IFSC code', () => {
    test('should show bank and branch name when the IFSC code is entered', async () => {
      const { container } = render(<App />);
      const ifscCode = container.querySelector('input[name="ifsc_code"]');
      fireEvent.change(ifscCode, { target: { value: 'ICIC0003714' } });
      await waitFor(() => {
        expect(screen.getByText('ICIC Bank, Aundh, Pune')).toBeInTheDocument();
      });
    });

    test('should not show bank and branch name when branch name is not returned', async () => {
      const { container } = render(<App />);
      const ifscCode = container.querySelector('input[name="ifsc_code"]');
      fireEvent.change(ifscCode, { target: { value: 'HDFC0003715' } });
      await delay();
      expect(screen.queryByText('HDFC Bank, Aundh, Pune')).not.toBeInTheDocument();
    });

    test('should not show bank and branch name when error occurs while fetching IFSC code details', async () => {
      const { container } = render(<App />);
      const ifscCode = container.querySelector('input[name="ifsc_code"]');
      fireEvent.change(ifscCode, { target: { value: 'IDFC0003715' } });
      await delay();
      expect(screen.queryByText('IDFC Bank, Aundh, Pune')).not.toBeInTheDocument();
    });
  });

  describe('Form submit', () => {
    test('should call onSave when the bank account details are correctly submitted', () => {
      const onSave = jest.fn((body, handleSubmitCallback) =>
        handleSubmitCallback({ state: 'sync-failed-async-started' }),
      );

      const { container } = render(<App onSave={onSave} />);
      const accountNumber = container.querySelector('input[name="account_number"]');
      const accountNumberConfirmation = container.querySelector(
        'input[name="account_number_confirmation"]',
      );
      const ifscCode = container.querySelector('input[name="ifsc_code"]');
      const beneficiaryName = container.querySelector('input[name="beneficiary_name"]');
      const submitButton = screen.getByRole('button', {
        name: 'Submit and verify',
      });

      fireEvent.change(accountNumber, { target: { value: '123456778' } });
      fireEvent.change(accountNumberConfirmation, { target: { value: '123456778' } });
      fireEvent.change(ifscCode, { target: { value: 'ICIC0003714' } });
      fireEvent.change(beneficiaryName, { target: { value: 'Beneficiary Name' } });
      expect(submitButton).toBeEnabled();
      fireEvent.submit(submitButton);
      expect(defaultProps.setStep).toHaveBeenCalledWith('sync-failed-async-started');
      expect(onSave).toHaveBeenCalledWith(
        {
          account_number: '123456778',
          account_number_confirmation: '123456778',
          beneficiary_name: 'Beneficiary Name',
          ifsc_code: 'ICIC0003714',
        },
        expect.any(Function),
      );
    });

    test('should call setVerificationError when incorrect bank account details are submitted', () => {
      const bankVerificationError =
        BankVerificationErrorInDetailsMap['KC03: Invalid Beneficiary Account Number or IFSC'];
      const onSave = jest.fn((body, handleSubmitCallback) =>
        handleSubmitCallback({
          state: 'penny-testing-details-error',
          error: bankVerificationError,
        }),
      );

      const { container } = render(<App onSave={onSave} />);
      const accountNumber = container.querySelector('input[name="account_number"]');
      const accountNumberConfirmation = container.querySelector(
        'input[name="account_number_confirmation"]',
      );
      const ifscCode = container.querySelector('input[name="ifsc_code"]');
      const beneficiaryName = container.querySelector('input[name="beneficiary_name"]');
      const submitButton = screen.getByRole('button', {
        name: 'Submit and verify',
      });

      fireEvent.change(accountNumber, { target: { value: '123456778' } });
      fireEvent.change(accountNumberConfirmation, { target: { value: '123456778' } });
      fireEvent.blur(accountNumberConfirmation);
      fireEvent.change(ifscCode, { target: { value: 'ICIC0003714' } });
      fireEvent.change(beneficiaryName, { target: { value: 'Beneficiary Name' } });
      expect(submitButton).toBeEnabled();
      fireEvent.submit(submitButton);
      expect(defaultProps.setStep).toHaveBeenCalledWith('penny-testing-details-error');
      expect(defaultProps.setVerificationError).toHaveBeenCalledWith(bankVerificationError);
    });
  });
});
