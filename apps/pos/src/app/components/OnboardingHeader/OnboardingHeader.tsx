import { Box, ChevronLeftIcon, Heading, IconButton, Text } from '@razorpay/blade/components';
import React from 'react';
import { useNavigate } from 'react-router-dom';

interface OnboardingHeaderProps {
  pageLabel?: string;
  title?: string;
  description?: string;
  isBackButtonVisible?: boolean;
  footer?: JSX.Element;
}

const OnboardingHeader = ({
  title,
  description,
  pageLabel,
  footer,
  isBackButtonVisible,
}: OnboardingHeaderProps): JSX.Element => {
  const navigate = useNavigate();
  return (
    <Box>
      <Box display="flex" justifyContent="space-between" marginBottom="spacing.0">
        {isBackButtonVisible ? (
          <IconButton
            icon={ChevronLeftIcon}
            size="large"
            onClick={() => navigate(-1)}
            accessibilityLabel="header-back-btn"
          />
        ) : null}
        {pageLabel ? (
          <Text color="surface.text.gray.muted" weight="semibold" size="small">
            {pageLabel}
          </Text>
        ) : null}
      </Box>

      <Box>
        {title ? (
          <Heading size="large" color="surface.text.gray.normal" marginTop="spacing.5">
            {title}
          </Heading>
        ) : null}
        {description ? (
          <Text color="surface.text.gray.subtle" marginBottom="spacing.5">
            {description}
          </Text>
        ) : null}
      </Box>
      {footer}
    </Box>
  );
};

export default OnboardingHeader;
