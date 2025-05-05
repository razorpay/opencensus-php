import React, { ReactNode } from 'react';
import { Box, Text, Card } from '@razorpay/blade/components';
import { isMobileDevice } from '@libs/shared-utils';

const SelectableOptionCard = ({
  title,
  subTitle,
  cardImageUrl,
  handleClick = () => {},
  isDisabled = false,
}: {
  title: ReactNode;
  subTitle: string;
  cardImageUrl: string;
  handleClick?: () => void;
  isDisabled?: boolean;
}) => {
  const isMobile = isMobileDevice();
  return (
    <Card
      testID="selectable-option-card"
      onClick={isDisabled ? undefined : handleClick}
      display="flex"
      padding={isMobile ? 'spacing.5' : 'spacing.7'}
      width="100%"
      elevation="none"
      borderRadius="medium"
      backgroundColor={
        isDisabled ? 'surface.background.gray.subtle' : 'surface.background.gray.intense'
      }
    >
      <Box display="flex" alignItems="center" gap="spacing.4" alignSelf="stretch">
        <img
          data-testid="card-image"
          src={cardImageUrl}
          alt="selectable option"
          style={{ width: '50px', height: '50px', objectFit: 'contain' }}
        />
        <Box display="flex" flexDirection="column" alignItems="flex-start" gap="6px" flex="1 0 0">
          {title}
          <Text testID="card-subtitle" size="small" color="surface.text.gray.subtle">
            {subTitle}
          </Text>
        </Box>
      </Box>
    </Card>
  );
};

export default SelectableOptionCard;
