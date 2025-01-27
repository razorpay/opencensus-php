import React, { ReactElement } from 'react';
import { Badge, Box, Card, CardBody, Text } from '@razorpay/blade/components';

export type ListItemCardProps = {
  Title: ReactElement<Text>;
  Description?: ReactElement;
  LeftIcon?: ReactElement;
  Badge?: ReactElement<typeof Badge>;
  RightComponent?: React.ReactNode;
  backgroundColor?: React.ComponentProps<typeof Card>['backgroundColor'];
  isSelected?: boolean;
  elevation?: React.ComponentProps<typeof Card>['elevation'];
  accessibilityLabel: string;
  onItemClick?: () => void;
};

export function ListItemCard({
  Title,
  LeftIcon,
  Description,
  Badge,
  RightComponent,
  backgroundColor,
  isSelected,
  elevation,
  accessibilityLabel,
  onItemClick,
}: ListItemCardProps) {
  return (
    <Card
      accessibilityLabel={accessibilityLabel}
      elevation={elevation ?? 'none'}
      onHover={() => {}}
      padding="spacing.0"
      isSelected={isSelected}
      backgroundColor={backgroundColor ?? 'surface.background.gray.intense'}
      borderRadius="large"
      onClick={onItemClick}
    >
      <CardBody>
        <Box
          display="flex"
          padding={['spacing.4', 'spacing.5', 'spacing.4', 'spacing.5']}
          justifyContent="center"
          alignItems="center"
          gap="spacing.4"
          alignSelf="stretch"
        >
          {LeftIcon ? LeftIcon : null}
          <Box flexGrow="1">
            <Box flexDirection="column">
              <Box display="flex" gap="spacing.3" alignItems="center">
                {Title}
                {Badge ? Badge : null}
              </Box>
              {Description}
            </Box>
          </Box>
          <Box>{RightComponent}</Box>
        </Box>
      </CardBody>
    </Card>
  );
}
