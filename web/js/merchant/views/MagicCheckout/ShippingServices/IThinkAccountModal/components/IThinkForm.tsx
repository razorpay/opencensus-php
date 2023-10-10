import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, Dispatch, AnyAction } from 'redux';

import { Button, ArrowRightIcon } from '@razorpay/blade/components';

import Input from 'common/new-ui/Input';

import { closeModal } from 'merchant_common/reducers/modals';
import { createShippingProviders } from 'merchant/reducers/magicCheckout/shipping_services/actions';
import { showNotification } from 'merchant_common/reducers/notifications';

import { SHIPPING_PARTNERS } from 'merchant/views/MagicCheckout/ShippingServices/constants';

import {
  FormWrapper,
  FieldLabel,
  FieldContainer,
  IThinkLogisticsInfo,
  FormCtaContainer,
} from 'merchant/views/MagicCheckout/ShippingServices/IThinkAccountModal/styled';

import { IThinkFormPropType } from 'merchant/views/MagicCheckout/ShippingServices/IThinkAccountModal/types';

const IThinkForm = (props: IThinkFormPropType) => {
  const { step, setStep, createProviders, displayNotification, closeModal, user } = props;

  const [apiKey, setApiKey] = useState<string>('');
  const [apiSecret, setApiSecret] = useState<string>('');
  const [isCtaEnabled, setIsCtaEnabled] = useState<boolean>(false);

  const handleSecondaryClick = () => {
    setStep(step - 1);
  };

  const checkCtaEnabled = (apiKey: string, apiSecret: string) => {
    setIsCtaEnabled(!!(apiKey && apiSecret));
  };

  const handleShiprocketConnect = () => {
    setIsCtaEnabled(false);
    createProviders({
      providerType: Object.keys(SHIPPING_PARTNERS)[2],
      providerId: user?.id,
      ithink_logistics: {
        auth: {
          user: apiKey,
          password: apiSecret,
        },
      },
    })
      .then(() => {
        displayNotification({
          type: 'success',
          message: 'Connected Successfully',
          closeTimeout: 2000,
        });
        setTimeout(() => {
          closeModal();
        }, 2000);
      })
      .catch(() => {
        displayNotification({
          type: 'error',
          message: 'The entered credentials are invalid. Please verify & retry',
        });
      });
  };

  useEffect(() => {
    checkCtaEnabled(apiKey, apiSecret);
  }, [apiKey, apiSecret]);

  return (
    <FormWrapper>
      <FieldContainer>
        <FieldLabel htmlFor="apiSecret">
          API Key
          <sup> *</sup>
        </FieldLabel>
        <Input
          autoFocus
          name="apiKey"
          placeholder="API Key of your iThink Logistics account"
          id="apikey"
          type="text"
          value={apiKey}
          onChange={(e) => setApiKey(e.target.value)}
        />
      </FieldContainer>
      <FieldContainer>
        <FieldLabel htmlFor="apiSecret">
          Secret Key
          <sup> *</sup>
        </FieldLabel>
        <Input
          name="apiSecret"
          placeholder="Secret Key of your iThink Logistics account"
          id="apiSecret"
          type="text"
          value={apiSecret}
          onChange={(e) => setApiSecret(e.target.value)}
        />
      </FieldContainer>
      <IThinkLogisticsInfo>
        <div className="ithink-connect-info-icon">i</div>
        <div>
          By sharing your API credentials, you are providing read access to Razorpay to your iThink
          Logistics account
        </div>
      </IThinkLogisticsInfo>
      <FormCtaContainer>
        <div className="secondary-cta pointer back" onClick={handleSecondaryClick}>
          Back
        </div>
        <Button
          type="button"
          size="medium"
          onClick={handleShiprocketConnect}
          icon={ArrowRightIcon}
          iconPosition="right"
          isDisabled={!isCtaEnabled}
        >
          Connect to iThink Logistics
        </Button>
      </FormCtaContainer>
    </FormWrapper>
  );
};

const mapStateToProps = (state: Record<string, any>) => ({
  user: state.session.user,
});

const mapDispatchToProps = (dispatch: Dispatch<AnyAction>) =>
  bindActionCreators(
    {
      closeModal,
      createProviders: createShippingProviders,
      displayNotification: showNotification,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(IThinkForm);
