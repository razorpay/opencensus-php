import React, { useState } from 'react';
import { connect } from 'react-redux';
import { AnyAction, bindActionCreators, Dispatch } from 'redux';

import DemoVideo from 'merchant/views/MagicCheckout/ShippingServices/IThinkAccountModal/components/DemoVideo';
import LinkAccountContainer from 'merchant/views/MagicCheckout/ShippingServices/IThinkAccountModal/containers/LinkAccountContainer';
import DemoVideoContainer from './containers/DemoVideoContainer';

import { closeModal } from 'merchant_common/reducers/modals';

import IThinkLogisticsIcon from 'assets/ithink-logistics-logo.png';

import {
  FormContainer,
  IntegrationModalContainer,
  DemoVideoContainer as StyledDemoVideoContainer,
} from 'merchant/views/MagicCheckout/ShippingServices/IThinkAccountModal/styled';

import { IThinkModalPropsType } from 'merchant/views/MagicCheckout/ShippingServices/IThinkAccountModal/types';

import {
  DEMO_INFO_TEXT,
  DEMO_VIDEO_LINK,
} from 'merchant/views/MagicCheckout/ShippingServices/IThinkAccountModal/constant';

const IThinkModal = ({ closeModal }: IThinkModalPropsType) => {
  const [step, setStep] = useState(0);

  return (
    <IntegrationModalContainer>
      <StyledDemoVideoContainer>
        <DemoVideoContainer
          modalIcon={IThinkLogisticsIcon}
          demoVideo={DemoVideo}
          demoInfoText={DEMO_INFO_TEXT}
          demoVideoLink={DEMO_VIDEO_LINK}
        />
      </StyledDemoVideoContainer>
      <FormContainer>
        <LinkAccountContainer closeModal={closeModal} step={step} setStep={setStep} />
      </FormContainer>
    </IntegrationModalContainer>
  );
};

const mapDispatchToProps = (dispatch: Dispatch<AnyAction>) =>
  bindActionCreators(
    {
      closeModal,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(IThinkModal);
