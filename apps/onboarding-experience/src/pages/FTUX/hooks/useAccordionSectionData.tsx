import React from 'react';
import { AccordionDataType } from '../types/common';

const useAccordionSectionData = (): { activeStep: number; accordionData: AccordionDataType[] } => {
  // To be replaced with data from GQL calls and conditions
  const activeStep = 1;
  const accordionData = [
    {
      title: 'App links verified',
      content: 'Body',
    },
    {
      title: 'Set up your payment gateway',
      content: <>Body</>,
    },
    {
      title: 'Accept your first payment',
      content: <>Body</>,
    },
  ];

  return {
    activeStep,
    accordionData,
  };
};

export default useAccordionSectionData;
