import React from 'react';
import { Box, Text, Heading } from '@razorpay/blade/components';

type ErrorPageProps = {
  title: string;
  description: string;
};

const ErrorPage: React.FC<ErrorPageProps> = ({
  title = 'It’s not you it’s us',
  description = 'We are unable to fetch the required information right now. Request you to retry after sometime',
}) => {
  return (
    <Box
      display="flex"
      flexDirection="column"
      justifyContent="center"
      alignItems="center"
      height={'100vh'}
      padding="spacing.4"
    >
      <Box marginTop={{ base: 'spacing.4', m: 'spacing.10' }} textAlign="center">
        <Heading
          as="h1"
          size="2xlarge"
          weight="semibold"
          marginBottom="spacing.2"
          color={'surface.text.gray.normal'}
        >
          {title}
        </Heading>
        <Text
          variant="body"
          weight={'medium'}
          size={'large'}
          marginBottom="spacing.4"
          color={'surface.text.gray.muted'}
        >
          {description}
        </Text>
      </Box>
    </Box>
  );
};

export default ErrorPage;
