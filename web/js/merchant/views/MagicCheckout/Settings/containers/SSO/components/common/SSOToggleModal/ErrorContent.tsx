import React from 'react';
import { Box, Text, Heading, CloseIcon, Avatar } from '@razorpay/blade/components';
import { ErrorContentProps } from './types';

export const ErrorContent: React.FC<ErrorContentProps> = ({ errorMsg = '' }) => (
  <>
    <Avatar size="large" icon={CloseIcon} color="negative" />
    <Box display="flex" flexDirection="column" alignItems="center">
      <Heading>Oops! Something went wrong</Heading>
      {errorMsg && <Text size="small">{errorMsg}</Text>}
    </Box>
  </>
);
