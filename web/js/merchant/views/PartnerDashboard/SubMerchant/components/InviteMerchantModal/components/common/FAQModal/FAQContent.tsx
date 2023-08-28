import React from 'react';
import {
  Accordion,
  AccordionItem,
  Box,
  Link,
  Text,
  ExternalLinkIcon,
} from '@razorpay/blade/components';

import { trackInviteFlowCommonCtaClicked } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/utils/analytics';

import BankAccount from './icons/bank-account.svg';
import BusinessAddress from './icons/business-address.svg';
import BusinessPanCard from './icons/business-pan-card.svg';
import GSTIN from './icons/gstin.svg';
import QuickerOnboarding from './icons/quicker-onboarding.svg';
import ValueAddedService from './icons/value-added-service.svg';

// Utility component
const RowTextItem = ({ children }) => {
  return (
    <Text weight="bold" size="small" color="surface.text.subtle.lowContrast">
      {children}
    </Text>
  );
};

export const titlesForTracking = {
  WHY_ASSIST: 'Why should I assist my client with their KYC?',
  WHAT_TO_DO: 'What will I have to do to perform their KYC?',
};
type FAQContentProps = {
  noTopMargin?: boolean;
  inviteFlow: string;
  productType: string;
};
const FAQContent = ({
  noTopMargin = false,
  inviteFlow,
  productType,
}: FAQContentProps): JSX.Element => {
  const onTitleClick = ({ expandedIndex, message }) => {
    if (expandedIndex !== -1) {
      trackInviteFlowCommonCtaClicked({
        inviteFlow,
        productType,
        ctaClicked: 'Accordian',
        message,
      });
    }
  };
  const onKnowMoreClick = () => {
    trackInviteFlowCommonCtaClicked({
      inviteFlow,
      productType,
      ctaClicked: 'Know more about the process',
    });
  };
  return (
    <Box
      display="flex"
      flexDirection="column"
      gap="spacing.3"
      justifyContent="center"
      alignItems="center"
      marginTop={noTopMargin ? 'spacing.0' : 'spacing.9'}
      marginBottom="spacing.4"
    >
      <Box
        display="flex"
        flexDirection="column"
        gap="spacing.3"
        justifyContent="center"
        alignItems="center"
        backgroundColor="surface.background.level3.lowContrast"
      >
        <Accordion
          onExpandChange={({ expandedIndex }) =>
            onTitleClick({ expandedIndex, message: titlesForTracking.WHY_ASSIST })
          }
          defaultExpandedIndex={0}
        >
          <AccordionItem
            // eslint-disable-next-line
            // @ts-ignore
            title={
              <Text weight="bold" size="medium">
                {titlesForTracking.WHY_ASSIST}
              </Text>
            }
          >
            <Box display="flex" flexDirection="column" gap="spacing.5">
              <Box display="flex" flexDirection="column" gap="spacing.7">
                <Box display="flex" gap="spacing.5" alignItems="center">
                  <Box>
                    <img src={QuickerOnboarding} />
                  </Box>
                  <Box display="flex" flexDirection="column" gap="spacing.2">
                    <Text weight="bold" size="small">
                      Quicker onboarding
                    </Text>
                    <Text variant="caption">
                      Performing KYC for your clients decreases their onboarding time by 50%
                    </Text>
                  </Box>
                </Box>
                <Box display="flex" gap="spacing.5" alignItems="center">
                  <Box>
                    <img src={ValueAddedService} />
                  </Box>
                  <Box display="flex" flexDirection="column" gap="spacing.2">
                    <Text weight="bold" size="small">
                      Value added services
                    </Text>
                    <Text variant="caption">
                      Ensure easy onboarding and strengthen client relationships.
                    </Text>
                  </Box>
                </Box>
              </Box>
            </Box>
          </AccordionItem>
        </Accordion>
      </Box>
      <Box
        display="flex"
        flexDirection="column"
        gap="spacing.3"
        justifyContent="center"
        alignItems="center"
        backgroundColor="surface.background.level3.lowContrast"
      >
        <Accordion
          onExpandChange={({ expandedIndex }) =>
            onTitleClick({ expandedIndex, message: titlesForTracking.WHAT_TO_DO })
          }
        >
          <AccordionItem
            // Discussion for medium size in Accordian: https://razorpay.slack.com/archives/CMQ3RBHEU/p1692710756767359?thread_ts=1692685789.385699&cid=CMQ3RBHEU
            // eslint-disable-next-line
            // @ts-ignore
            title={
              <Text weight="bold" size="medium">
                {titlesForTracking.WHAT_TO_DO}
              </Text>
            }
          >
            <Box display="flex" flexDirection="column" gap="spacing.5">
              <Box display="flex" flexDirection="column" gap="spacing.5">
                <Box display="flex" flexDirection="column" gap="spacing.3">
                  <Text>You need to have the following details of your client:</Text>
                  <Box display="flex" flexDirection="column" gap="spacing.2">
                    <Box display="flex" gap="spacing.3" alignItems="center" flex="1">
                      <Box>
                        <Box>
                          <img src={BusinessPanCard} />
                        </Box>
                      </Box>
                      <RowTextItem>Business PAN Card</RowTextItem>
                    </Box>
                    <Box display="flex" gap="spacing.3" alignItems="center" flex="1">
                      <Box>
                        <Box>
                          <img src={GSTIN} />
                        </Box>
                      </Box>
                      <RowTextItem>GSTIN</RowTextItem>
                    </Box>
                    <Box display="flex" gap="spacing.3" alignItems="center" flex="1">
                      <Box>
                        <img src={BusinessAddress} />
                      </Box>
                      <RowTextItem>
                        Business Address{' '}
                        <Text
                          display="inline-block"
                          size="small"
                          color="surface.text.subtle.lowContrast"
                        >
                          (self declared, no proof needed)
                        </Text>
                      </RowTextItem>
                    </Box>
                    <Box display="flex" gap="spacing.3" alignItems="center" flex="1">
                      <Box>
                        <img src={BankAccount} />
                      </Box>
                      <RowTextItem>Bank Account details</RowTextItem>
                    </Box>
                  </Box>
                </Box>
                <Link
                  href="https://razorpay.com/docs/partners/resellers/perform-kyc/"
                  target="_blank"
                  rel="noopener noreferer"
                  icon={ExternalLinkIcon}
                  iconPosition="right"
                  size="medium"
                  onClick={onKnowMoreClick}
                >
                  Know more about the process
                </Link>
              </Box>
            </Box>
          </AccordionItem>
        </Accordion>
      </Box>
    </Box>
  );
};
export default FAQContent;
