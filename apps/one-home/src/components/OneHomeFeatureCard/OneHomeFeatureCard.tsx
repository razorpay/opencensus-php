import React, { useState } from 'react';
import { Box, Button, Card, ChevronRightIcon, Heading, Text } from '@razorpay/blade/components';
import { useNavigate } from 'react-router-dom';

const OneHomeFeatureCard = ({ isMobile }: { isMobile: boolean }) => {
  const navigate = useNavigate();
  const [isExpanded, setIsExpanded] = useState(false);

  return isMobile ? (
    <Card padding="spacing.0">
      <Box
        backgroundColor="surface.background.cloud.intense"
        padding="spacing.5"
        display="flex"
        flexDirection="column"
        gap="spacing.3"
        borderRadius="large"
      >
        <Box display="flex" gap="spacing.5" justifyContent="space-between">
          <Box>
            {isExpanded ? (
              <>
                <Heading color="surface.text.staticWhite.normal" size="medium" weight="semibold">
                  Introducing the new, all-in-one
                  <Heading color="surface.text.staticWhite.normal" size="medium" weight="semibold">
                    Razorpay Home
                  </Heading>
                </Heading>
              </>
            ) : (
              <>
                <Text size="small" weight="semibold" color="surface.text.staticWhite.normal">
                  Introducing the new, all-in-one
                  <Text size="small" weight="semibold" color="surface.text.staticWhite.normal">
                    Razorpay Home
                  </Text>
                </Text>
              </>
            )}
          </Box>

          {!isExpanded && (
            <Button
              icon={ChevronRightIcon}
              variant="tertiary"
              color="white"
              size="medium"
              onClick={() => setIsExpanded(true)}
            />
          )}
        </Box>

        {isExpanded && (
          <>
            <Text
              marginBottom="spacing.5"
              weight="medium"
              size="medium"
              color="surface.text.staticWhite.normal"
            >
              The one place for you to keep track of everything on Razorpay, across the Payments,
              Banking+ and Payroll platforms
            </Text>

            <Button
              variant="primary"
              color="white"
              size="medium"
              isFullWidth
              onClick={() => navigate('/home')}
            >
              Go to the new Home
            </Button>
          </>
        )}
      </Box>
    </Card>
  ) : null;
};

export default OneHomeFeatureCard;
