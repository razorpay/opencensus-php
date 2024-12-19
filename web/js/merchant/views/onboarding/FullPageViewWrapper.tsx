import React, { ReactNode } from 'react';
import { Box, Link, ArrowLeftIcon } from '@razorpay/blade/components';
import { useNavigate } from 'react-router-dom';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';

const FullPageViewWrapper = ({ children }: { children: ReactNode }): JSX.Element => {
  const navigate = useNavigate();

  return (
    <Box display="flex" justifyContent="center" padding="spacing.6">
      <Box width="100%">
        <Box
          as="header"
          backgroundColor="surface.background.gray.intense"
          borderWidth="thin"
          borderBottomWidth={{ base: 'none', m: 'thin' }}
          borderColor="surface.border.gray.muted"
          paddingY="spacing.5"
          paddingLeft="spacing.6"
        >
          <Link onClick={() => navigate(-1)} icon={ArrowLeftIcon}>
            Back
          </Link>
        </Box>
        <Box
          paddingY={{ m: 'spacing.6' }}
          paddingX={{ m: 'spacing.10' }}
          marginTop={{ base: 'spacing.2', m: 'spacing.0' }}
          borderWidth="thin"
          borderColor="surface.border.gray.muted"
          backgroundColor="surface.background.gray.subtle"
        >
          <Box backgroundColor="surface.background.gray.intense">
            <ErrorBoundary resetOnProps>{children}</ErrorBoundary>
          </Box>
        </Box>
      </Box>
    </Box>
  );
};

export default FullPageViewWrapper;
