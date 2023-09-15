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
  const [measurementId, setMeasurementId] = useState<string>('');
  const [apiSecret, setApiSecret] = useState<string>('');

  const handleIntegrateBackendClick = (): void => {
    const creds = {
      measurementId,
      apiSecret,
    };

    onSavingAccountCreds(creds);
  };

  return (
    <FormWrapper>
      <FieldContainer>
        <FieldLabel htmlFor="measurementId">
          Measurement ID
          <sup> *</sup>
        </FieldLabel>
        <Input
          id="measurementId"
          type="text"
          placeholder="Enter Measurement Id"
          value={measurementId}
          onChange={(e: React.ChangeEvent<HTMLInputElement>) => setMeasurementId(e.target.value)}
        />
      </FieldContainer>
      <FieldContainer>
        <FieldLabel htmlFor="apiSecret">
          API secret value
          <sup> *</sup>
        </FieldLabel>
        <Input
          id="apiSecret"
          type="text"
          placeholder="Enter API secret value"
          value={apiSecret}
          onChange={(e: React.ChangeEvent<HTMLInputElement>) => setApiSecret(e.target.value)}
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
          isDisabled={!(measurementId && apiSecret)}
        >
          Integrate backend
        </Button>
      </FormCtaContainer>
    </FormWrapper>
  );
};

const mapStateToProps = (state: Record<string, any>) => ({
  isLoading: state.magicAnalyticsSettings.isLoading.createAccount,
});

export default connect(mapStateToProps, null)(CredentialsForm);
