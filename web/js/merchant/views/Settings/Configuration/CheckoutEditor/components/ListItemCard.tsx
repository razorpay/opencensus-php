import React, { ReactElement, useState } from 'react';
import { useSortable } from '@dnd-kit/sortable';
import { Badge, Box, Card, CardBody, MenuIcon, Radio, Text } from '@razorpay/blade/components';

import { noop } from 'common/utils/rzp-utils';
import { StyledButton } from './styles';

export type ListItemCardProps = {
  Title: ReactElement<typeof Text>;
  Description?: ReactElement;
  LeftIcon?: ReactElement;
  Badge?: ReactElement<typeof Badge>;
  RightComponent?: React.ReactNode;
  backgroundColor?: React.ComponentProps<typeof Card>['backgroundColor'];
  isSelected?: boolean;
  isDraggable?: boolean;
  elevation?: React.ComponentProps<typeof Card>['elevation'];
  accessibilityLabel: string;
  onItemClick?: () => void;
  id?: string;
  Radio?: ReactElement<typeof Radio>;
  isHoverable?: boolean;
  showDragIcon?: boolean;
};

export function ListItemCard({
  Title,
  LeftIcon,
  Description,
  Badge,
  RightComponent,
  backgroundColor,
  isSelected,
  elevation = 'none',
  accessibilityLabel,
  onItemClick,
  isDraggable = false,
  id = '',
  Radio,
  isHoverable = false,
  showDragIcon = false,
}: ListItemCardProps) {
  const { attributes, listeners } = useSortable({ id });

  const [bgColor, setBgColor] = useState<React.ComponentProps<typeof Card>['backgroundColor']>(
    backgroundColor ?? 'surface.background.gray.intense',
  );

  function applyHoverEffect() {
    setBgColor('surface.background.gray.subtle');
  }

  function removeHoverEffect() {
    setBgColor(backgroundColor ?? 'surface.background.gray.intense');
  }

  return (
    <Box
      onMouseEnter={isHoverable ? applyHoverEffect : noop}
      onMouseLeave={isHoverable ? removeHoverEffect : noop}
    >
      <Card
        accessibilityLabel={accessibilityLabel}
        elevation={elevation}
        padding="spacing.0"
        isSelected={isSelected}
        backgroundColor={bgColor}
        borderRadius="large"
        onClick={onItemClick}
        as={Radio && 'label'}
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
            {showDragIcon ? (
              <StyledButton
                cursor={isDraggable ? 'grab' : 'pointer'}
                {...listeners}
                {...attributes}
              >
                <MenuIcon
                  color={isDraggable ? 'surface.icon.gray.normal' : 'surface.icon.gray.disabled'}
                />
              </StyledButton>
            ) : null}
            {Radio ? Radio : null}
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
    </Box>
  );
}
