import React, { useEffect, useState } from 'react';
import {
  Card,
  CardHeader,
  CardBody,
  CardHeaderLeading,
  ArrowUpRightIcon,
  Button,
  Text,
  Box,
} from '@razorpay/blade/components';
import { RadioBlock } from './RadioBlock';
import { ShopifySteps } from './ShopifySteps';
import { BUYER_PROTECT_SHOPIFY_INTEGRATION_GUIDE } from 'merchant/views/CustomerTrust/constants';
import {
  trackBpPlatformSelectOtherClick,
  trackBpPlatformSelectShopifyClick,
  trackBpViewIntegrationStepsClick,
} from 'merchant/views/CustomerTrust/analytics';

export const IntegrationSteps = () => {
  const [selectedPlatform, setSelectedPlatform] = useState<'shopify' | 'non-shopify'>('shopify');

  useEffect(() => {
    if (selectedPlatform === 'shopify') {
      trackBpPlatformSelectShopifyClick();
    } else {
      trackBpPlatformSelectOtherClick();
    }
  }, [selectedPlatform]);

  const handleViewIntegrationStepsClick = () => {
    window.open(BUYER_PROTECT_SHOPIFY_INTEGRATION_GUIDE, '_blank');
    trackBpViewIntegrationStepsClick();
  };

  const renderContent = () => {
    if (selectedPlatform === 'non-shopify') {
      return (
        <Box
          display="flex"
          flexDirection="column"
          padding={{
            base: 'spacing.5',
            s: 'spacing.7',
          }}
          gap="spacing.4"
        >
          <Text weight="semibold" color="surface.text.gray.normal">
            Custom Integration
          </Text>
          <Text size="small" color="surface.text.gray.muted">
            Want to display Buyer Protection on your product page? Follow our step-by-step guide to
            get started.
          </Text>
          <Box
            display={{
              base: 'block',
              s: 'inline-block',
            }}
          >
            <Button
              variant="primary"
              icon={ArrowUpRightIcon}
              iconPosition="right"
              onClick={handleViewIntegrationStepsClick}
            >
              View integration steps
            </Button>
          </Box>
        </Box>
      );
    }

    if (selectedPlatform === 'shopify') {
      return <ShopifySteps />;
    }

    return null;
  };

  return (
    <>
      <Card elevation="none" backgroundColor="surface.background.gray.moderate">
        <CardHeader>
          <CardHeaderLeading
            title="Select your platform"
            subtitle="Choose your e-commerce platform to get the right instructions to setup Buyer Protection on your product page"
          />
        </CardHeader>
        <CardBody>
          <Box
            display="flex"
            flexDirection="column"
            gap={{
              base: 'spacing.5',
              s: 'spacing.7',
            }}
            padding={{
              base: 'spacing.5',
              s: 'spacing.7',
            }}
          >
            <RadioBlock
              selected={selectedPlatform}
              onChange={(value) => setSelectedPlatform(value as 'shopify' | 'non-shopify')}
            />
            {renderContent()}
          </Box>
        </CardBody>
      </Card>
    </>
  );
};
