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
  UserIcon,
  PhoneIcon,
  MailIcon,
  GlobeIcon,
} from '@razorpay/blade/components';
import type { SSOCustomer } from 'merchant/views/MagicCheckout/SSODashboard/types';

interface InfoCardsProps {
  customer?: SSOCustomer;
}

const CustomerMarketInfoCard = ({ customer }: InfoCardsProps) => {
  return (
    <Card
      backgroundColor="surface.background.gray.intense"
      borderRadius="medium"
      elevation="none"
      width="100%"
    >
      <CardHeader paddingBottom="spacing.4">
        <Text weight="semibold">Marketing Info</Text>
        <CardHeaderTrailing
          visual={
            <Button
              color="primary"
              variant="tertiary"
              size="small"
              iconPosition="left"
              icon={GlobeIcon}
            ></Button>
          }
        />
      </CardHeader>
      <CardBody>
        <List>
          <ListItem icon={UserIcon}>UTM Source: {customer?.utm_source ?? '-'}</ListItem>

          <ListItem icon={PhoneIcon}>UTM Medium: {customer?.utm_medium ?? '-'}</ListItem>
          <ListItem icon={MailIcon}>UTM Campaign: {customer?.utm_campaign ?? '-'}</ListItem>
        </List>
      </CardBody>
    </Card>
  );
};

export default CustomerMarketInfoCard;
