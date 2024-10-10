import React, { useState } from 'react';
import { Box, Button, Link, Modal, ModalBody, ModalHeader } from '@razorpay/blade/components';

import Stepper from './Stepper';
import { ONBOARDING_DATA } from './constants';
import { OnboardingModalProps } from './types';

const OnboardingModal = ({ partner, status }: OnboardingModalProps): JSX.Element => {
  const [step, setStep] = useState(0);

  const onboardingData = ONBOARDING_DATA[partner];

  const onNextClick = () => {
    const lastStepIndex = onboardingData.length - 1;
    if (step === lastStepIndex) {
      //redirect to login modal and pass partner
      console.log('redirect to partner modal', partner, status);
      return;
    }
    setStep((prev) => prev + 1);
  };

  const onPrevClick = () => {
    setStep((prev) => prev - 1);
  };

  return (
    <Modal isOpen={true} onDismiss={() => {}} size="medium" zIndex={10000}>
      <ModalHeader />
      <ModalBody padding="spacing.0">
        <Box display="flex" flexDirection="row">
          <Box flex="1" padding="spacing.5">
            <Stepper step={step} data={onboardingData} />
            <Box
              display="flex"
              flexDirection="row"
              justifyContent="space-between"
              alignItems="center"
              paddingTop="spacing.4"
              borderTopWidth="thinner"
              borderTopColor="surface.border.gray.muted"
            >
              <Link size="small">Terms and Conditions</Link>
              <Box display="flex" flexDirection="row" gap="spacing.3">
                {step > 0 && (
                  <Button variant="tertiary" onClick={onPrevClick}>
                    Previous
                  </Button>
                )}
                <Button type="button" variant="primary" onClick={onNextClick}>
                  Next
                </Button>
              </Box>
            </Box>
          </Box>
          <Box
            flex="1"
            backgroundColor="surface.background.gray.moderate"
            padding="spacing.5"
            display={{ base: 'none', m: 'flex' }}
            flexDirection="row"
            justifyContent="center"
            alignItems="center"
            borderTopRightRadius="large"
            borderBottomRightRadius="large"
            maxHeight="480px"
            overflow="hidden"
          >
            <img width="100%" height="auto" src={onboardingData[step].image} />
          </Box>
        </Box>
      </ModalBody>
    </Modal>
  );
};

export default OnboardingModal;
