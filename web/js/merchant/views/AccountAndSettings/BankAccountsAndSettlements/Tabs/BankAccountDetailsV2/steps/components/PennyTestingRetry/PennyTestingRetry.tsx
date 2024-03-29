import { Button, Link, Text } from '@razorpay/blade/components';
import { StyledDivider } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/common/styled';
import { BVS_ICONS } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/InputError/constants';
import { BvsErrorIcon } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/InputError/styled';
import {
  BANK_ACCOUNT_UPDATE_STEPS,
  StepsInfoPropsInterface,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import { trackBankAccountUpdateEvent } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/utils/track';
import React from 'react';
import { RetryContainer, StyledDescriptionContent, StyledLayout, StyledPartition } from './styled';

const PennyTestingRetry = ({ setView, setState }: StepsInfoPropsInterface): JSX.Element => {
  const handleUploadClick = () => {
    trackBankAccountUpdateEvent({
      objectName: 'Upload Bank Account Proof',
      actionName: 'Clicked',
    });
    setView(BANK_ACCOUNT_UPDATE_STEPS.UPLOAD_PROOF);
  };

  const handleRetryClick = () => {
    trackBankAccountUpdateEvent({
      objectName: 'Try Again',
      actionName: 'Clicked',
    });
    setState((prevState) => ({
      ...prevState,
      retries: 1,
    }));
    setView(BANK_ACCOUNT_UPDATE_STEPS.BANK_ACCOUNT_FORM);
  };

  return (
    <RetryContainer>
      <StyledLayout>
        <StyledDescriptionContent>
          <BvsErrorIcon src={BVS_ICONS.BANK_ERROR} />
          <Text size="large">Couldn’t verify bank account</Text>
          <Text size="medium" color="surface.text.gray.subtle">
            Something went wrong while automatically verifying your details. Check details and try
            again
          </Text>
        </StyledDescriptionContent>
        <Button onClick={handleRetryClick} size="medium" type="button" variant="primary">
          Try again
        </Button>
      </StyledLayout>
      <StyledPartition>
        <StyledDivider isFullWidth />
        <Text size="small" color="surface.text.gray.muted">
          OR
        </Text>
        <StyledDivider isFullWidth />
      </StyledPartition>
      <StyledDescriptionContent>
        <Link onClick={handleUploadClick} variant="button">
          Upload bank account proof
        </Link>
        <Text size="small" color="surface.text.gray.muted">
          We’ll manually verify your details in 2-3 days
        </Text>
      </StyledDescriptionContent>
    </RetryContainer>
  );
};

export default PennyTestingRetry;
