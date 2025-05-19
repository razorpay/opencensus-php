import React from 'react';
import {
  Card,
  CardHeader,
  Text,
  CardHeaderTrailing,
  CardBody,
  Button,
  List,
  ListItem,
  ArrowRightIcon,
  CalendarIcon,
  LogInIcon,
  ClockIcon,
} from '@razorpay/blade/components';
import type { SSOCustomer } from 'merchant/views/MagicCheckout/SSODashboard/types';
import { getFormattedDate } from 'merchant/views/MagicCheckout/SSODashboard/utils';

interface InfoCardsProps {
  customer?: SSOCustomer;
}
const CustomerLoginActivityCard = ({ customer }: InfoCardsProps) => {
  return (
    <Card
      backgroundColor="surface.background.gray.intense"
      borderRadius="medium"
      elevation="none"
      width="100%"
    >
      <CardHeader paddingBottom="spacing.4">
        <Text weight="semibold">Login Activity</Text>
        <CardHeaderTrailing
          visual={
            <Button
              color="primary"
              variant="tertiary"
              size="small"
              iconPosition="left"
              icon={ArrowRightIcon}
            ></Button>
          }
        />
      </CardHeader>
      <CardBody>
        <List>
          <ListItem icon={CalendarIcon}>
            First Login: {customer?.first_login ? getFormattedDate(customer?.first_login) : 'N/A'}
          </ListItem>
          <ListItem icon={ClockIcon}>
            Last Login: {customer?.last_login ? getFormattedDate(customer?.last_login) : 'N/A'}
          </ListItem>
          <ListItem icon={LogInIcon}>
            Login Frequency: {customer?.login_frequency ? `${customer?.login_frequency} times` : ''}
          </ListItem>
        </List>
      </CardBody>
    </Card>
  );
};

export default CustomerLoginActivityCard;
