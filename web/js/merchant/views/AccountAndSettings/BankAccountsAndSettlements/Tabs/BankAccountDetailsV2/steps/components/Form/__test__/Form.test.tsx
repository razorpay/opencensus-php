import React, { useState } from 'react';
import { render, screen, waitFor, fireEvent } from 'test-utils';
import { formInitState } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/Form/constants';

import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import Form from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/Form/Form';
import { getMaskedPanNumber } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/Form/utils';
import { company_pan, INPUT_FIELD_TO_DATA, promoter_pan } from './fixtures/constants';

const mockSetView = jest.fn();
const mockSetLayoutInfo = jest.fn();
const mockCloseModal = jest.fn();
const mockShowNotification = jest.fn();

const defaultProps = {
  setView: mockSetView,
  setLayoutInfo: mockSetLayoutInfo,
  showNotification: mockShowNotification,
  closeModal: mockCloseModal,
};

describe('Bank Account Update - InputError', () => {
  const App = ({ initialStoreState = {}, initialState = formInitState, ...rest }) => {
    const [state, setState] = useState(initialState);
    return (
      <Provider store={storeWithInitialState(initialStoreState)}>
        <Form {...defaultProps} {...rest} state={state} setState={setState} />
      </Provider>
    );
  };

  beforeEach(() => {
    window.BANK_DETAILS_URL = 'https://ifsc.razorpay.com';
    mockSetView.mockReset();
    mockSetLayoutInfo.mockReset();
    mockCloseModal.mockReset();
    mockShowNotification.mockReset();
  });

  describe('Pan Number Banner Content', () => {
    const BUSINESS_TYPE_TO_CONTENT = {
      PROPRIETORSHIP: {
        business_type: '1',
        content: `The bank account must belong to the business PAN holder ${getMaskedPanNumber(
          company_pan,
        )} or signatory PAN holder ${getMaskedPanNumber(promoter_pan)} only.`,
      },
      NOT_REGISTERED: {
        business_type: '11',
        content: `The bank account must belong to the PAN holder ${getMaskedPanNumber(
          promoter_pan,
        )} only.`,
      },
      INDIVIDUAL: {
        business_type: '2',
        content: `The bank account must belong to the PAN holder ${getMaskedPanNumber(
          promoter_pan,
        )} only.`,
      },
    };

    test.each(['PROPRIETORSHIP', 'NOT_REGISTERED', 'INDIVIDUAL'])(
      'should render correct pan banner when business type is %s',
      (business_type_key) => {
        const { business_type, content } = BUSINESS_TYPE_TO_CONTENT[business_type_key];
        const initialStoreState = {
          session: {
            user: {
              promoter_pan,
              company_pan,
              business_type,
            },
          },
        };
        render(<App initialStoreState={initialStoreState} />);
        expect(screen.getByText(content)).toBeInTheDocument();
      },
    );
  });

  describe('Input Validations', () => {
    test.each(['ACCOUNT_NUMBER', 'ACCOUNT_NUMBER_CONFIRMATION', 'IFSC_CODE', 'BENIFICARY_NAME'])(
      'should show validation error when invalid %s is entered',
      (input_field_key) => {
        const { name, value, error } = INPUT_FIELD_TO_DATA[input_field_key];
        render(<App />);
        const inputField = screen.getByRole('textbox', {
          name,
        });

        expect(inputField).toBeInTheDocument();

        fireEvent.change(inputField as Element, { target: { value } });
        fireEvent.blur(inputField as Element);
        expect(screen.getByText(error)).toBeInTheDocument();
      },
    );
  });

  describe('IFSC code', () => {
    test('should show bank and branch name when the IFSC code is entered', async () => {
      render(<App />);
      const ifscCode = screen.getByRole('textbox', {
        name: INPUT_FIELD_TO_DATA.IFSC_CODE.name,
      });
      fireEvent.change(ifscCode as Element, { target: { value: 'ICIC0003714' } });
      await waitFor(() => {
        expect(screen.getByText('ICIC Bank, Aundh, Pune')).toBeInTheDocument();
      });
    });

    test('should not show bank and branch name when branch name is not returned', async () => {
      render(<App />);
      const ifscCode = screen.getByRole('textbox', {
        name: INPUT_FIELD_TO_DATA.IFSC_CODE.name,
      });
      fireEvent.change(ifscCode as Element, { target: { value: 'HDFC0003715' } });
      await waitFor(() => {
        expect(screen.queryByText('HDFC Bank, Aundh, Pune')).not.toBeInTheDocument();
      });
    });

    test('should not show bank and branch name when error occurs while fetching IFSC code details', async () => {
      render(<App />);
      const ifscCode = screen.getByRole('textbox', {
        name: INPUT_FIELD_TO_DATA.IFSC_CODE.name,
      });
      fireEvent.change(ifscCode as Element, { target: { value: 'IDFC0003715' } });
      await waitFor(() => {
        expect(screen.queryByText('IDFC Bank, Aundh, Pune')).not.toBeInTheDocument();
      });
    });
  });

  test('should close modal on Close CTA', async () => {
    render(<App />);
    const closeCTA = screen.getByRole('button', {
      name: 'Cancel',
    });
    expect(closeCTA).toBeInTheDocument();
    fireEvent.click(closeCTA);
    await waitFor(() => {
      expect(mockCloseModal).toHaveBeenCalled();
    });
  });
});
