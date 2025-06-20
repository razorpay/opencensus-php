import React from 'react';
import { Box, StepGroup, StepItem, Skeleton } from '@razorpay/blade/components';
import { isMobileDevice } from '@libs/shared-utils';

const TrackTransactionsSkeleton = () => {
  const isMobile = isMobileDevice();

  return (
    <Box
      borderStyle="solid"
      borderWidth="thick"
      borderColor="surface.border.gray.subtle"
      borderRadius="medium"
      display="flex"
      flexDirection="column"
      gap="spacing.5"
      paddingX={{
        base: 'spacing.5',
        l: '72px',
      }}
      paddingY="spacing.5"
    >
      {isMobile ? (
        <Skeleton width="200px" height="24px" borderRadius="medium" />
      ) : (
        <Skeleton width="300px" height="36px" borderRadius="medium" />
      )}
      {/* Timeline */}
      <Box
        display="flex"
        flexDirection={{
          base: 'column',
          m: 'row',
        }}
        alignItems="center"
        justifyContent="center"
        paddingRight={{
          base: 'spacing.8',
          m: 'auto',
        }}
        paddingX={{
          base: 'spacing.5',
          m: '32px',
        }}
        paddingY={{
          base: 'spacing.8',
          m: '32px',
        }}
        marginRight="-12px"
        width="100%"
        borderStyle="solid"
        borderWidth="thick"
        borderColor="surface.border.gray.subtle"
        borderRadius="large"
        marginBottom="spacing.4"
      >
        <StepGroup orientation="horizontal" marginTop={isMobile ? '-32px' : '0px'}>
          {Array.from({ length: 5 }).map((_, index) => {
            return (
              <StepItem
                key={index}
                minWidth="spacing.2"
                marker={
                  <Box
                    width={isMobile ? '100%' : '70px'}
                    height="spacing.8"
                    display="flex"
                    alignItems="center"
                    justifyContent="center"
                  >
                    <Skeleton
                      borderRadius="round"
                      width={isMobile ? '32px' : '48px'}
                      height={isMobile ? '32px' : '48px'}
                    />
                  </Box>
                }
                stepProgress={'none'}
                title={''}
              >
                <Box
                  marginTop={{ base: '-16px', m: '-8px' }}
                  display="flex"
                  flexDirection="column"
                  gap="spacing.2"
                  alignItems="center"
                >
                  {isMobile ? (
                    <Skeleton width="22px" height="16px" />
                  ) : (
                    <Box display="flex" flexDirection="column" alignItems="center" gap="spacing.2">
                      <Skeleton width="40px" height="16px" />
                      <Skeleton width="80px" height="14px" />
                      <Skeleton width="60px" height="14px" />
                    </Box>
                  )}
                </Box>
              </StepItem>
            );
          })}
        </StepGroup>
        {isMobile && (
          <Box
            display="flex"
            flexDirection="column"
            gap="spacing.3"
            alignItems="center"
            marginTop="spacing.5"
          >
            <Skeleton width="250px" height="16px" />
          </Box>
        )}
      </Box>
      {/* Settlement Details Skeleton */}
      <Box
        display="flex"
        flexDirection={{ base: 'column', m: 'row' }}
        gap="spacing.2"
        marginTop={{ base: '-16px', m: 'spacing.0' }}
      >
        <Skeleton width={isMobile ? '100%' : '500px'} height={isMobile ? '12px' : '16px'} />
        <Skeleton width={isMobile ? '200px' : '300px'} height={isMobile ? '12px' : '16px'} />
      </Box>
    </Box>
  );
};

export default TrackTransactionsSkeleton;
