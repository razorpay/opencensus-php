import React from 'react';
import { Box, Button, Text } from '@razorpay/blade/components';
import styled from 'styled-components';
import coinImage from 'merchant/components/HeaderNav/assets/partner-program-coins.svg';

const PartnerProgramImage = styled.img`
  border-bottom-right-radius: 8px;
`;

interface ExplorePartnerProgramCardProps {
  onClick: () => void;
}

const ExplorePartnerProgramCard = ({ onClick }: ExplorePartnerProgramCardProps) => {
  return (
    <Box display="flex" backgroundColor="surface.background.primary.subtle" borderRadius="large">
      <Box display="flex" flexDirection="column" margin="spacing.5" marginRight="spacing.4">
        <Text marginBottom="spacing.4">Partner with us and start earning on every referral</Text>
        <Button variant="primary" onClick={onClick}>
          Explore Partner Program
        </Button>
      </Box>
      <Box display="flex" alignItems="flex-end">
        <PartnerProgramImage src={coinImage} />
      </Box>
    </Box>
  );
};

export default ExplorePartnerProgramCard;
