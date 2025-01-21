import React, { useState } from 'react';
import {
  Box,
  Card,
  Button,
  CardBody,
  Display,
  Link,
  ArrowRightIcon,
  ArrowLeftIcon,
  Alert,
  useToast,
} from '@razorpay/blade/components';
import { useMutation } from '@tanstack/react-query';

import { graphqlRequestMutation } from '@apps/digital-bills/src/utils/graphql';
import useAppStore from '@apps/digital-bills/src/bootstrap/Store';
import { JOIN_WAITLIST } from '@apps/digital-bills/src/bootstrap/Hoc/WithOnboardingRedirect/mutations';
import FeatureCard from '@apps/digital-bills/src/views/Onboarding/components/FeatureCard';
import WaitlistModal from '@apps/digital-bills/src/views/Onboarding/components/WaitlistModal';
import {
  DIGITAL_BILLING_PRODUCT_TYPE,
  FEATURE_CARDS_DATA,
  ONBOARDING_STATUS,
  STORE_COUNT_OPTIONS_MAP,
} from '@apps/digital-bills/src/views/Onboarding/constants';

type FeaturesProps = {
  isInWaitlist: boolean;
  updateActiveScreen?: () => void;
};

const Features = ({ isInWaitlist, updateActiveScreen }: FeaturesProps) => {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const { updateOnboardingStatus } = useAppStore();
  const { show } = useToast();

  const { mutate: joinWaitlist, isLoading } = useMutation({
    mutationFn: ({
      minStoreCount,
      maxStoreCount,
    }: {
      minStoreCount: number;
      maxStoreCount: number | null;
    }) =>
      graphqlRequestMutation({
        document: JOIN_WAITLIST,
        variables: { minStoreCount, maxStoreCount, productType: DIGITAL_BILLING_PRODUCT_TYPE },
      }),
    onSuccess: () => {
      show({
        type: 'informational',
        content: 'We have received your request.',
      });
      updateOnboardingStatus(ONBOARDING_STATUS.PENDING);
      setIsModalOpen(false);
    },
    onError: () => {
      show({
        type: 'informational',
        color: 'negative',
        content: 'Something went wrong. Please try again!',
      });
    },
  });

  return (
    <>
      <WaitlistModal
        modalProps={{
          isOpen: isModalOpen,
          onDismiss: () => setIsModalOpen(false),
        }}
        onSubmit={(storesRange) => {
          joinWaitlist(STORE_COUNT_OPTIONS_MAP[storesRange]);
        }}
        isLoading={isLoading}
        isInWaitlist={isInWaitlist}
      />
      {isInWaitlist ? (
        <Alert
          isFullWidth
          color="positive"
          description="Your request has been recorded and we will get back to you as soon as possible."
          isDismissible={false}
          marginBottom="spacing.5"
        />
      ) : null}
      <Card
        elevation="lowRaised"
        padding="spacing.0"
        backgroundColor="surface.background.gray.moderate"
        accessibilityLabel="Digital Billing Features"
      >
        <CardBody>
          <Box marginX="spacing.8" marginY="spacing.11">
            <Box
              display="flex"
              alignItems={{ base: 'flex-start', l: 'center' }}
              justifyContent="space-between"
              flexDirection={{ base: 'column', l: 'row' }}
            >
              <Display size="small" weight="medium">
                What makes BillMe great?
              </Display>
              <Link
                marginTop={{ base: 'spacing.3', l: 'spacing.0' }}
                target="_blank"
                href="https://www.billme.io/"
              >
                Know More
              </Link>
            </Box>
            <Box
              display="flex"
              justifyContent="space-between"
              marginY={{ base: 'spacing.7', l: 'spacing.10' }}
              gap="spacing.5"
              flexDirection={{ base: 'column', l: 'row' }}
            >
              {FEATURE_CARDS_DATA.map((card) => {
                const { id, title, description, image } = card;
                return (
                  <FeatureCard key={id} image={image} title={title} description={description} />
                );
              })}
            </Box>
            <Box display="flex" alignItems="center" justifyContent="flex-end" gap="spacing.8">
              {!isInWaitlist ? (
                <Link icon={ArrowLeftIcon} iconPosition="left" onClick={updateActiveScreen}>
                  Back
                </Link>
              ) : null}
              <Button
                size="large"
                isDisabled={isInWaitlist || isLoading}
                icon={ArrowRightIcon}
                iconPosition="right"
                onClick={() => setIsModalOpen(true)}
              >
                Join The Waitlist
              </Button>
            </Box>
          </Box>
        </CardBody>
      </Card>
    </>
  );
};

export default Features;
