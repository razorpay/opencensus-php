import React from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Box,
  Heading,
  Link,
  Text,
  EditInlineIcon,
  ExternalLinkIcon,
  CashIcon,
  SparklesIcon,
  MagicCheckoutIcon,
} from '@razorpay/blade/components';

import { OnboardedMerchantTabHeader } from 'merchant/views/MagicCheckout/Settings/containers/MagicXControlCenter/styled';

import { MAGIC_DASHBOARD_REVAMP_EXPERIMENT } from 'merchant/views/MagicCheckout/constants';
import { checkMagicConfigurationFlow } from 'merchant/views/MagicCheckout/utils/Configuration';
import { useMagicExperiment } from 'merchant/views/MagicCheckout/utils/useMagicExperiment';
import {
  ROUTES,
  DOCS_LINKS,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXControlCenter/constants';

export const OnboardedMerchantTab = () => {
  const navigate = useNavigate();
  const MAGICX_VERSION = useMagicExperiment(MAGIC_DASHBOARD_REVAMP_EXPERIMENT)
    ? 'MAGICX_V2'
    : 'MAGICX_V1';

  const navigateToPath = (path: string) => {
    navigate(
      //Support to render on Dashboard Full Page View mode
      checkMagicConfigurationFlow() ? path?.replace('/magic/', '/configuration/magic/') : path,
    );
  };

  return (
    <>
      <OnboardedMerchantTabHeader>
        <Box display="flex" flexDirection="column" gap="spacing.3">
          <Heading size="xlarge" weight="semibold">
            Welcome to Checkout360
          </Heading>
          <Text weight="regular" color="surface.text.gray.subtle">
            Manage & Configure COD rules, Checkout settings, RTO prediction, and other features here
          </Text>
        </Box>
      </OnboardedMerchantTabHeader>
      <Box display="flex" flexDirection="column" paddingX="spacing.8" gap="spacing.7">
        <Text size="large" weight="medium">
          Feature Settings
        </Text>
        <Box
          paddingRight="spacing.3"
          width="100%"
          maxWidth="600px"
          borderColor="surface.border.gray.muted"
          borderRadius="medium"
          display="flex"
          justifyContent="space-between"
        >
          <Box
            paddingX="spacing.7"
            paddingY="spacing.4"
            display="flex"
            alignItems="center"
            gap="spacing.6"
          >
            <CashIcon size="2xlarge" color="surface.icon.onSea.onSubtle" />
            <Heading color="surface.text.gray.subtle" weight="semibold" size="medium">
              COD Configuration
            </Heading>
          </Box>
          <Box
            backgroundColor="surface.background.gray.moderate"
            paddingX="spacing.7"
            paddingY="spacing.4"
            marginY="spacing.3"
            display="flex"
            alignItems="center"
            gap="spacing.7"
          >
            <Link
              icon={EditInlineIcon}
              iconPosition="left"
              onClick={() => navigateToPath(ROUTES.COD_CONFIG[MAGICX_VERSION])}
            >
              Configure
            </Link>
            <Link icon={ExternalLinkIcon} iconPosition="left" href={DOCS_LINKS.COD} target="_blank">
              User Manual
            </Link>
          </Box>
        </Box>
        <Box
          paddingRight="spacing.3"
          width="100%"
          maxWidth="600px"
          borderColor="surface.border.gray.muted"
          borderRadius="medium"
          display="flex"
          justifyContent="space-between"
        >
          <Box
            paddingX="spacing.7"
            paddingY="spacing.4"
            display="flex"
            alignItems="center"
            gap="spacing.6"
          >
            <SparklesIcon size="2xlarge" color="surface.icon.onSea.onSubtle" />
            <Heading color="surface.text.gray.subtle" weight="semibold" size="medium">
              RTO Prediction
            </Heading>
          </Box>
          <Box
            backgroundColor="surface.background.gray.moderate"
            paddingX="spacing.7"
            paddingY="spacing.4"
            marginY="spacing.3"
            display="flex"
            alignItems="center"
            gap="spacing.7"
          >
            <Link
              icon={EditInlineIcon}
              iconPosition="left"
              onClick={() => navigateToPath(ROUTES.RTO_CONFIG[MAGICX_VERSION])}
            >
              Configure
            </Link>
            <Link icon={ExternalLinkIcon} iconPosition="left" href={DOCS_LINKS.RTO} target="_blank">
              User Manual
            </Link>
          </Box>
        </Box>
        <Box
          paddingRight="spacing.3"
          width="100%"
          maxWidth="600px"
          borderColor="surface.border.gray.muted"
          borderRadius="medium"
          display="flex"
          justifyContent="space-between"
        >
          <Box
            paddingX="spacing.7"
            paddingY="spacing.4"
            display="flex"
            alignItems="center"
            gap="spacing.6"
          >
            <MagicCheckoutIcon size="2xlarge" color="surface.icon.onSea.onSubtle" />
            <Heading color="surface.text.gray.subtle" weight="semibold" size="medium">
              Checkout Settings
            </Heading>
          </Box>
          <Box
            backgroundColor="surface.background.gray.moderate"
            paddingX="spacing.7"
            paddingY="spacing.4"
            marginY="spacing.3"
            display="flex"
            alignItems="center"
            gap="spacing.7"
          >
            <Link
              icon={EditInlineIcon}
              iconPosition="left"
              onClick={() => navigateToPath(ROUTES.CHECKOUT_CONFIG[MAGICX_VERSION])}
            >
              Configure
            </Link>
            <Link
              icon={ExternalLinkIcon}
              iconPosition="left"
              href={DOCS_LINKS.CHECKOUT}
              target="_blank"
            >
              User Manual
            </Link>
          </Box>
        </Box>
      </Box>
    </>
  );
};

export default OnboardedMerchantTab;
