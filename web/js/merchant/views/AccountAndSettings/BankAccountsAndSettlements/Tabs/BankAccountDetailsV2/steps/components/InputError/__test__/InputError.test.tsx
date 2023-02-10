import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import {
  BankAccountUpdateErrorCodeEnum,
  BANK_ACCOUNT_UPDATE_STEPS,
  InputErrorPropsInterface,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import { formInitState } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/Form/constants';
import { getErrorDataFromCode } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/InputError/utils';

import InputError from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/InputError/InputError';

const mockSetState = jest.fn();
const mockSetView = jest.fn();
const mockBVSError = BankAccountUpdateErrorCodeEnum.ACCOUNT_BLOCKED;

const props: InputErrorPropsInterface = {
  setView: mockSetView,
  state: {
    ...formInitState,
    bvs_error_code: mockBVSError,
  },
  setState: mockSetState,
  openModal: jest.fn(),
  closeModal: jest.fn(),
  isMobile: false,
};

describe('Bank Account Update - InputError', () => {
  test.each(Object.keys(BankAccountUpdateErrorCodeEnum))(
    'should render %s InputError Component with error details',
    (bvsErrorKey) => {
      const bvs_error_code = BankAccountUpdateErrorCodeEnum[bvsErrorKey];
      const { subTitle, title } = getErrorDataFromCode(bvs_error_code);
      render(<InputError {...props} state={{ ...formInitState, bvs_error_code }} />);
      const ChangeDetailsCTA = screen.getByRole('button', {
        name: 'Change details',
      });

      expect(screen.getByText(new RegExp(title, 'i'))).toBeInTheDocument();
      expect(screen.getByText(new RegExp(subTitle, 'i'))).toBeInTheDocument();

      expect(ChangeDetailsCTA).toBeInTheDocument();
    },
  );

  test('should reset form to init state and change view to form on change details click', async () => {
    render(<InputError {...props} />);
    const ChangeDetailsCTA = screen.getByRole('button', {
      name: 'Change details',
    });
    await userEvent.click(ChangeDetailsCTA);
    expect(mockSetState).toHaveBeenCalledWith(formInitState);
    expect(mockSetView).toHaveBeenCalledWith(BANK_ACCOUNT_UPDATE_STEPS.BANK_ACCOUNT_FORM);
  });
});
