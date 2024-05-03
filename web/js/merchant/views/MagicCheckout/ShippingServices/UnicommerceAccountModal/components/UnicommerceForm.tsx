import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, Dispatch, AnyAction } from 'redux';

import { Button, ArrowRightIcon, Box, InfoIcon, TextInput } from '@razorpay/blade/components';

import { closeModal } from 'merchant_common/reducers/modals';
import { createShippingProviders } from 'merchant/reducers/magicCheckout/shipping_services/actions';
import { showNotification } from 'merchant_common/reducers/notifications';

import {
  UnicommerceFormPropType,
  UnicommerceFormDataType,
  UnicommerceFormGlobalStateType,
} from 'merchant/views/MagicCheckout/ShippingServices/UnicommerceAccountModal/types';

import {
  FORM_FIELDS,
  USERNAME_INVALID_REGEX,
} from 'merchant/views/MagicCheckout/ShippingServices/UnicommerceAccountModal/constants';
import { SHIPPING_PARTNERS } from 'merchant/views/MagicCheckout/ShippingServices/constants';

import {
  FormWrapper,
  UnicommerceLogisticsInfo,
  FormCtaContainer,
} from 'merchant/views/MagicCheckout/ShippingServices/UnicommerceAccountModal/styled';

const UnicommerceForm = (props: UnicommerceFormPropType) => {
  const { createProviders, displayNotification, closeModal, user } = props;

  const [formData, setFormData] = useState<UnicommerceFormDataType>({
    username: '',
    password: '',
    tenant: '',
  });

  const [isCtaEnabled, setIsCtaEnabled] = useState<boolean>(false);
  const [isUsernameValid, setIsUsernameValid] = useState<boolean>(true);

  const checkCtaEnabled = (formData: UnicommerceFormDataType) => {
    const { username, password, tenant } = formData;
    setIsCtaEnabled(!!(username && password && tenant));
  };

  const handleUnicommerceConnect = () => {
    setIsCtaEnabled(false);
    createProviders({
      providerType: Object.keys(SHIPPING_PARTNERS)[3],
      providerId: user?.id,
      unicommerce: {
        auth: {
          user: formData.username,
          password: formData.password,
        },
        tenant: formData.tenant,
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

  const handleInputChange = (e) => {
    const { name, value } = e;

    setFormData((prevVal) => ({
      ...prevVal,
      [name]: value,
    }));
  };

  useEffect(() => {
    checkCtaEnabled(formData);
  }, [formData]);

  const handleUsernameValidation = (fieldName: string) => {
    if (fieldName !== FORM_FIELDS[0].name) {
      return;
    }

    const { username } = formData;
    if (username !== '') {
      setIsUsernameValid(USERNAME_INVALID_REGEX.test(username));
    }
  };

  const getValidationState = (fieldName: string) => {
    return !isUsernameValid && fieldName === FORM_FIELDS[0].name ? 'error' : 'none';
  };

  return (
    <FormWrapper>
      {FORM_FIELDS.map((field) => (
        <Box key={field.name}>
          <TextInput
            name={field.name}
            label={field.label}
            placeholder={field.placeholder}
            isRequired={true}
            onChange={(e) => handleInputChange(e)}
            necessityIndicator="required"
            value={formData[field.name]}
            helpText={field.helpText}
            onBlur={() => handleUsernameValidation(field.name)}
            validationState={getValidationState(field.name)}
            errorText="Please enter valid username"
          />
        </Box>
      ))}
      <UnicommerceLogisticsInfo>
        <InfoIcon
          color="interactive.icon.gray.muted"
          marginRight="spacing.4"
          position="relative"
          top="spacing.1"
          size="2xlarge"
        />
        <Box>
          Razorpay will only have read access to Unicommerce account. This will be used to sync
          delivery statuses of your orders.
        </Box>
      </UnicommerceLogisticsInfo>
      <FormCtaContainer>
        <div className="secondary-cta pointer back" onClick={closeModal}>
          Close
        </div>
        <Button
          type="button"
          size="medium"
          onClick={handleUnicommerceConnect}
          icon={ArrowRightIcon}
          iconPosition="right"
          isDisabled={!isCtaEnabled || !isUsernameValid}
        >
          Connect to Unicommerce
        </Button>
      </FormCtaContainer>
    </FormWrapper>
  );
};

const mapStateToProps = (state: UnicommerceFormGlobalStateType) => ({
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

export default connect(mapStateToProps, mapDispatchToProps)(UnicommerceForm);
