import {
  BladeProvider,
  Box,
  Button,
  DownloadIcon,
  Heading,
  Link,
  PayrollAddonsIcon,
  Text,
  TextProps,
  UserPlusIcon,
} from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { PwaInstallBg, RazorpayLogoWhite } from 'apps/pos/src/assets';
import useOnboardingStore from 'apps/pos/src/bootstrap/Store';
import React from 'react';
import { BeforeInstallPromptEvent } from './types';

declare global {
  interface WindowEventMap {
    beforeinstallprompt: BeforeInstallPromptEvent;
  }
}

const Instruction = ({
  title,
  content,
  icon,
}: {
  title: string;
  content: TextProps<{ variant: 'body' }>['children'];
  icon: React.ReactNode;
}) => {
  return (
    <Box
      display="grid"
      gap="spacing.6"
      backgroundColor="surface.background.gray.intense"
      paddingX="20px"
      gridTemplateColumns="auto 1fr"
    >
      {icon}
      <Box>
        <Text size="medium" color="surface.text.gray.normal" weight="semibold">
          {title}
        </Text>
        <Text size="small" color="surface.text.gray.subtle" marginTop="spacing.3">
          {content}
        </Text>
        <Box
          height="1px"
          width="100%"
          marginTop="spacing.6"
          backgroundColor="surface.background.cloud.subtle"
        />
      </Box>
    </Box>
  );
};

const PwaInstall = () => {
  const pwaPrompt = useOnboardingStore((state) => state.pwaPrompt);
  const onInstallClick = () => {
    if (pwaPrompt) {
      pwaPrompt.prompt();
    }
  };

  const DASHBOARD_LINK = `/app/pos-sales`;

  return (
    <Box marginTop="-4px" flexGrow="1" display="grid" gridTemplateRows="auto 1fr">
      <BladeProvider colorScheme="dark" themeTokens={bladeTheme}>
        <Box
          backgroundImage={`url("${PwaInstallBg}")`}
          backgroundPosition="20% 30%"
          backgroundColor="surface.background.gray.moderate"
          paddingTop="34px"
          paddingBottom="50px"
          paddingX="20px"
        >
          <img
            src={RazorpayLogoWhite}
            alt="Razorpay"
            width="100px"
            height="21px"
            style={{ objectFit: 'contain' }}
          />
          <Heading
            marginTop="28px"
            size="2xlarge"
            color="surface.text.gray.normal"
            weight="regular"
          >
            Onboard Merchants with{' '}
            <Heading size="2xlarge" as="span" color="surface.text.onSea.onSubtle" weight="regular">
              Razorpay's Agent App
            </Heading>
          </Heading>
        </Box>
      </BladeProvider>

      <Box
        marginTop="-4px"
        display="flex"
        flexDirection="column"
        justifyContent="space-between"
        borderTopLeftRadius="large"
        borderTopRightRadius="large"
        backgroundColor="surface.background.gray.intense"
        paddingTop="42px"
        paddingBottom="8px"
      >
        <Box display="flex" flexDirection="column" gap="16px">
          <Instruction
            title="Install App on your device"
            content="'Install Now' to get started right away"
            icon={<PayrollAddonsIcon size="xlarge" color="surface.icon.gray.muted" />}
          />
          <Instruction
            title="Get started seamlessly"
            content={
              <>
                Once installed, launch it by{' '}
                <Link size="small" href={DASHBOARD_LINK}>
                  Accessing Dashboard
                </Link>
              </>
            }
            icon={<UserPlusIcon size="xlarge" color="surface.icon.gray.muted" />}
          />
        </Box>

        <Box borderTopColor="surface.border.gray.muted" borderTopWidth="thin" padding="spacing.3">
          <Button isFullWidth icon={DownloadIcon} iconPosition="right" onClick={onInstallClick}>
            Install Now
          </Button>
        </Box>
      </Box>
    </Box>
  );
};

export default PwaInstall;
