import React from 'react';
import { Box, Text } from '@razorpay/blade/components';
import { Link as RouterLink } from 'react-router-dom';

import { DataTableColumn } from 'common/typings';
import { getTime } from 'common/ui/item';
import { email as maskedEmail } from 'common/ui/item/pair';
import { SubmerchantInviteItem } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/PaymentsClients/AllInvites/api';
import {
  PGAcceptedInviteItem,
  POSAcceptedInviteItem,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/api';

export const addedOnColumn = {
  title: 'Added On',
  value: getTime('created_at', 'll'),
};

export const appIdColumn: DataTableColumn = {
  title: 'App Id',
  value: (item) => (
    <RouterLink to={`/partners/applications/${item.application.id}`}>
      {item.application.id}
    </RouterLink>
  ),
};

export const mobileAndEmailColumn: DataTableColumn = {
  title: 'Contact',
  value: ({ user, email }: PGAcceptedInviteItem | POSAcceptedInviteItem) => (
    <Box width="220px">
      <Box>{user?.contact_mobile || ''}</Box>
      <Text truncateAfterLines={1}>{email}</Text>
    </Box>
  ),
};
export const emailColumn: DataTableColumn = {
  title: 'Registered Email',
  value: (item) => (
    <Box width="220px">
      <Text truncateAfterLines={1}>{maskedEmail.value(item)}</Text>
    </Box>
  ),
};

export const emailIdColumn: DataTableColumn = {
  ...emailColumn,
  title: 'Email ID',
};

export const accountNameColumn: DataTableColumn = {
  title: 'Account Name',
  value: (item: { name: string }): string => item.name,
};
export const nameColumn: DataTableColumn = {
  ...accountNameColumn,
  title: 'Name',
};

export const contactNoColumn: DataTableColumn = {
  title: 'Contact',
  value: (item: SubmerchantInviteItem): string => item.contact_no,
};

export const lastInvitedOnColumn: DataTableColumn = {
  title: 'Last Invited On',
  value: getTime('updated_at', 'll'),
};
