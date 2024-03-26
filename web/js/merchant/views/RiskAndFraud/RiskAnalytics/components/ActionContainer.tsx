import React from 'react';
import { Box, Text, Button, ArrowRightIcon, DownloadIcon } from '@razorpay/blade/components';

import { DownloadIconWrapper } from '../../components/styled';
import { ActionContainerProps } from '../types';

const ActionContainer = ({
  heading,
  description,
  note,
  buttonText,
  showDownloadIcon = false,
  onButtonClick,
}: ActionContainerProps) => {
  return (
    <Box
      position="relative"
      backgroundColor="surface.background.level3.lowContrast"
      padding={['spacing.5', 'spacing.7']}
      overflow="hidden"
      marginBottom="spacing.5"
    >
      <Box display="flex" flexDirection="column" gap="spacing.7">
        <Box
          display="flex"
          flexDirection="column"
          gap="spacing.3"
          paddingRight="spacing.10"
          width="85%"
        >
          <Text weight="bold" size="large" marginBottom="spacing.4">
            {heading}
          </Text>
          <Text>{description}</Text>
          {note && (
            <Text>
              <Text as="span" weight="bold">
                Note:{' '}
              </Text>
              {note}
            </Text>
          )}
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
          backgroundColor="surface.background.level2.lowContrast"
          top="-30px"
          right="-4px"
          transform="rotate(-14deg)"
        >
          <DownloadIconWrapper>
            <DownloadIcon size="2xlarge" color="surface.action.icon.disabled.lowContrast" />
          </DownloadIconWrapper>
        </Box>
      )}
    </Box>
  );
};

export default ActionContainer;
