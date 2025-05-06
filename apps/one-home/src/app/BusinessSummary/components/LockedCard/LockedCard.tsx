import React from 'react';
import {
  Card,
  CardBody,
  Box,
  AcceptPaymentsIcon,
  Heading,
  Text,
  IconComponent,
  CardHeader,
} from '@razorpay/blade/components';

interface LockedCardProps {
  title: string;
  description: string;
  imgSrc: string;
  icon: IconComponent;
}

const LockedCard: React.FC<LockedCardProps> = ({ title, description, imgSrc, icon: Icon }) => {
  return (
    <Card padding="spacing.0" elevation="none" borderRadius="large">
      <CardHeader showDivider={false} marginBottom="spacing.0" paddingBottom="spacing.0">
        <Box
          display={'flex'}
          flex={1}
          padding={['spacing.5', 'spacing.0', 'spacing.0', 'spacing.5']}
          justifyContent={'space-between'}
          width={{ base: '100%', m: 'auto', l: '360px' }}
        >
          <Box
            display={'flex'}
            flexDirection={'column'}
            flexWrap={'wrap'}
            padding={'spacing.5'}
            marginBottom={'spacing.5'}
          >
            <Box
              padding="spacing.3"
              backgroundColor={'surface.background.gray.subtle'}
              display={'flex'}
              justifyContent={'center'}
              alignItems={'center'}
              alignSelf={'flex-start'}
            >
              <Icon size="medium" color="surface.icon.gray.subtle" />
            </Box>
            <Box display={'flex'} flexDirection={'column'} gap="8px">
              <Heading>{title}</Heading>
              <Text variant="body" size="small" color="surface.text.gray.subtle">
                {description}
              </Text>
            </Box>
          </Box>

          <Box
            minWidth={{ base: '100px', l: '120px', xl: '120px' }}
            backgroundImage={`url(${imgSrc})`}
            backgroundSize="contain"
            backgroundRepeat="no-repeat"
            backgroundPosition="bottom right"
          />
        </Box>
      </CardHeader>
    </Card>
  );
};

export default LockedCard;
