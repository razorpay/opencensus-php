import React from 'react';
import ModalHeader from 'common/ui/ModalHeader';

import UnicommerceForm from 'merchant/views/MagicCheckout/ShippingServices/UnicommerceAccountModal/components/UnicommerceForm';

import { LinkAccountPropsType } from 'merchant/views/MagicCheckout/ShippingServices/UnicommerceAccountModal/types';

import { LinkAccountWrapper } from 'merchant/views/MagicCheckout/ShippingServices/UnicommerceAccountModal/styled';

const LinkAccountContainer = ({ closeModal }: LinkAccountPropsType): JSX.Element => {
  return (
    <LinkAccountWrapper>
      <ModalHeader onCloseClick={closeModal} />
      <div className="link-account-content">
        <div className="link-account-header font-bold color-black">Integrate with Unicommerce</div>
        <div className="link-account-desc">
          Please enter the below credentials to integrate with Unicommerce
        </div>
        <UnicommerceForm />
      </div>
    </LinkAccountWrapper>
  );
};

export default LinkAccountContainer;
