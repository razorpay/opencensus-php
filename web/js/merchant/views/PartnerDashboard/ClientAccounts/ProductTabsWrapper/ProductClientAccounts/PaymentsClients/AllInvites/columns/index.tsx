import React from 'react';

import { GetColumnsType } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/DataTableWrapper';
import {
  emailIdColumn,
  nameColumn,
  contactNoColumn,
  lastInvitedOnColumn,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/DataTableWrapper/columns';
import InviteActionButton from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/components/InviteActionButton';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

const actions = {
  title: 'Actions',
  value: (item) => <InviteActionButton productType={PRODUCT_TYPE.PG} invite={item} />,
};

export const getColumns: GetColumnsType = (_args) => [
  nameColumn,
  emailIdColumn,
  contactNoColumn,
  actions,
  lastInvitedOnColumn,
];
