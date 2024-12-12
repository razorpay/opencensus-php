import React, { useEffect } from 'react';
import {
  Box,
  OptimizerIcon,
  Amount,
  Text,
  Heading,
  Badge,
  CheckIcon,
  Button,
  ArrowRightIcon,
  Divider,
} from '@razorpay/blade/components';
import { StateIconBackground } from 'merchant/views/Optimizer/OnBoarding/styled';
import { trackOptimizerEvents } from 'merchant/views/Optimizer/track';
import { LEARN_MORE_LINK, INFO_POINTS } from 'merchant/views/Optimizer/OnBoarding/constants';
import {
  PRICING_PLAN_VISIT,
  PRICING_PLAN_GET_STARTED_CLICK,
  PRICING_PLAN_LEARN_MORE_CLICK,
} from 'merchant/views/Optimizer/OnBoarding/track';

export const Pricing = ({
  mode,
  nextStep,
}: {
  mode: string;
  nextStep: () => void;
}): JSX.Element => {
  const getStartedClick = () => {
    trackOptimizerEvents(PRICING_PLAN_GET_STARTED_CLICK);
    nextStep();
  };

  useEffect(() => {
    trackOptimizerEvents(PRICING_PLAN_VISIT);
  }, []);

  const learnMoreClicked = () => {
    trackOptimizerEvents(PRICING_PLAN_LEARN_MORE_CLICK);
    window.open(LEARN_MORE_LINK, '_blank');
  };
  return (
    <Box
      display="flex"
      flexDirection="column"
      gap="spacing.8"
      justifyContent="center"
      paddingX="spacing.8"
      paddingY="spacing.10"
      backgroundColor="surface.background.sea.subtle"
      borderRadius="large"
      flex="1"
    >
      <Box
        display="flex"
        flexDirection="column"
        gap="spacing.7"
        justifyContent="space-between"
        flex="1"
      >
        <Box display="flex" flexDirection="column" gap="spacing.7">
          <Box display="flex" flexDirection="column" gap="spacing.7">
            <Box
              backgroundColor="surface.background.primary.intense"
              width="fit-content"
              borderRadius="medium"
              paddingX="spacing.3"
              paddingTop="spacing.3"
              paddingBottom="spacing.1"
            >
              <OptimizerIcon size="large" color="surface.icon.staticWhite.normal" />
            </Box>
            <Box display="flex" flexDirection="column" gap="spacing.3">
              <Text size="small" color="surface.text.gray.muted">
                Optimizer - Razorpay's AI powered payments router
              </Text>
              <Heading size="large">Get started with Optimizer</Heading>
            </Box>
          </Box>
          <Box display="flex" flexDirection="column" gap="spacing.7">
            <Box display="flex" flexDirection="column" gap="spacing.3">
              <Box display="flex" flexDirection="column" gap="spacing.2" justifyContent="center">
                <Box display="flex" flexDirection="row" justifyContent="space-between">
                  <Box display="flex" gap="spacing.2" alignItems="end">
                    <Amount
                      value={0}
                      type="display"
                      suffix="none"
                      isAffixSubtle={false}
                      weight="semibold"
                    />
                    <Heading
                      position="relative"
                      bottom="spacing.4"
                      weight="regular"
                      color="surface.text.gray.muted"
                      size="large"
                    >
                      / month
                    </Heading>
                    <Text
                      position="relative"
                      bottom="spacing.7"
                      size="small"
                      color="interactive.text.negative.muted"
                    >
                      *
                    </Text>
                  </Box>
                  <Badge color="information" alignSelf="center">
                    Startup Plan
                  </Badge>
                </Box>
                <Text size="small" color="surface.text.gray.subtle">
                  <Text display="inline" size="small" color="interactive.text.negative.muted">
                    *
                  </Text>
                  Free up to 1 crore per month, 0.25% per txn after
                </Text>
                <Divider orientation="horizontal" />
              </Box>
            </Box>
            <Box display="flex" flexDirection="column" gap="spacing.4">
              {INFO_POINTS.map((point, index) => (
                <Box display="flex" gap="spacing.2" alignItems="center" flex="1" key={index}>
                  <StateIconBackground>
                    <CheckIcon color="surface.icon.staticWhite.normal" size="small" />
                  </StateIconBackground>
                  <Text size="small" color="surface.text.gray.normal">
                    {point}
                  </Text>
                </Box>
              ))}
            </Box>
          </Box>
        </Box>
        <Box display="flex" gap="spacing.3" alignItems="center" flex="1" marginTop="spacing.10">
          <Button variant="secondary" isFullWidth onClick={learnMoreClicked}>
            Learn more
          </Button>
          <Button
            icon={ArrowRightIcon}
            iconPosition="right"
            isFullWidth
            onClick={getStartedClick}
            isDisabled={mode !== 'live'} // only live mode allowed
          >
            Get started
          </Button>
        </Box>
      </Box>
    </Box>
  );
};
