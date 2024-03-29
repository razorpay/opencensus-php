import React, { useEffect } from 'react';
import { Button, Text } from '@razorpay/blade/components';
import {
  BANK_ACCOUNT_UPDATE_STEPS,
  InputErrorPropsInterface,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import {
  RetryContainer,
  StyledDescriptionContent,
  StyledLayout,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/PennyTestingRetry/styled';
import { formInitState } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/Form/constants';
import { trackBankAccountUpdateEvent } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/utils/track';
import { getErrorDataFromCode } from './utils';
import { BVS_ICONS } from './constants';
import { BvsErrorIcon } from './styled';

const InputError = ({ setView, state, setState }: InputErrorPropsInterface): JSX.Element => {
  const { icon, subTitle, title } = getErrorDataFromCode(state.bvs_error_code);
  const resetInput = () => {
    trackBankAccountUpdateEvent({
      objectName: 'Input Error Change Details',
      actionName: 'Clicked',
    });
    setState(formInitState);
    setView(BANK_ACCOUNT_UPDATE_STEPS.BANK_ACCOUNT_FORM);
  };

  useEffect(() => {
    trackBankAccountUpdateEvent({
      objectName: 'Bank Account Input Error Screen',
      actionName: 'Viewed',
      properties: {
        errorMessage: title,
      },
    });
  }, []);

  return (
    <RetryContainer>
      <StyledLayout>
        <StyledDescriptionContent>
          <BvsErrorIcon src={BVS_ICONS[icon]} />
          <Text size="large">{title}</Text>
          <Text size="medium" color="surface.text.gray.subtle">
            {subTitle}
          </Text>
        </StyledDescriptionContent>
        <Button size="medium" type="button" variant="primary" onClick={resetInput}>
          Change details
        </Button>
      </StyledLayout>
    </RetryContainer>
  );
};

export default InputError;
