import React from 'react';
import {
  Box,
  Card,
  CardBody,
  ArrowUpRightIcon,
  Heading,
  Link,
  Badge,
  Text,
  SparklesIcon,
} from '@razorpay/blade/components';

import { OFFERS_DATA, EXCLUSIVE_OFFER_HEADER } from '../constant';
import { trackEventOnPublicUrl } from '../analytics';

const OffersCard = ({ isSmallDevice }: { isSmallDevice: boolean }) => {
  const handleClickAction = (url, title) => {
    trackEventOnPublicUrl({
      label: title,
      link_url: url,
    });
    window.open(url, '_blank', 'noopener');
  };
  return (
    <Box
      testID="offers-box"
      height={isSmallDevice ? '100%' : '186px'}
      marginX={isSmallDevice ? 'spacing.0' : 'spacing.7'}
      display={'flex'}
      flexDirection={isSmallDevice ? 'column' : 'row'}
      justifyContent={'space-between'}
    >
      {OFFERS_DATA.map(({ badgeLabel, title, description, linkLabel, url }, index) => (
        <Card
          key={title}
          minWidth={'370px'}
          height={isSmallDevice ? '200px' : '100%'}
          elevation="lowRaised"
          marginRight={!isSmallDevice && index < OFFERS_DATA.length - 1 ? 'spacing.5' : 'spacing.0'}
          marginBottom={isSmallDevice && index < OFFERS_DATA.length - 1 ? 'spacing.7' : 'spacing.0'}
          padding="spacing.7"
        >
          <CardBody height={'100%'}>
            <Box
              height={'100%'}
              display={'flex'}
              flexDirection={'column'}
              justifyContent={'space-between'}
            >
              <Badge icon={SparklesIcon} color="information">
                {badgeLabel}
              </Badge>
              <Heading>{title}</Heading>
              <Text>{description}</Text>
              <Link
                icon={ArrowUpRightIcon}
                variant="anchor"
                color="primary"
                size="medium"
                iconPosition="right"
                onClick={() => handleClickAction(url, title)}
              >
                {linkLabel}
              </Link>
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
    <Box display="flex" flexDirection="column" justifyContent={'flex-start'}>
      <Heading size={isSmallDevice ? 'large' : 'medium'} marginX="spacing.7" marginY="spacing.7">
        {EXCLUSIVE_OFFER_HEADER}
      </Heading>
      <OffersCard isSmallDevice={isSmallDevice} />
    </Box>
  );
};

export default RazorpayExclusiveOffer;
