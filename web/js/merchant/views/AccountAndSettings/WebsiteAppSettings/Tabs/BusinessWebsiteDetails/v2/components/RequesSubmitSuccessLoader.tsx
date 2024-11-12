import React from 'react';
import { Box, Text, Heading, Button } from '@razorpay/blade/components';

const RequesSubmitSuccessLoader = ({ onClick, eta }) => (
  <>
    <Heading size="large" textAlign="center">
      Your website is submitted for verification
    </Heading>
    <Box>
      <Text textAlign="center" color="surface.text.gray.subtle">
        We’re verifying your details and will share an update
      </Text>
      <Text weight="medium" textAlign="center" color="surface.text.gray.subtle">
        within {eta}
      </Text>
    </Box>
    <Button onClick={onClick}>Okay, got it</Button>
  </>
);

export default RequesSubmitSuccessLoader;
