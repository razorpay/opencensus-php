import React, { useState, useRef, useEffect } from 'react';
import {
  Box,
  Heading,
  Text,
  Link,
  ExternalLinkIcon,
  Button,
  Spinner,
  ZapIcon,
  Amount,
} from '@razorpay/blade/components';

import {
  trackButton,
  trackRender,
} from 'merchant/views/Settlements/InstantSettlements/utils/analytics';
import {
  SCREENS,
  convertToMajorUnit,
  midLimitGTMViewedStatus,
} from 'merchant/views/Settlements/Settlements/components/Modals/OnDemandV2/helpers';
import ConfettiBImg from 'merchant/views/Settlements/Settlements/components/Modals/OnDemandV2/images/confetti-big.png';
import ConfettiSImg from 'merchant/views/Settlements/Settlements/components/Modals/OnDemandV2/images/confetti-small.png';
import ShieldImg from 'merchant/views/Settlements/Settlements/components/Modals/OnDemandV2/images/shield.png';

const STEPS = {
  INFO: 1,
  ANALYZING_ONE: 2,
  ANALYZING_TWO: 3,
  OFFER: 4,
} as const;
const WAIT_TIME = 1000;

const wait = (timeInMs: number) =>
  new Promise((res) => {
    setTimeout(res, timeInMs);
  });

const GtmModalContent = ({
  maxLimit,
  currency,
  onComplete,
}: {
  maxLimit: number;
  currency: 'INR';
  onComplete: VoidFunction;
}) => {
  const [currentStep, setCurrentStep] = useState<typeof STEPS[keyof typeof STEPS]>(STEPS.INFO);
  const hasViewedOfferRef = useRef(false);

  useEffect(() => {
    trackRender({ screen: SCREENS.WITHDRAW, context: 'gtm' });
  }, []);

  const handleNext = async () => {
    trackButton({
      screen: SCREENS.WITHDRAW,
      name: 'next',
      properties: {
        step: currentStep,
      },
    });

    if (currentStep === STEPS.INFO && hasViewedOfferRef.current) {
      setCurrentStep(STEPS.OFFER);
      return;
    }

    if (currentStep === STEPS.INFO) {
      setCurrentStep(STEPS.ANALYZING_ONE);
      await wait(WAIT_TIME);
      setCurrentStep(STEPS.ANALYZING_TWO);
      await wait(WAIT_TIME);
      setCurrentStep(STEPS.OFFER);
      midLimitGTMViewedStatus.setViewed();
      hasViewedOfferRef.current = true;
      trackRender({
        screen: SCREENS.WITHDRAW,
        context: 'gtm-offer',
      });
      return;
    }
    onComplete();
    midLimitGTMViewedStatus.setViewed();
  };

  const handleBack = () => {
    setCurrentStep(STEPS.INFO);
  };

  const renderStep = () => {
    if (currentStep === STEPS.INFO) {
      return (
        <>
          {/* Header */}
          <Box
            display="flex"
            alignItems="center"
            justifyContent="space-between"
            gap="spacing.6"
            paddingX="spacing.6"
            paddingY="spacing.9"
            backgroundColor="surface.background.cloud.subtle"
          >
            <div>
              <Heading color="surface.text.primary.normal" size="xlarge">
                Reliable
              </Heading>
              <Heading color="surface.text.primary.normal" size="xlarge" weight="regular">
                Daily Limits
              </Heading>
            </div>
            <img width="76" height="86" src={ShieldImg} alt="Shield" />
          </Box>
          {/* Body */}
          <Box padding="spacing.6">
            <Heading size="small">
              Introducing a more secure, compliant & predictable version of Instant Settlements.
            </Heading>
            <Text marginTop="spacing.3" marginBottom="spacing.9">
              Daily Settlement Limits will now ensure that cashflow from your settlement balance is
              more predictable. Settle your daily limits without any interruptions!
            </Text>
            <Box display="flex" alignItems="center" justifyContent="space-between" gap="spacing.6">
              <Link
                icon={ExternalLinkIcon}
                iconPosition="right"
                href="https://razorpay.com/docs/payments/settlements/instant/#daily-settlement-limit"
                target="_blank"
              >
                Learn More
              </Link>
              <Button onClick={handleNext}>Next</Button>
            </Box>
          </Box>
        </>
      );
    }

    if (currentStep === STEPS.ANALYZING_ONE || currentStep === STEPS.ANALYZING_TWO) {
      return (
        <Box
          minHeight="380px"
          display="flex"
          flexDirection="column"
          alignItems="center"
          justifyContent="center"
          margin="spacing.6"
          borderRadius="medium"
          backgroundColor="surface.background.gray.moderate"
        >
          <Spinner accessibilityLabel="Analyzing" />
          <Text marginTop="spacing.3" weight="semibold">
            {currentStep === STEPS.ANALYZING_ONE
              ? 'Analyzing your transaction history...'
              : 'Analyzing your business details...'}
          </Text>
        </Box>
      );
    }
    return (
      <Box paddingX="spacing.6" paddingBottom="spacing.6" paddingTop="spacing.10">
        {/* Header */}
        <Box
          position="relative"
          padding="spacing.6"
          textAlign="center"
          borderRadius="medium"
          backgroundColor="surface.background.cloud.subtle"
        >
          <Amount
            type="heading"
            marginBottom="spacing.3"
            color="surface.text.primary.normal"
            size="xlarge"
            value={convertToMajorUnit(maxLimit, { currency, keepDecimal: false })}
            suffix="none"
            currency={currency}
          />
          <Text>Maximum Daily Withdrawal Limit</Text>
          <Box top="-10px" pointerEvents="none" right="12%" position="absolute">
            <img width="30" src={ConfettiSImg} />
          </Box>
          <Box
            opacity="0.5"
            bottom="-6px"
            pointerEvents="none"
            right="-14px"
            transform="rotate(100deg)"
            position="absolute"
          >
            <img width="30" src={ConfettiSImg} />
          </Box>
          <Box position="absolute" left="-20px" top="0px" pointerEvents="none">
            <img width="60" src={ConfettiBImg} />
          </Box>
        </Box>
        {/* Body */}
        <Text marginY="spacing.5">
          This is the maximum amount that you can withdraw every day. Once this limit is exhausted,
          you will be able to withdraw again the next working day.
        </Text>
        <Text color="surface.text.gray.subtle">
          For any queries or assistance, notify your account manager or write to us at
          support@razorpay.com
        </Text>
        <Box
          marginTop="spacing.9"
          display="flex"
          justifyContent="flex-end"
          alignItems="center"
          gap="spacing.3"
        >
          <Button variant="tertiary" onClick={handleBack}>
            Back
          </Button>
          <Button icon={ZapIcon} onClick={handleNext}>
            Settle now
          </Button>
        </Box>
      </Box>
    );
  };

  return (
    <Box backgroundColor="surface.background.gray.intense" borderRadius="large" overflow="hidden">
      {renderStep()}
    </Box>
  );
};

export default GtmModalContent;
