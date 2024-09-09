import React from 'react';
import { Box } from '@razorpay/blade/components';

type ModalFooterProps = { children: React.ReactNode };
// Note: Blade's ModalFooter couldn't be used as an indirect child which is needed for multi-step form inside modal body.
const ModalFooter = ({ children }: ModalFooterProps): JSX.Element => (
  // This represents a ModalFooter container with absolute position
  <Box position="absolute" height="76px" width="100%" left="0px" bottom="0px">
    <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%" padding="spacing.5">
      {children}
    </Box>
  </Box>
);

export default ModalFooter;
