import React from 'react';

import InfoComponent from 'merchant/views/MagicCheckout/ShippingServices/ClickPostAccountModal/components/InfoComponent';
import LinkAccountContainer from 'merchant/views/MagicCheckout/ShippingServices/ClickPostAccountModal/containers/LinkAccountContainer';

import ClickpostIcon from 'assets/clickpost-logo.png';

import {
  ClickpostModalContainer,
  ClickpostFormContainer,
  ClickpostInfoContainer,
  ClickpostModalIcon,
} from 'merchant/views/MagicCheckout/ShippingServices/ClickPostAccountModal/styled';

const ClickpostModal = () => {
  return (
    <ClickpostModalContainer>
      <ClickpostInfoContainer>
        <InfoComponent />
        <ClickpostModalIcon src={ClickpostIcon} alt="clickpost-icon" />
      </ClickpostInfoContainer>
      <ClickpostFormContainer>
        <LinkAccountContainer />
      </ClickpostFormContainer>
    </ClickpostModalContainer>
  );
};

export default ClickpostModal;
