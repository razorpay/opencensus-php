import React from 'react';
import { Box, Divider } from '@razorpay/blade/components';

type ModalFooterProps = { children: React.ReactNode };
// Note: Blade's ModalFooter couldn't be used as an indirect child which is needed for multi-step form inside modal body.
const ModalFooter = ({ children }: ModalFooterProps): JSX.Element => {
  return (
    <>
      <Divider />
      <Box
        display="flex"
        gap="spacing.3"
        justifyContent="flex-end"
        width="100%"
        paddingTop="spacing.5"
      >
        {children}
      </Box>
    </>
  );
};
export default ModalFooter;
