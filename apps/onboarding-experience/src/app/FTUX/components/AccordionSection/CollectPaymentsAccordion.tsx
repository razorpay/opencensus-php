import React, { useState } from 'react';
import {
  Box,
  Accordion,
  AccordionItem,
  AccordionItemHeader,
  AccordionItemBody,
  CheckIcon,
  Avatar,
} from '@razorpay/blade/components';
import { AccordionDataType } from '@FTUX/types/homepage';

const CollectPaymentsAccordion = ({
  data,
  activeStep = 0,
}: {
  data: AccordionDataType[];
  activeStep?: number;
}) => {
  const [expandedIndex, setExpandedIndex] = useState(activeStep);

  const handleExpandChange = ({ expandedIndex: newExpandedIndex }: { expandedIndex: number }) => {
    setExpandedIndex(newExpandedIndex);
  };

  return (
    <Accordion
      expandedIndex={expandedIndex}
      onExpandChange={handleExpandChange}
      variant="filled"
      maxWidth="auto"
      marginTop="spacing.4"
    >
      {data.length > 0 ? (
        data.map((item, index) => (
          <AccordionItem key={item.title}>
            <AccordionItemHeader
              leading={
                index < activeStep && !item.isIncomplete ? (
                  <Avatar icon={CheckIcon} color="positive" size="small" variant="circle" />
                ) : (
                  <Box
                    borderRadius="round"
                    borderWidth={index === activeStep ? 'thick' : 'none'}
                    borderStyle="dashed"
                    borderColor="surface.border.primary.normal"
                  >
                    <Avatar
                      name={`${index + 1}`}
                      color={index === activeStep ? 'primary' : 'neutral'}
                      size="small"
                      variant="circle"
                    />
                  </Box>
                )
              }
              title={item.title}
              titleSuffix={item.getTitleSuffix?.(expandedIndex === index)}
            />
            <AccordionItemBody>{item.content}</AccordionItemBody>
          </AccordionItem>
        ))
      ) : (
        <></>
      )}
    </Accordion>
  );
};

export default CollectPaymentsAccordion;
