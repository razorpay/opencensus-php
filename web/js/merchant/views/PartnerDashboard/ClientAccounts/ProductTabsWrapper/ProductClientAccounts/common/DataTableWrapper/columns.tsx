import React from 'react';
import { Box, Text, Badge } from '@razorpay/blade/components';
import { Link as RouterLink } from 'react-router-dom';

import { DataTableColumn } from 'common/typings';
import Time from 'common/ui/Time';
import { getTime } from 'common/ui/item';
import { email as maskedEmail } from 'common/ui/item/pair';
import { SubmerchantInviteItem } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/PaymentsClients/AllInvites/api';
import {
  PGAcceptedInviteItem,
  POSAcceptedInviteItem,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/api';
import { isInviteRecentlyAccepted } from 'merchant/views/PartnerDashboard/SubMerchant/utils';

export const addedOnColumn: DataTableColumn = {
  title: 'Added On',
  value: getTime('created_at', 'll'),
};

export const inviteAcceptedOn: DataTableColumn = {
  title: 'Invite Accepted On',
  value: (item: POSAcceptedInviteItem | PGAcceptedInviteItem) => (
    <Box minWidth="155px">
      <Time value={item.created_at} format="ll" />
      {isInviteRecentlyAccepted(item.created_at) && (
        <Badge
          display="inline-block"
          contrast="high"
          fontWeight="bold"
          marginLeft="spacing.3"
          color="positive"
        >
          NEW
        </Badge>
      )}
    </Box>
  ),
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
