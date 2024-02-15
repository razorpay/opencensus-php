import React from 'react';

import {
  POSAgentsMap,
  POSSubmerchantInviteItem,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/POSClients/AllInvites/api';
import { GetColumnsType } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/DataTableWrapper';
import {
  emailIdColumn,
  nameColumn,
  contactNoColumn,
  lastInvitedOnColumn,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/DataTableWrapper/columns';
import InviteActionButton from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/components/InviteActionButton';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

const actionsColumn = {
  title: 'Actions',
  value: (item: POSSubmerchantInviteItem) => (
    <InviteActionButton productType={PRODUCT_TYPE.POS} invite={item} />
  ),
};

type customColumnsGetterArgs = { posAgentsMap: POSAgentsMap };
export const customColumnsGetter = ({ posAgentsMap }: customColumnsGetterArgs): GetColumnsType => {
  const invitedByColumn = {
    title: 'Invited By',
    value: (item: POSSubmerchantInviteItem): string =>
      posAgentsMap[item.inviter_user_id]?.inviterName || item.inviter_email || item.inviter_user_id,
  };

  const getColumns: GetColumnsType = (_args) => [
    nameColumn,
    emailIdColumn,
    contactNoColumn,
    invitedByColumn,
    lastInvitedOnColumn,
    actionsColumn,
  ];
  return getColumns;
};
