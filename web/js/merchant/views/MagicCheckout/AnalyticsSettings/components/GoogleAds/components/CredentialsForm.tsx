import React, { useState } from 'react';
import { connect } from 'react-redux';

import { Button, ArrowRightIcon } from '@razorpay/blade/components';
import Input from 'common/new-ui/Input';

import { CredentialsFormPropsType } from 'merchant/views/MagicCheckout/AnalyticsSettings/types';

import { FormCtaContainer } from 'merchant/views/MagicCheckout/AnalyticsSettings/styledComponents/common';
import {
  FormWrapper,
  FieldLabel,
  FieldContainer,
} from 'merchant/views/MagicCheckout/AnalyticsSettings/styledComponents/CredentialsForm';

const CredentialsForm = ({
  setStep,
  onSavingAccountCreds,
  isLoading,
}: CredentialsFormPropsType) => {
  const [conversionId, setConversionId] = useState<string>('');
  const [conversionLabel, setConversionLabel] = useState<string>('');
  const [adwordAccountNumber, setAdwordAccountNumber] = useState<string>('');

  const handleIntegrateBackendClick = (): void => {
    const creds = {
      conversionId,
      conversionLabel,
      adwordAccountNumber,
    };

    onSavingAccountCreds(creds);
  };
  return (
    <FormWrapper>
      <FieldContainer>
        <FieldLabel htmlFor="conversionId">
          Conversion ID
          <sup> *</sup>
        </FieldLabel>
        <Input
          id="conversionId"
          type="text"
          placeholder="Enter conversion ID"
          value={conversionId}
          onChange={(e: React.ChangeEvent<HTMLInputElement>) => setConversionId(e.target.value)}
        />
      </FieldContainer>
      <FieldContainer>
        <FieldLabel htmlFor="conversionLabel">
          Conversion label
          <sup> *</sup>
        </FieldLabel>
        <Input
          id="conversionLabel"
          type="text"
          placeholder="Enter conversion lable"
          value={conversionLabel}
          onChange={(e: React.ChangeEvent<HTMLInputElement>) => setConversionLabel(e.target.value)}
        />
      </FieldContainer>
      <FieldContainer>
        <FieldLabel htmlFor="accountNumber">
          Adwords account number
          <sup> *</sup>
        </FieldLabel>
        <Input
          id="accountNumber"
          type="text"
          placeholder="Enter adwords account number"
          value={adwordAccountNumber}
          onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
            setAdwordAccountNumber(e.target.value)
          }
        />
      </FieldContainer>
      <FormCtaContainer>
        <div
          className="secondary-cta pointer back"
          onClick={() => setStep((prev: number) => prev - 1)}
        >
          Back
        </div>
        <Button
          type="button"
          size="medium"
          onClick={handleIntegrateBackendClick}
          icon={ArrowRightIcon}
          iconPosition="right"
          isLoading={isLoading}
          isDisabled={!(conversionId && conversionLabel && adwordAccountNumber)}
        >
          Integrate backend
        </Button>
      </FormCtaContainer>
    </FormWrapper>
  );
};

const mapStateToProps = (state: Record<string, any>) => ({
  isLoading: state.magicAnalyticsSettings?.isLoading?.createAccount,
});

export default connect(mapStateToProps, null)(CredentialsForm);
