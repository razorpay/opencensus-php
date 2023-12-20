import React from 'react';
import { connect } from 'react-redux';
import { AnyAction, bindActionCreators, Dispatch } from 'redux';

import { closeModal } from 'merchant_common/reducers/modals';

import LinkAccountContainer from 'merchant/views/MagicCheckout/ShippingServices/UnicommerceAccountModal/containers/LinkAccountContainer';
import InfoComponent from 'merchant/views/MagicCheckout/ShippingServices/UnicommerceAccountModal/components/InfoComponent';

import UnicommerceIcon from 'assets/unicommerce.png';

import { UnicommerceModalPropsType } from 'merchant/views/MagicCheckout/ShippingServices/UnicommerceAccountModal/types';

import {
  UnicommerceModalContainer,
  FormContainer,
  UnicommerceModalIcon,
  InfoContainer,
} from 'merchant/views/MagicCheckout/ShippingServices/UnicommerceAccountModal/styled';

const UnicommerceModal = ({ closeModal }: UnicommerceModalPropsType) => {
  return (
    <UnicommerceModalContainer>
      <InfoContainer>
        <InfoComponent />
        <UnicommerceModalIcon src={UnicommerceIcon} alt="Unicommerce-icon" />
      </InfoContainer>
      <FormContainer>
        <LinkAccountContainer closeModal={closeModal} />
      </FormContainer>
    </UnicommerceModalContainer>
  );
};

const mapDispatchToProps = (dispatch: Dispatch<AnyAction>) =>
  bindActionCreators(
    {
      closeModal,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(UnicommerceModal);
