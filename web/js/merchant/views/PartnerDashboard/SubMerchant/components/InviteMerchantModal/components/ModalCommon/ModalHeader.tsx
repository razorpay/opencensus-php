import React from 'react';
import { Box, CloseIcon, Divider, IconButton, Text } from '@razorpay/blade/components';
type ModalHeaderProps = {
  modalTitle: string | null;
  onDismiss: () => void;
  showDivider: boolean;
};

// Note: Current ModalHeader from blade doesn't support hiding the divider
const ModalHeader = ({
  modalTitle = null,
  onDismiss,
  showDivider = false,
}: ModalHeaderProps): JSX.Element => {
  return (
    <>
      <Box
        height={modalTitle ? 'auto' : '10px'}
        paddingBottom={modalTitle ? 'spacing.4' : 'spacing.0'}
        display="flex"
        flexDirection="row"
      >
        <Box paddingRight="spacing.5" flex="1 1 auto">
          {modalTitle ? (
            <Text weight="semibold" display="inline-block" size="large">
              {modalTitle}
            </Text>
          ) : null}
        </Box>
        <Box display="inline-block" height="28px">
          <IconButton
            icon={CloseIcon}
            emphasis="intense"
            accessibilityLabel="Close"
            onClick={onDismiss}
            size="large"
          />
        </Box>
      </Box>
      {showDivider ? (
        <Box paddingBottom="spacing.5">
          <Divider />
        </Box>
      ) : null}
    </>
  );
};

export default ModalHeader;
