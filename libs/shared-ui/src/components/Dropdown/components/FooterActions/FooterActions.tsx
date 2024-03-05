import React from 'react';
import { Box, Button } from '@razorpay/blade/components';
import { FooterActionsProps } from './types';

const FooterActions = ({
  tempSelectedOptions,
  onClear,
  onApply,
}: FooterActionsProps): JSX.Element => {
  return (
    <Box display="flex" gap="spacing.5">
      <Button
        isFullWidth
        variant="secondary"
        isDisabled={!tempSelectedOptions.length}
        onClick={onClear}
      >
        Clear
      </Button>
      <Button isFullWidth isDisabled={!tempSelectedOptions.length} onClick={onApply}>
        Apply
      </Button>
    </Box>
  );
};

export default FooterActions;
