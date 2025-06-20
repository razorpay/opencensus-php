import React from 'react';
import { Text, Badge } from '@razorpay/blade/components';
import { getFormattedDate } from 'merchant/views/MagicCheckout/SSODashboard/utils';

export const DateCell = ({ value }) => <Text>{value ? getFormattedDate(value) : '-'}</Text>;

export const BaseCell = ({ value }) => <Text color="surface.text.gray.normal">{value ?? '-'}</Text>;

export const UTMSourceCell = ({ value }) => {
  if (!value) return <Text color="surface.text.gray.normal">-</Text>;
  switch (value?.toLowerCase()) {
    case 'google':
      return <Badge color="notice">{value}</Badge>;
    case 'instagram':
      return <Badge color="negative">{value}</Badge>;
    case 'facebook':
      return <Badge color="information">{value}</Badge>;
    case 'whatsapp':
      return <Badge color="positive">{value}</Badge>;
    default:
      return <Badge color="positive">{value}</Badge>;
  }
};
