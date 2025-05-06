import React, { useState } from 'react';
import {
  Accordion,
  AccordionItem,
  AccordionItemBody,
  AccordionItemHeader,
  BladeProvider,
  Box,
  Button,
  Heading,
  Link,
  Text,
  Theme,
} from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { useNavigate } from 'react-router-dom';
import styled from 'styled-components';
import BankingSvg from './BankingSvg';
import HomeSvg from './HomeSvg';
import PayrollSvg from './PayrollSvg';
import RzpSvg from './RzpSvg';
import TrendingUpSvg from './TrendingUpSvg';
import { FtuxConsentFooterProps } from '../types';
import { setItem } from 'common/utils/localStorage';

const StyledIconWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
            width:98px;
            height:98px;
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:center;
            gap: ${theme.spacing[3]}px;
            background-color: ${theme.colors.surface.background.gray.intense};
            border-radius: ${theme.border.radius['round']};
            position:absolute;
            top:50%;
            left:50%;
            transform:translate(-50%, -50%);
            `,
);

const FtuxModalIcon = ({
  icon,
  iconLabel,
  styles,
}: {
  icon: JSX.Element;
  iconLabel: string;
  styles: Record<string, unknown>;
}): React.ReactElement => {
  return (
    <Box
      width="98px"
      height="98px"
      borderRadius="large"
      backgroundColor="surface.background.gray.subtle"
      display="flex"
    >
      <Box
        width="100%"
        display="flex"
        flexDirection="column"
        alignItems="center"
        gap="spacing.3"
        justifyContent="center"
        {...styles}
      >
        {icon}
        <Text size="xsmall" weight="semibold" color="surface.text.gray.subtle">
          {iconLabel}
        </Text>
      </Box>
    </Box>
  );
};

export const FtuxConsentBody = (): React.ReactElement => {
  const [isExpanded, setIsExpanded] = useState(false);
  return (
    <Box display="flex" flexDirection="column" gap="spacing.7">
      <Box display="flex" alignSelf="start" width="200px" maxWidth="200px">
        <Box width="100%" display="flex" flexDirection="column" gap="spacing.2" position="relative">
          <Box display="flex" gap="spacing.2">
            <FtuxModalIcon
              icon={<RzpSvg />}
              iconLabel={'PAYMENTS'}
              styles={{
                marginBottom: 'spacing.7',
                marginRight: 'spacing.7',
              }}
            />
            <FtuxModalIcon
              icon={<BankingSvg />}
              iconLabel={'BANKING+'}
              styles={{
                marginBottom: 'spacing.7',
                marginLeft: 'spacing.7',
              }}
            />
          </Box>

          <BladeProvider themeTokens={bladeTheme} colorScheme="dark">
            <StyledIconWrapper>
              <HomeSvg />
              <Text size="small" color="surface.text.staticWhite.normal" weight="semibold">
                HOME
              </Text>
            </StyledIconWrapper>
          </BladeProvider>

          <Box display="flex" gap="spacing.2">
            <FtuxModalIcon
              icon={<PayrollSvg />}
              iconLabel={'PAYROLL'}
              styles={{
                marginTop: 'spacing.7',
                marginRight: 'spacing.7',
              }}
            />
            <FtuxModalIcon
              icon={<TrendingUpSvg />}
              iconLabel={'ENGAGE'}
              styles={{
                marginTop: 'spacing.7',
                marginLeft: 'spacing.7',
              }}
            />
          </Box>
        </Box>
      </Box>

      <Box display="flex" flexDirection="column">
        <Heading size="medium" weight="semibold">
          Introducing the new, all-in-one
        </Heading>
        <Heading size="2xlarge" weight="semibold">
          Razorpay Home
        </Heading>
        <Text size="medium" color="surface.text.gray.subtle" marginTop="spacing.4">
          The one place for you to keep track of everything on Razorpay. Don't worry, we have
          ensured that only the relevant folks have access to data.
        </Text>
      </Box>

      <Accordion
        variant="filled"
        onExpandChange={({ expandedIndex }) => {
          setIsExpanded(expandedIndex < 0 ? false : true);
          setItem('hasUserInteractedWithHomeConsent', 'true');
        }}
      >
        <AccordionItem>
          <AccordionItemHeader
            title="By proceeding, you agree to Razorpay's terms and conditions"
            trailing={<Link href="#">{isExpanded ? 'Show Less' : 'See TnCs'}</Link>}
          />
          <AccordionItemBody
            _description={
              'By proceeding, you consent to Razorpay collecting, consolidating, and displaying information regarding all services you avail or you may avail from entities within the Razorpay Group under a unified dashboard. You acknowledge and agree that the consolidated information may include data shared between Razorpay entities solely for this purpose. You may withdraw your consent at any time by raising a support ticket. For clarity, “Razorpay Group” shall mean Razorpay Software Private Limited and its affiliates, subsidiaries, and other group entities that operate under common ownership or control.'
            }
          />
        </AccordionItem>
      </Accordion>
    </Box>
  );
};

export const FtuxConsentFooter = ({
  onOptOut,
  onProceed,
}: FtuxConsentFooterProps): React.ReactElement => {
  const navigate = useNavigate();

  const handleOptOutClick = () => {
    onOptOut();
  };

  const handleProceedClick = () => {
    //TODO: navigate to the one-home dashboard
    // close the modal or sheet
    onProceed();
  };

  return (
    <Box width="100%" display="flex" gap="spacing.5" justifyContent="flex-end">
      <Button variant="tertiary" color="primary" size="medium" onClick={handleOptOutClick}>
        I want to opt out
      </Button>
      <Button variant="primary" color="primary" size="medium" onClick={handleProceedClick}>
        Proceed
      </Button>
    </Box>
  );
};
