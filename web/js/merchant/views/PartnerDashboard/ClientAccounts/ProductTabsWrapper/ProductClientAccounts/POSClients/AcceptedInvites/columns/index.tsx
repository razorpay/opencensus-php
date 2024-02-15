import React from 'react';
import { Badge, Box } from '@razorpay/blade/components';
import { Link as RouterLink } from 'react-router-dom';

import { DataTableColumn } from 'common/typings';
import Time from 'common/ui/Time';
import { handleClientAccountSelected } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/POSClients/AcceptedInvites/analytics';
import { GetColumnsType } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/DataTableWrapper';
import {
  nameColumn,
  mobileAndEmailColumn,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/DataTableWrapper/columns';
import { POSAcceptedInviteItem } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/api';
import SubMerchantKycStatusLabel from 'merchant/views/PartnerDashboard/SubMerchant/components/SubMerchantKycStatusLabel';
import { isInviteRecentlyAccepted } from 'merchant/views/PartnerDashboard/SubMerchant/utils';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

import ActionButtonKYC from './ActionButtonKYC';

const idColumn: DataTableColumn = {
  title: 'Account ID',
  value: (item: POSAcceptedInviteItem) => (
    <RouterLink
      to={`/partners/submerchants/pos/${item.id}`}
      onClick={() => {
        handleClientAccountSelected(PRODUCT_TYPE.POS, item.id);
      }}
    >
      {item.id}
    </RouterLink>
  ),
};

const inviteAcceptedOn: DataTableColumn = {
  title: 'Invite Accepted On',
  value: (item: POSAcceptedInviteItem) => (
    <>
      <Time value={item.created_at} format="ll" />
      {isInviteRecentlyAccepted(item.created_at) && (
        <Box display="inline-block">
          <Badge contrast="high" fontWeight="bold" marginLeft="spacing.3" variant="positive">
            NEW
          </Badge>
        </Box>
      )}
    </>
  ),
};

const kycLastSubmittedByColumn: DataTableColumn = {
  title: 'KYC Last Submitted by',
  value: (submerchant: POSAcceptedInviteItem): string =>
    submerchant.pos?.last_kyc_performed_by.name,
};

const kycStatusColumn: DataTableColumn = {
  title: 'KYC Status',
  value: (submerchant: POSAcceptedInviteItem) => (
    <SubMerchantKycStatusLabel
      showDescriptionAsTooltip
      activation_status={submerchant.pos?.activation_status}
      kyc_access={submerchant.kyc_access}
    />
  ),
};

const actionsColumn: DataTableColumn = {
  title: 'Actions',
  value: (submerchant: POSAcceptedInviteItem) => (
    <ActionButtonKYC
      activation_status={submerchant.details.activation_status}
      kyc_access={submerchant.kyc_access}
      submerchant={submerchant}
      isPGProductWithInviteFlow
    />
  ),
};

export const getColumns: GetColumnsType = () => {
  return [
    idColumn,
    nameColumn,
    mobileAndEmailColumn,
    kycStatusColumn,
    kycLastSubmittedByColumn,
    inviteAcceptedOn,
    actionsColumn,
  ];
};
