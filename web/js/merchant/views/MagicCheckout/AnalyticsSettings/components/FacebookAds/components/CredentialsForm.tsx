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
}: CredentialsFormPropsType): JSX.Element => {
  const [pixelId, setPixelId] = useState<string>('');
  const [accessToken, setAccessToken] = useState<string>('');

  const handleIntegrateBackendClick = (): void => {
    const creds = {
      pixelId,
      accessToken,
    };

    onSavingAccountCreds(creds);
  };

  return (
    <FormWrapper>
      <FieldContainer>
        <FieldLabel htmlFor="pixelId">
          Pixel ID
          <sup> *</sup>
        </FieldLabel>
        <Input
          id="pixelId"
          type="text"
          placeholder="Enter Pixel Id"
          value={pixelId}
          onChange={(e: React.ChangeEvent<HTMLInputElement>) => setPixelId(e.target.value)}
        />
      </FieldContainer>
      <FieldContainer>
        <FieldLabel htmlFor="accessToken">
          Access token
          <sup> *</sup>
        </FieldLabel>
        <Input
          id="accessToken"
          type="text"
          placeholder="Enter Access token"
          value={accessToken}
          onChange={(e: React.ChangeEvent<HTMLInputElement>) => setAccessToken(e.target.value)}
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
          isDisabled={!(pixelId && accessToken)}
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
