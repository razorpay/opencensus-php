import React from 'react';
import { Box, Heading, Badge } from '@razorpay/blade/components';
import { useMerchantContext } from '@FTUX/context/MerchantContext';
import useAccordionSectionData from '@FTUX/hooks/useAccordionSectionData';
import collectPaymentsBannerImg from '@OnboardingExperienceAssets/CollectPaymentsBanner.svg';
import CollectPaymentsAccordion from './CollectPaymentsAccordion';

const AccordionSection = () => {
  const { merchantData } = useMerchantContext();
  const { completedSteps, expandedStep, setExpandedStep, accordionData, isAppOnlyMerchant } =
    useAccordionSectionData();

  const isMerchantActivated = merchantData?.merchantById?.activation?.isActivated;

  return (
    <Box>
      <Box
        display="flex"
        flexDirection={{
          base: 'column-reverse',
          l: 'row',
        }}
        alignItems={{
          base: 'flex-start',
          m: 'center',
        }}
        gap="spacing.4"
        marginBottom="spacing.5"
      >
        <Box
          flex="1"
          display="flex"
          flexDirection="column"
          gap="spacing.3"
          paddingLeft={{
            base: 'spacing.5',
            m: 'spacing.7',
          }}
        >
          <Heading weight="semibold" size="medium">
            {isMerchantActivated
              ? `Set up your ${isAppOnlyMerchant ? 'apps' : 'website'}`
              : `Test collecting payments in ${accordionData.length || 0} easy steps`}
          </Heading>
          <Badge color="neutral" size="large">
            {completedSteps || 0}/{accordionData.length || 0} COMPLETED
          </Badge>
        </Box>
        <Box
          width={{
            base: '100%',
            l: '360px',
          }}
        >
          <img src={collectPaymentsBannerImg} alt="accordionHeader" width="100%" />
        </Box>
      </Box>
      <CollectPaymentsAccordion
        data={accordionData}
        completedSteps={completedSteps}
        expandedStep={expandedStep}
        setExpandedStep={setExpandedStep}
      />
    </Box>
  );
};

export default AccordionSection;
