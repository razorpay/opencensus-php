import React from 'react';
import { Box, Heading, Badge } from '@razorpay/blade/components';
import useAccordionSectionData from '@FTUX/hooks/useAccordionSectionData';
import CollectPaymentsBannerImg from 'apps/onboarding-experience/src/assets/CollectPaymentsBanner.svg';
import CollectPaymentsAccordion from './CollectPaymentsAccordion';

const AccordionSection = () => {
  const { activeStep, accordionData } = useAccordionSectionData();

  return (
    <Box>
      <Box
        display="flex"
        flexDirection={{
          base: 'column-reverse',
          l: 'row',
        }}
        alignItems="center"
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
            Start collecting payments in 3 easy steps
          </Heading>
          <Badge color="neutral" size="large">
            {activeStep || 0}/{accordionData.length || 0} COMPLETED
          </Badge>
        </Box>
        <Box
          width={{
            base: '100%',
            l: '360px',
          }}
        >
          <img src={CollectPaymentsBannerImg} alt="accordionHeader" width="100%" />
        </Box>
      </Box>
      <CollectPaymentsAccordion activeStep={activeStep} data={accordionData} />
    </Box>
  );
};

export default AccordionSection;
