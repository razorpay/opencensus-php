import React from 'react';
import { Box, Text, Button, ArrowRightIcon, DownloadIcon } from '@razorpay/blade/components';

import { DownloadIconWrapper } from '../../components/styled';
import { ActionContainerProps } from '../types';

const ActionContainer = ({
  heading,
  description,
  buttonText,
  showDownloadIcon = false,
  onButtonClick,
}: ActionContainerProps) => {
  return (
    <Box
      position="relative"
      backgroundColor="surface.background.gray.moderate"
      padding={['spacing.5', 'spacing.7']}
      overflow="hidden"
      marginBottom="spacing.5"
    >
      <Box display="flex" flexDirection="column" gap="spacing.7">
        <Box
          display="flex"
          flexDirection="column"
          gap="spacing.3"
          paddingRight={showDownloadIcon ? 'spacing.10' : 'spacing.0'}
          width={showDownloadIcon ? '85%' : '100%'}
        >
          <Text weight="semibold" size="large" marginBottom="spacing.4">
            {heading}
          </Text>
          <Text>{description}</Text>
        </Box>
        <Box>
          <Button
            size="small"
            type="button"
            variant="primary"
            iconPosition="right"
            icon={ArrowRightIcon}
            onClick={onButtonClick}
          >
            {buttonText}
          </Button>
        </Box>
      </Box>
      {showDownloadIcon && (
        <Box
          position="absolute"
          width="152px"
          height="152px"
          borderRadius="round"
          display="flex"
          alignItems="center"
          justifyContent="center"
          backgroundColor="surface.background.gray.intense"
          top="-30px"
          right="-4px"
          transform="rotate(-14deg)"
        >
          <DownloadIconWrapper>
            <DownloadIcon size="2xlarge" color="interactive.icon.gray.disabled" />
          </DownloadIconWrapper>
        </Box>
      )}
    </Box>
  );
};

export default ActionContainer;
