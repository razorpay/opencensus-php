import React from 'react';
import { Box, Heading, Badge } from '@razorpay/blade/components';
import useAccordionSectionData from '@FTUX/hooks/useAccordionSectionData';
import AccordionHeaderBg from '@FTUX/assets/AccordionHeaderBg.svg';
import CollectPaymentsAccordion from './CollectPaymentsAccordion';

const AccordionSection = () => {
  const { activeStep, accordionData } = useAccordionSectionData();

  return (
    <Box
      backgroundColor="surface.background.gray.intense"
      borderRadius="medium"
      borderColor="surface.border.gray.muted"
      padding="spacing.5"
    >
      <Box
        display="flex"
        flexDirection={{
          base: 'column-reverse',
          m: 'row',
        }}
        gap="spacing.4"
      >
        <Box flex="1" display="flex" flexDirection="column" gap="spacing.3">
          <Heading weight="semibold" size="medium">
            Start collecting payments in 3 easy steps
          </Heading>
          <Badge color="neutral" size="large">
            {(activeStep || 0) + 1}/{accordionData.length || 0} COMPLETED
          </Badge>
        </Box>
        <Box
          flex="1"
          width="100%"
          maxHeight={{
            base: 'auto',
            m: 'spacing.11',
          }}
        >
          <img src={AccordionHeaderBg} alt="accordionHeader" width="100%" height="100%" />
        </Box>
      </Box>
      <CollectPaymentsAccordion activeStep={activeStep} data={accordionData} />
    </Box>
  );
};

export default AccordionSection;
