import React from 'react';

import ModalHeader from 'common/ui/ModalHeader';

import { LinkAccountWrapper } from 'merchant/views/MagicCheckout/ShippingServices/IThinkAccountModal/styled';

import { LinkAccountPropsType } from 'merchant/views/MagicCheckout/ShippingServices/IThinkAccountModal/types';

import { STEP_TEXTS } from 'merchant/views/MagicCheckout/ShippingServices/IThinkAccountModal/constant';

const LinkAccountContainer = ({ closeModal, step, setStep }: LinkAccountPropsType): JSX.Element => {
  const { Component, header, desc } = STEP_TEXTS[step];
  return (
    <LinkAccountWrapper>
      <ModalHeader onCloseClick={closeModal} />
      <div className="link-account-content">
        <div className="link-account-header font-bold color-black">{header}</div>
        <div className="link-account-desc">{desc}</div>
        <Component step={step} setStep={setStep} />
      </div>
    </LinkAccountWrapper>
  );
};

export default LinkAccountContainer;
