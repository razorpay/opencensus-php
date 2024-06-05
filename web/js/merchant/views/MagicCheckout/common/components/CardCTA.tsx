import React from 'react';
import { useNavigate } from 'react-router-dom';
import { Card, Box, CardBody, Text, ExternalLinkIcon } from '@razorpay/blade/components';

const CardCTA = ({ redirectUrl, heading }): React.ReactElement => {
  const navigate = useNavigate();

  const handleClick = () => {
    navigate(redirectUrl);
  };

  return (
    <Box marginTop="spacing.4">
      <Card
        shouldScaleOnHover
        width={{
          s: '100%',
          m: '100%',
        }}
        onClick={handleClick}
      >
        <CardBody>
          <Text>{heading}</Text>
          <ExternalLinkIcon size="medium" color="interactive.icon.primary.normal" />
        </CardBody>
      </Card>
    </Box>
  );
};

export default CardCTA;
