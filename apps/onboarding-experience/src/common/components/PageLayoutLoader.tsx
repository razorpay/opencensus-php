import React from 'react';
import { Box, Divider, Skeleton } from '@razorpay/blade/components';

export const CardLoader = () => (
  <Box
    display="flex"
    flexDirection="column"
    gap="spacing.3"
    width="100%"
    height="100%"
    padding="spacing.5"
    backgroundColor="surface.background.gray.intense"
    borderRadius="medium"
    testID="card-loader"
  >
    <Skeleton height="65%" width="100%" borderRadius="medium" />
    <Box
      display="flex"
      gap="spacing.3"
      alignItems="start"
      flexDirection="column"
      justifyContent="space-between"
      width="100%"
      height="35%"
    >
      <Skeleton height="20%" width="50%" borderRadius="medium" />
      <Skeleton height="20%" width="100%" borderRadius="medium" />
      <Skeleton height="40%" width="100%" borderRadius="medium" />
      <Skeleton height="20%" width="50%" borderRadius="medium" />
    </Box>
  </Box>
);

export const BannerLoader = () => {
  return (
    <Box
      display="flex"
      flexDirection="column"
      gap="spacing.4"
      padding="spacing.7"
      borderRadius="medium"
      backgroundColor="surface.background.gray.intense"
      testID="banner-loader"
    >
      <Box
        display="flex"
        flexDirection={{
          base: 'column',
          l: 'row',
        }}
        justifyContent="space-between"
        height={{
          base: '150px',
          l: '50px',
        }}
        gap="spacing.4"
      >
        <Box
          display="flex"
          flexDirection="column"
          height="100%"
          gap="spacing.3"
          width={{
            base: '100%',
            l: '50%',
          }}
        >
          <Skeleton borderRadius="medium" height={{ base: '400%', l: '100%' }} width="100%" />
          <Skeleton borderRadius="medium" height={{ base: '60%', l: '100%' }} width="100%" />
        </Box>
        <Skeleton borderRadius="medium" height={{ base: '10%', l: '100%' }} width="50%" />
      </Box>
      <Skeleton borderRadius="medium" height={{ base: '150px', l: '80px' }} width="100%" />
    </Box>
  );
};

export const PageLayoutLoader = () => {
  return (
    <Box
      display="flex"
      flexDirection="column"
      flexWrap="wrap"
      gap={{ base: 'spacing.7', l: 'spacing.8' }}
      padding="spacing.3"
      testID="page-layout-loader"
    >
      <Skeleton borderRadius="medium" height={{ base: '100px', l: '50px' }} width="100%" />
      <Divider />
      <Box
        padding="spacing.7"
        borderRadius="medium"
        backgroundColor="surface.background.gray.intense"
        display={{ base: 'flex', l: 'none' }}
        flexDirection="column"
        gap="spacing.3"
      >
        <Skeleton borderRadius="medium" height="30px" width="100%" testID="mobile-section-title" />
        <Skeleton borderRadius="medium" height="20px" width="50%" />
      </Box>
      <Box
        display="flex"
        flexDirection="column"
        gap="spacing.4"
        padding="spacing.7"
        borderRadius="medium"
        backgroundColor="surface.background.gray.intense"
      >
        <Box
          display={{ base: 'none', l: 'flex' }}
          justifyContent="space-between"
          gap="spacing.4"
          height="50px"
        >
          <Box display="flex" flexDirection="column" gap="spacing.3" width="50%">
            <Skeleton
              borderRadius="medium"
              height="100%"
              width="100%"
              testID="desktop-section-title-1"
            />
            <Skeleton borderRadius="medium" height="100%" width="100%" />
          </Box>
          <Skeleton borderRadius="medium" height="100%" width="50%" />
        </Box>
        <Skeleton borderRadius="medium" height="50px" width="100%" />
        <Skeleton borderRadius="medium" height="50px" width="100%" />
        <Skeleton borderRadius="medium" height="50px" width="100%" />
      </Box>
      <Divider />
      <Skeleton borderRadius="medium" height="50px" width="100%" />
      <BannerLoader />
      <Box
        display="flex"
        flexDirection={{
          base: 'column',
          l: 'row',
        }}
        gap={{ base: 'spacing.6', l: 'spacing.9' }}
        justifyContent="space-between"
        height={{ base: '1000px', l: '350px' }}
        width="100%"
      >
        <CardLoader />
        <CardLoader />
        <CardLoader />
      </Box>
    </Box>
  );
};
