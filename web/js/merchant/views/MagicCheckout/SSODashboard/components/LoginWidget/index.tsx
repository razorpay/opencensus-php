import React from 'react';
import {
  Box,
  Button,
  Card,
  CardBody,
  CardHeader,
  CardHeaderTrailing,
  Heading,
  Text,
  UserIcon,
  UserPlusIcon,
  Spinner,
} from '@razorpay/blade/components';

interface LoginWidgetProps {
  isLoading: boolean;
  totalLogin: number;
  newLogin: number;
}

const LoginWidget = ({ isLoading, totalLogin, newLogin }: LoginWidgetProps) => {
  const LoadingSpinner = () => (
    <Box display="flex" alignItems="center" height="38px">
      <Spinner size="xlarge" accessibilityLabel="loading data" />
    </Box>
  );

  return (
    <Box display="flex" gap="spacing.7">
      <Card
        backgroundColor="surface.background.gray.intense"
        borderRadius="medium"
        elevation="none"
        padding="spacing.5"
        width="100%"
      >
        <CardHeader showDivider={false} paddingBottom="spacing.0">
          <Text weight="semibold">Total Logged-in Users</Text>
          <CardHeaderTrailing
            visual={
              <Button
                color="primary"
                variant="primary"
                size="small"
                iconPosition="left"
                icon={UserIcon}
              ></Button>
            }
          />
        </CardHeader>
        <CardBody>
          {isLoading ? (
            <LoadingSpinner />
          ) : (
            <Heading size="xlarge" weight="semibold">
              {totalLogin}
            </Heading>
          )}
        </CardBody>
      </Card>

      <Card
        backgroundColor="surface.background.gray.intense"
        borderRadius="medium"
        elevation="none"
        padding="spacing.5"
        width="100%"
      >
        <CardHeader showDivider={false} paddingBottom="spacing.0">
          <Text weight="semibold">New Account Creations</Text>
          <CardHeaderTrailing
            visual={
              <Button
                color="positive"
                variant="secondary"
                size="small"
                iconPosition="left"
                icon={UserPlusIcon}
              ></Button>
            }
          />
        </CardHeader>
        <CardBody>
          {isLoading ? (
            <LoadingSpinner />
          ) : (
            <Heading size="xlarge" weight="semibold">
              {newLogin}
            </Heading>
          )}
        </CardBody>
      </Card>
    </Box>
  );
};

export default LoginWidget;
