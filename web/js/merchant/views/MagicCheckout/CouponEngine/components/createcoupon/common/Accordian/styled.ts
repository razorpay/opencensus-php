import styled from 'styled-components';

// type imports
import {
  AccordionHeaderProps,
  AccordionBodyProps,
  AccordionFooterProps,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/common/Accordian/types';

export const AccordionContainer = styled.div`
  background: white;
  border: 1px solid #e2e3e3;
  border-radius: 10px;
`;

export const AccordionHeader = styled.div<AccordionHeaderProps>`
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  font-size: 16px;
  font-weight: 600;
  line-height: 20px;
  padding: 18px;
  border-bottom: ${({ isOpen }) => (isOpen ? '1px solid #EBECED' : 'none')};
`;

export const ChevronIcon = styled.i<AccordionHeaderProps>`
  transform: ${({ isOpen }) => (isOpen ? 'rotate(180deg)' : 'rotate(0deg)')};
  transition: transform 0.3s ease-in-out;
  font-size: 24px;
`;

export const AccordionBody = styled.div<AccordionBodyProps>`
  padding: 18px;
  display: ${({ isOpen }) => (isOpen ? 'block' : 'none')};
  transition: display 0.3s ease-in-out;
`;

export const AccordionFooter = styled.div<AccordionFooterProps>`
  padding: 18px;
  display: ${({ isOpen }) => (isOpen ? 'block' : 'none')};
  transition: display 0.3s ease-in-out;
  border-top: 1px solid #ebeced;
`;
