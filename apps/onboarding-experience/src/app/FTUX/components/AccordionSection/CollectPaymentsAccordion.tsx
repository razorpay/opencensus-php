import React from 'react';
import {
  Box,
  Accordion,
  AccordionItem,
  AccordionItemHeader,
  AccordionItemBody,
} from '@razorpay/blade/components';
import { AccordionDataType } from '@FTUX/types/homepage';
import completedIcon from '@OnboardingExperienceAssets/AccordionIcons/Completed.svg';
import { activeStepIcons, upcomingStepIcons } from '@FTUX/constants/accordion';

type CollectPaymentsAccordionTypes = {
  data: AccordionDataType[];
  expandedStep: number;
  setExpandedStep: (step: number) => void;
  completedSteps: number;
};

// Currently icons are supported for 3 steps only, to add more steps, add relevant icons in the constants
const CollectPaymentsAccordion = ({
  data,
  expandedStep = 0,
  setExpandedStep,
  completedSteps,
}: CollectPaymentsAccordionTypes) => {
  const handleExpandChange = ({ expandedIndex: newExpandedIndex }: { expandedIndex: number }) => {
    setExpandedStep(newExpandedIndex);
  };

  const getIconForStep = (index: number) => {
    if (index < completedSteps) {
      // Completed step
      return completedIcon;
    } else if (index === completedSteps) {
      // Active step - use icon based on index
      return activeStepIcons[Math.min(index, activeStepIcons.length - 1)];
    } else {
      // Upcoming step - use icon based on index
      return upcomingStepIcons[Math.min(index, upcomingStepIcons.length - 1)];
    }
  };

  return (
    <Accordion
      expandedIndex={expandedStep}
      onExpandChange={handleExpandChange}
      variant="filled"
      maxWidth="auto"
      marginTop="spacing.4"
      data-analytics-name="collect-payments-accordion"
    >
      {data.length > 0 ? (
        data.map((item, index) => (
          <AccordionItem key={item.title} data-analytics-name={item.title}>
            <AccordionItemHeader
              leading={
                <img src={getIconForStep(index)} alt={`Step ${index + 1}`} width={24} height={24} />
              }
              title={item.title}
              titleSuffix={item.getTitleSuffix?.(expandedStep === index)}
              data-analytics-name={`collect-payments-accordion-header-${index + 1}`}
            />
            <AccordionItemBody>
              <Box paddingLeft={{ base: 'spacing.1', m: 'spacing.8' }}>{item.content}</Box>
            </AccordionItemBody>
          </AccordionItem>
        ))
      ) : (
        <></>
      )}
    </Accordion>
  );
};

export default CollectPaymentsAccordion;
