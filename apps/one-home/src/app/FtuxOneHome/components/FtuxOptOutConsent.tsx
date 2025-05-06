import React from 'react';
import { useStore } from '@apps/shell/src/client/store/commonStore';
import { Alert, Box, Button, Heading, InfoIcon, Text } from '@razorpay/blade/components';
import { useNavigate } from 'react-router-dom';
import { FtuxOptOutFooterProps } from '../types';

export const FtuxConsentOptOutBody = (): React.ReactElement => {
  return (
    <Box display="flex" flexDirection="column" gap="spacing.5">
      <Heading size="large" weight="semibold">
        Are you sure that you do not want to access the Razorpay Home?
      </Heading>

      <Text size="medium" weight="medium" color="surface.text.gray.normal">
        You will be missing out on a combined view of your business and a one-stop view of all your
        money-movement details.
      </Text>

      <Alert
        icon={InfoIcon}
        description="99.6% of Razorpay businesses have opted in and chosen a better view of their finances."
        color="positive"
        emphasis="subtle"
      />
    </Box>
  );
};

export const FtuxConsentOptOutFooter = ({
  onGoBack,
}: FtuxOptOutFooterProps): React.ReactElement => {
  const { user } = useStore((state) => state['session']);
  const navigate = useNavigate();

  const handleGoBackClick = () => {
    onGoBack();
  };
  const handleDeclineClick = () => {
    const product = user.product;
    if (product === 'primary') navigate('/dashboard');
    else if (product === 'banking') navigate('/banking');
  };

  return (
    <Box display="flex" justifyContent="flex-end" gap="spacing.5">
      <Button variant="tertiary" color="primary" size="medium" onClick={handleGoBackClick}>
        Go back
      </Button>
      <Button variant="primary" color="primary" size="medium" onClick={handleDeclineClick}>
        Decline anyway
      </Button>
    </Box>
  );
};
