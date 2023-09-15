import React, { useState } from 'react';

import LinkAccountInfoContainer from 'merchant/views/MagicCheckout/AnalyticsSettings/common/LinkAccountInfoContainer';
import LinkAccountForm from 'merchant/views/MagicCheckout/AnalyticsSettings/common/LinkAccountForm';

import { IntegrationModalPropsType } from 'merchant/views/MagicCheckout/AnalyticsSettings/types';

import {
  LinkAccountContainer,
  DemoContainer,
  FormContainer,
} from 'merchant/views/MagicCheckout/AnalyticsSettings/styledComponents/IntegrationModal';

type ModalPropsType = {
  integrationModalProps: IntegrationModalPropsType;
};

const IntegrationModal = (props: ModalPropsType): JSX.Element => {
  const { integrationModalProps } = props;

  const {
    stepTexts,
    demoContainer: demoVideo,
    modalIcon,
    demoInfoText,
    points,
    pointsHeader,
    demoVideoLink,
    onSavingAccountCreds,
  } = integrationModalProps;

  const [step, setStep] = useState<number>(0);

  return (
    <LinkAccountContainer>
      <DemoContainer>
        <LinkAccountInfoContainer
          demoVideo={demoVideo}
          modalIcon={modalIcon}
          demoInfoText={demoInfoText}
          demoVideoLink={demoVideoLink}
        />
      </DemoContainer>
      <FormContainer>
        <LinkAccountForm
          step={step}
          setStep={setStep}
          stepTexts={stepTexts}
          points={points}
          pointsHeader={pointsHeader}
          onSavingAccountCreds={onSavingAccountCreds}
        />
      </FormContainer>
    </LinkAccountContainer>
  );
};

export default IntegrationModal;
