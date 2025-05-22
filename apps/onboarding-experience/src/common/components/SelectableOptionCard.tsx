import React, { ReactNode } from 'react';
import { Box, Text, Card, BoxProps } from '@razorpay/blade/components';
import { isMobileDevice } from '@libs/shared-utils';

export type SelectableOptionCardProps = {
  title?: string;
  customTitle?: ReactNode;
  subTitle: string;
  cardImageUrl: string;
  handleClick?: () => void;
  isDisabled?: boolean;
  link?: string;
  imageWidth?: string;
  imageHeight?: string;
  containerProps?: BoxProps;
  isSelected?: boolean;
};

const SelectableOptionCard = ({
  title,
  customTitle,
  subTitle,
  cardImageUrl,
  handleClick = () => {},
  isDisabled = false,
  link,
  imageWidth = '50px',
  imageHeight = '50px',
  containerProps,
  isSelected,
}: SelectableOptionCardProps) => {
  const isMobile = isMobileDevice();
  return (
    <Card
      testID="selectable-option-card"
      onClick={isDisabled ? undefined : handleClick}
      href={link}
      target="_blank"
      display="flex"
      padding={isMobile ? 'spacing.5' : 'spacing.7'}
      width="100%"
      elevation="none"
      borderRadius="medium"
      backgroundColor={
        isDisabled ? 'surface.background.gray.subtle' : 'surface.background.gray.intense'
      }
      isSelected={isSelected}
    >
      <Box display="flex" alignItems="center" gap="spacing.4" {...containerProps}>
        <img
          data-testid="card-image"
          src={cardImageUrl}
          alt="selectable option"
          style={{ width: imageWidth, height: imageHeight, objectFit: 'contain' }}
        />
        <Box display="flex" flexDirection="column" alignItems="flex-start" gap="6px" flex="1 0 0">
          {title ? (
            <Text color="surface.text.gray.subtle" weight="semibold">
              {title}
            </Text>
          ) : (
            customTitle
          )}
          <Text testID="card-subtitle" size="small" color="surface.text.gray.subtle">
            {subTitle}
          </Text>
        </Box>
      </Box>
    </Card>
  );
};

export default SelectableOptionCard;
