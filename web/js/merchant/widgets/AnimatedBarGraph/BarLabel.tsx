import React from 'react';
import { BarLabelWrapper } from './styled';
import { Badge } from '@razorpay/blade/components';
import { BarLabelAlignment, BarVariant } from './types';

interface BarLabelProps {
  variant: BarVariant;
  align: BarLabelAlignment;
  label: string | null;
}

function BarLabel({ variant, align, label }: BarLabelProps): JSX.Element | null {
  if (!label) {
    return null;
  }
  return (
    <BarLabelWrapper align={align}>
      <Badge color={variant}>{label}</Badge>
    </BarLabelWrapper>
  );
}

export default BarLabel;
