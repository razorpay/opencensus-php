import React from 'react';
import { Box, Text, IconComponent, Badge } from '@razorpay/blade/components';
import { SfStatusType } from '../types';
interface StepsItemProps {
  icon: IconComponent;
  title: string;
  status: SfStatusType;
}
type ColorMapping = {
  badge: 'positive' | 'primary' | 'neutral';
  box: 'surface.background.gray.subtle' | 'surface.background.primary.intense';
};
const checkStatus = (status) => ['Next', 'Final'].includes(status);
const StepsItem = ({ icon: Icon, title, status }: StepsItemProps) => {
  const getColor = (status: StepsItemProps['status']): ColorMapping => {
    switch (status) {
      case 'Completed':
        return {
          badge: 'positive',
          box: 'surface.background.gray.subtle',
        } as const;
      case 'Ongoing':
        return {
          badge: 'primary',
          box: 'surface.background.primary.intense',
        } as const;
      default:
        return {
          badge: 'neutral',
          box: 'surface.background.gray.subtle',
        } as const;
    }
  };
  const isNextOrFinal = checkStatus(status);
  return (
    <Box
      width={{ l: '440px' }}
      display="flex"
      alignItems="center"
      justifyContent="start"
      gap={{ base: 'spacing.5' }}
      marginBottom={'spacing.7'}
    >
      <Box
        borderRadius="large"
        width="spacing.9"
        height="spacing.9"
        display="grid"
        placeItems="center"
        backgroundColor={getColor(status).box}
      >
        <Icon
          size="large"
          color={
            status === 'Ongoing' ? 'surface.icon.staticWhite.normal' : 'surface.icon.gray.muted'
          }
        />
      </Box>
      <Box>
        <Text
          size="medium"
          color={isNextOrFinal ? 'surface.text.gray.muted' : 'surface.text.gray.normal'}
        >
          {title}
        </Text>
        {isNextOrFinal ? (
          <Text size="small" color="surface.text.gray.muted">
            {status}
          </Text>
        ) : (
          <Badge size="small" color={getColor(status).badge}>
            {status}
          </Badge>
        )}
      </Box>
    </Box>
  );
};

export default StepsItem;
