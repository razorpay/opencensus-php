import React from 'react';
import { Link as RouterLink } from 'react-router-dom';

import { DataTableColumn } from 'common/typings';
import { handleClientAccountSelected } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/POSClients/AcceptedInvites/analytics';
import { GetColumnsType } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/DataTableWrapper';
import {
  nameColumn,
  mobileAndEmailColumn,
  inviteAcceptedOn,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/DataTableWrapper/columns';
import { POSAcceptedInviteItem } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/api';
import SubMerchantKycStatusLabel from 'merchant/views/PartnerDashboard/SubMerchant/components/SubMerchantKycStatusLabel';
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

const kycLastSubmittedByColumn: DataTableColumn = {
  title: 'KYC Last Submitted by',
  value: (submerchant: POSAcceptedInviteItem): string => {
    const last_kyc_performed_by = submerchant.pos?.last_kyc_performed_by;
    return last_kyc_performed_by?.name || last_kyc_performed_by?.contact_email || '--';
  },
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
    <ActionButtonKYC submerchant={submerchant} productType={PRODUCT_TYPE.POS} />
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
