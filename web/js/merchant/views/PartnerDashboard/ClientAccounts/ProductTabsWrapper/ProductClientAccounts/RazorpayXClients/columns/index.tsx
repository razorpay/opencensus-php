import React from 'react';
import { Link as RouterLink } from 'react-router-dom';

import { DataTableColumns } from 'common/typings';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import { XSubmerchantCAStatusLabel } from 'merchant/components/StatusLabel';
import { handleClientAccountSelected } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/RazorpayXClients/analytics';
import { GetColumnsType } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/DataTableWrapper';
import {
  emailColumn,
  accountNameColumn,
  addedOnColumn,
  appIdColumn,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/DataTableWrapper/columns';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

const idColumn = {
  title: 'Account ID',
  value: (item) => item.id,
};

const xNameColumn = {
  ...accountNameColumn,
  value: (item) => (
    <RouterLink
      to={`/partners/submerchants/x/${item.id}`}
      onClick={() => handleClientAccountSelected(PRODUCT_TYPE.X, item.id)}
    >
      {item.name}
    </RouterLink>
  ),
};

const xCurrentAccountStatus = {
  title: (
    <>
      Current Account Status&nbsp;
      <span>
        <i className="i i-info-circle" />
        &nbsp;
        <PopoverComponent align="top" theme="dark">
          <PopoverBody>Current status of merchant&apos;s current account</PopoverBody>
        </PopoverComponent>
      </span>
    </>
  ),
  value: (submerchant) => (
    <XSubmerchantCAStatusLabel
      status={
        submerchant.banking_account && submerchant.banking_account.ca_status
          ? submerchant.banking_account.ca_status.toLowerCase()
          : 'inactive'
      }
    />
  ),
};
export const getColumns: GetColumnsType = ({ user }) => {
  let conditionalAppIdColumn = [] as DataTableColumns;
  if (user.isPartner('pure_platform')) {
    conditionalAppIdColumn = [appIdColumn];
  }
  const xColumns = [
    xNameColumn,
    idColumn,
    emailColumn,
    ...conditionalAppIdColumn,
    xCurrentAccountStatus,
    addedOnColumn,
  ];

  return xColumns;
};
