import { Box, CopyIcon, IconButton, Popover, Text, Tooltip } from '@razorpay/blade/components';
import React, { ReactNode, useState } from 'react';

interface CopyWrapperProps {
  children: ReactNode;
  onClick: () => void;
}

export const CopyWrapper: React.FC<CopyWrapperProps> = ({ children, onClick }) => {
  const [isCopied, setIsCopied] = useState(false);
  const handleCopyClick = () => {
    onClick();
    setIsCopied(true);
  };

  return (
    <Box display="flex" alignItems="center">
      <Box marginRight="spacing.3">{children}</Box>
      <Tooltip
        content={isCopied ? 'Copied' : 'Copy'}
        onOpenChange={({ isOpen }) => {
          if (!isOpen) {
            setTimeout(() => setIsCopied(false), 100);
          }
        }}
      >
        <IconButton
          accessibilityLabel="Copy merchant ID"
          onClick={handleCopyClick}
          icon={CopyIcon}
        />
      </Tooltip>
    </Box>
  );
};
