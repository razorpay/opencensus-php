import React from 'react';
import {
  Card,
  Box,
  Heading,
  Text,
  Button,
  ArrowRightIcon,
  IconComponent,
  CardHeader,
} from '@razorpay/blade/components';

interface Action {
  label: string;
  onClick: () => void;
  variant?: 'primary' | 'secondary' | 'tertiary';
}

interface GrowthCardProps {
  title: string;
  description: string;
  icon: IconComponent;
  imageSrc: string;
  action: Action;
}

const GrowthCard: React.FC<GrowthCardProps> = ({
  title,
  description,
  icon: Icon,
  imageSrc,
  action,
}) => {
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
            padding={['spacing.0', 'spacing.5']}
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
            <Box display={'flex'} flexDirection={'column'} gap="spacing.3">
              <Heading>{title}</Heading>
              <Text variant="body" size="small" color="surface.text.gray.subtle">
                {description}
              </Text>
            </Box>
            <Button
              marginTop="spacing.5"
              variant={action?.variant || 'secondary'}
              alignSelf={'flex-start'}
              icon={ArrowRightIcon}
              iconPosition="right"
              onClick={action.onClick}
            >
              {action.label}
            </Button>
          </Box>

          <Box
            minWidth={{ base: '100px', l: '120px', xl: '120px' }}
            backgroundImage={`url(${imageSrc})`}
            backgroundSize="contain"
            backgroundRepeat="no-repeat"
            backgroundPosition="bottom right"
          />
        </Box>
      </CardHeader>
    </Card>
  );
};

export default GrowthCard;
