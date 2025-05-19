import React from 'react';
import type { SSOCustomer } from 'merchant/views/MagicCheckout/SSODashboard/types';
import {
  Card,
  CardHeader,
  Text,
  CardHeaderTrailing,
  CopyIcon,
  CardBody,
  Button,
  List,
  ListItem,
  UserIcon,
  PhoneIcon,
  MailIcon,
} from '@razorpay/blade/components';
import copyToClipboard from 'common/utils/copyToClipboard';
import { showNotification } from 'merchant_common/reducers/notifications';

interface InfoCardsProps {
  customer?: SSOCustomer;
}

const CustomerInfoCard = ({ customer }: InfoCardsProps) => {
  const handleCopy = () => {
    copyToClipboard(customer?.email || '');
    showNotification({
      type: 'success',
      message: 'Customer Email copied successfully',
    });
  };
  return (
    <Card
      backgroundColor="surface.background.gray.intense"
      borderRadius="medium"
      elevation="none"
      width="100%"
    >
      <CardHeader paddingBottom="spacing.4">
        <Text weight="semibold">Customer Info</Text>
        <CardHeaderTrailing
          visual={
            <Button
              color="primary"
              variant="tertiary"
              size="small"
              onClick={handleCopy}
              iconPosition="left"
              icon={CopyIcon}
            ></Button>
          }
        />
      </CardHeader>
      <CardBody>
        <List>
          <ListItem icon={UserIcon}>{customer?.name || '-'}</ListItem>
          <ListItem icon={PhoneIcon}>{customer?.phone || '-'}</ListItem>
          <ListItem icon={MailIcon}>{customer?.email || '-'}</ListItem>
        </List>
      </CardBody>
    </Card>
  );
};

export default CustomerInfoCard;
