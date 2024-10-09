import {
  Badge,
  Box,
  CheckCircleIcon,
  ChevronRightIcon,
  IconComponent,
  Text,
} from '@razorpay/blade/components';
import React from 'react';
import styled from 'styled-components';
import IconWrapper from './IconWrapper';

interface ConfigCardType {
  title: string;
  isDone: boolean;
  Icon: IconComponent;
  showOnlyText?: boolean;
  language?: string;
  onClick: () => void;
}

const ClickableContainer = styled.div``;

const DeviceConfigCard = ({
  title,
  isDone,
  Icon,
  showOnlyText,
  language,
  onClick,
}: ConfigCardType) => {
  return (
    <ClickableContainer onClick={onClick}>
      <Box
        display="flex"
        alignItems="center"
        justifyContent="space-between"
        padding={['spacing.5', 'spacing.6']}
        borderRadius="medium"
        backgroundColor="surface.background.gray.intense"
        marginBottom="spacing.5"
      >
        <Box display="flex" alignItems="center">
          <IconWrapper Icon={Icon} />
          <Text>{title} </Text>
          <Text
            size="small"
            variant="caption"
            weight="regular"
            color="surface.text.gray.muted"
            marginLeft="spacing.1"
          >
            (optional)
          </Text>
        </Box>
        {showOnlyText ? (
          <Text size="small" weight="regular" color="surface.text.gray.muted">
            {language}
          </Text>
        ) : (
          <Box>
            {isDone ? (
              <Badge icon={CheckCircleIcon} color="positive">
                Done
              </Badge>
            ) : (
              <ChevronRightIcon color="surface.icon.primary.normal" size="large" />
            )}
          </Box>
        )}
      </Box>
    </ClickableContainer>
  );
};

export default DeviceConfigCard;
