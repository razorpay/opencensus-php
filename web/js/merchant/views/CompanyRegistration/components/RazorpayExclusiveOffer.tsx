import React from 'react';
import { Box, Card, CardBody, Heading, Text } from '@razorpay/blade/components';

import { OFFERS_DATA, EXCLUSIVE_OFFER_HEADER } from '../constant';

const OffersCard = ({ isSmallDevice }: { isSmallDevice: boolean }) => {
  return (
    <Box
      testID="offers-box"
      height={isSmallDevice ? '100%' : '186px'}
      marginX={{ base: 'spacing.0', m: 'spacing.7' }}
      display={'flex'}
      flexDirection={isSmallDevice ? 'column' : 'row'}
      justifyContent={'space-between'}
    >
      {OFFERS_DATA.map(({ title, description }, index) => (
        <Card
          key={title}
          elevation="midRaised"
          borderRadius="medium"
          marginRight={!isSmallDevice && index < OFFERS_DATA.length - 1 ? 'spacing.5' : 'spacing.0'}
          marginBottom={isSmallDevice && index < OFFERS_DATA.length - 1 ? 'spacing.7' : 'spacing.0'}
          padding="spacing.7"
        >
          <CardBody height={'100%'}>
            <Box height={'100%'} display={'flex'} flexDirection={'column'}>
              <Heading marginBottom="spacing.4">{title}</Heading>
              <Text>{description}</Text>
            </Box>
          </CardBody>
        </Card>
      ))}
    </Box>
  );
};
interface RazorpayExclusiveOfferProps {
  isSmallDevice: boolean;
}
const RazorpayExclusiveOffer: React.FC<RazorpayExclusiveOfferProps> = ({ isSmallDevice }) => {
  return (
    <Box
      display="flex"
      flexDirection="column"
      justifyContent={'flex-start'}
      marginBottom={{ base: 'spacing.5', m: 'spacing.0' }}
    >
      <Heading
        size={isSmallDevice ? 'large' : 'medium'}
        marginLeft={{ base: 'spacing.0', m: 'spacing.7' }}
        marginY="spacing.7"
      >
        {EXCLUSIVE_OFFER_HEADER}
      </Heading>
      <OffersCard isSmallDevice={isSmallDevice} />
    </Box>
  );
};

export default RazorpayExclusiveOffer;
