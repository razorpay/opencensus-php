import { ReactNode } from 'react';

export interface AccordionProps {
  header: ReactNode;
  body: ReactNode;
  footer?: ReactNode;
  open?: boolean;
}

export interface AccordionHeaderProps {
  isOpen: boolean;
}

export interface AccordionBodyProps {
  isOpen: boolean;
}

export interface AccordionFooterProps {
  isOpen: boolean;
}
