import React from 'react';
import {
  ArrowLeftIcon,
  ArrowRightIcon,
  Box,
  HomeIcon,
  LockIcon,
  MoreVerticalIcon,
  RotateClockWiseIcon,
  StarIcon,
  Text,
} from '@razorpay/blade/components';

type ChromeSearchBar = {
  isDesktopPreview: boolean;
  isMobile: boolean;
};

const ChromeSearchBar = ({ isDesktopPreview, isMobile }: ChromeSearchBar) => {
  return (
    <Box
      minWidth={isDesktopPreview ? '800px' : 'spacing.0'}
      width={isDesktopPreview || isMobile ? '100%' : '400px'}
    >
      {isDesktopPreview ? (
        <Box
          borderBottomWidth="thinner"
          borderBottomColor="surface.border.gray.normal"
          backgroundColor="surface.background.gray.intense"
          borderTopLeftRadius="large"
          borderTopRightRadius="large"
          padding={['spacing.2', 'spacing.3']}
        >
          <Box display="flex" alignItems="center" gap="spacing.4">
            <ArrowLeftIcon color="interactive.icon.gray.normal" size="small" />
            <ArrowRightIcon color="interactive.icon.gray.muted" size="small" />
            <RotateClockWiseIcon color="interactive.icon.gray.normal" size="small" />
            <HomeIcon color="interactive.icon.gray.normal" size="small" />
            <Box
              display="flex"
              flex={1}
              padding={['spacing.2', 'spacing.3']}
              justifyContent="space-between"
              alignItems="center"
              borderRadius="2xlarge"
              backgroundColor="surface.background.gray.subtle"
            >
              <Box display="flex" alignItems="center" gap="spacing.3">
                <LockIcon color="interactive.icon.gray.normal" size="small" />
                <Text color="surface.text.gray.normal" size="xsmall">
                  https://pages.razorpay.com/
                </Text>
              </Box>
              <StarIcon color="interactive.icon.gray.normal" size="small" />
            </Box>
            <MoreVerticalIcon color="interactive.icon.gray.normal" size="small" />
          </Box>
        </Box>
      ) : (
        <Box
          backgroundColor="surface.background.gray.intense"
          borderTopLeftRadius="large"
          borderTopRightRadius="large"
          padding="spacing.3"
        >
          <Box display="flex" alignItems="center" justifyContent="space-between" gap="spacing.5">
            <Box
              display="flex"
              flex={1}
              padding={['spacing.4', 'spacing.5']}
              justifyContent="space-between"
              alignItems="center"
              borderRadius="2xlarge"
              backgroundColor="surface.background.gray.subtle"
            >
              <Box display="flex" alignItems="center" gap="spacing.3">
                <LockIcon color="interactive.icon.information.normal" size="small" />
                <Text color="surface.text.gray.normal" size="xsmall">
                  {`https://pages.razorpay.com/`}
                </Text>
              </Box>
            </Box>
            <Box
              padding={['spacing.0', 'spacing.2']}
              backgroundColor="surface.background.sea.intense"
              display="flex"
              justifyContent="center"
              alignItems="center"
              borderRadius="medium"
            >
              <Text size="small" color="surface.text.staticWhite.normal">
                C
              </Text>
            </Box>
            <MoreVerticalIcon color="interactive.icon.gray.normal" size="small" />
          </Box>
        </Box>
      )}
    </Box>
  );
};

export default ChromeSearchBar;
