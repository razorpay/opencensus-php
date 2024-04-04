import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { Button, ArrowRightIcon, Box, TextInput } from '@razorpay/blade/components';

import * as ModalActions from 'merchant_common/reducers/modals';
import { createShippingProviders } from 'merchant/reducers/magicCheckout/shipping_services/actions';
import { showNotification } from 'merchant_common/reducers/notifications';

import type {
  ClickpostFormDataType,
  ClickpostFormPropType,
} from 'merchant/views/MagicCheckout/ShippingServices/ClickPostAccountModal/types';

import { FORM_FIELDS } from 'merchant/views/MagicCheckout/ShippingServices/ClickPostAccountModal/constants';
import { SHIPPING_PARTNERS } from 'merchant/views/MagicCheckout/ShippingServices/constants';

import {
  FormWrapper,
  FormCtaContainer,
} from 'merchant/views/MagicCheckout/ShippingServices/ClickPostAccountModal/styled';

const ClickpostForm = (props: ClickpostFormPropType) => {
  const { createProviders, showNotification, closeModal, user } = props;

  const [formData, setFormData] = useState<ClickpostFormDataType>({
    username: '',
    password: '',
  });

  const [isCtaEnabled, setIsCtaEnabled] = useState<boolean>(false);

  const checkCtaEnabled = (formData: ClickpostFormDataType) => {
    const { username, password } = formData;
    setIsCtaEnabled(!!(username && password));
  };

  const handleClickpostConnect = () => {
    setIsCtaEnabled(false);
    createProviders({
      providerType: Object.keys(SHIPPING_PARTNERS)[4],
      providerId: user?.id,
      clickpost: {
        username: formData.username,
        password: formData.password,
      },
    })
      .then(() => {
        showNotification({
          type: 'success',
          message: 'Connected Successfully',
          closeTimeout: 2000,
        });
        setTimeout(() => {
          closeModal();
        }, 2000);
      })
      .catch(() => {
        showNotification({
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
          />
        </Box>
      ))}
      <FormCtaContainer>
        <Button
          type="button"
          size="medium"
          onClick={handleClickpostConnect}
          icon={ArrowRightIcon}
          iconPosition="right"
          isDisabled={!isCtaEnabled}
        >
          Connect to Clickpost
        </Button>
      </FormCtaContainer>
    </FormWrapper>
  );
};

const mapStateToProps = (state) => {
  return { user: state.session.user };
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      ...ModalActions,
      showNotification,
      createProviders: createShippingProviders,
    },
    dispatch,
  );
export default connect(mapStateToProps, mapDispatchToProps)(ClickpostForm);
