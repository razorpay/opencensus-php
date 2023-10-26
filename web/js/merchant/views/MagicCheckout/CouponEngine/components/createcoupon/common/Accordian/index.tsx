import React, { useState, useEffect } from 'react';

// ui imports
import {
  AccordionContainer,
  AccordionHeader,
  ChevronIcon,
  AccordionBody,
  AccordionFooter,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/common/Accordian/styled';

// types imports
import { AccordionProps } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/common/Accordian/types';

export const Accordion: React.FC<AccordionProps> = ({ header, body, footer, open = false }) => {
  const [isOpen, setIsOpen] = useState(open);

  const toggleAccordion = () => {
    setIsOpen(!isOpen);
  };

  useEffect(() => {
    // Update isOpen when the open prop changes
    setIsOpen(open);
  }, [open]);

  return (
    <AccordionContainer>
      <AccordionHeader onClick={toggleAccordion} isOpen={isOpen}>
        {header}
        <ChevronIcon className="i i-chevron-down" isOpen={isOpen} />
      </AccordionHeader>
      <AccordionBody isOpen={isOpen}>{body}</AccordionBody>
      {footer && <AccordionFooter isOpen={isOpen}>{footer}</AccordionFooter>}
    </AccordionContainer>
  );
};
