import React from 'react';
import { Tooltip, Box } from '@razorpay/blade/components';
import { Link as RouterLink } from 'react-router-dom';

import { DataTableColumn, DataTableColumns, User } from 'common/typings';
import ShowWhen from 'merchant/components/ShowWhen';
import { SubmerchantSettlementLabel } from 'merchant/components/StatusLabel';
import { handleClientAccountSelected } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/PaymentsClients/AcceptedInvites/analytics';
import { GetColumnsType } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/DataTableWrapper';
import {
  emailColumn,
  nameColumn,
  addedOnColumn,
  appIdColumn,
  mobileAndEmailColumn,
  inviteAcceptedOn,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/DataTableWrapper/columns';
import { PGAcceptedInviteItem } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/api';
import { getIsInviteFlowEnabled } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/utils/tabsData';
import SubMerchantKycStatusLabel from 'merchant/views/PartnerDashboard/SubMerchant/components/SubMerchantKycStatusLabel';
import { PARTNER_TYPE, PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

import ActionButtonKYC from './ActionButtonKYC';
import SwitchMerchant from './SwitchMerchant';

const idColumn = {
  title: 'Account ID',
  value: (item) => (
    <RouterLink
      to={`/partners/submerchants/${item.id}`}
      onClick={() => {
        handleClientAccountSelected(PRODUCT_TYPE.PG, item.id);
      }}
    >
      {item.id}
    </RouterLink>
  ),
};

const idColumnWithoutLink = {
  title: 'Account ID',
  value: (item) => (!item.name ? idColumn.value(item) : item.id),
};

const settlementStatus = {
  title: (
    <>
      Settlement Status
      <Tooltip
        content="Current status of whether the merchant can receive the settlement"
        placement="top"
      >
        <i className="i i-info-circle" />
      </Tooltip>
    </>
  ),
  value: (submerchant) => (
    <SubmerchantSettlementLabel
      status={
        submerchant.details &&
        submerchant.details.activation_status === 'activated' &&
        submerchant.hold_funds === false
          ? 'active'
          : 'inactive'
      }
    />
  ),
};

const purePlatformNameColumn = {
  ...nameColumn,
  value: (item) => (
    <RouterLink
      to={`/partners/submerchants/${item.id}/${item.application.id}`}
      onClick={() => {
        handleClientAccountSelected(PRODUCT_TYPE.PG, item.id, item.application.id);
      }}
    >
      {item.name}
    </RouterLink>
  ),
};

const switchAccountLabel = (user: User, itemDetails: PGAcceptedInviteItem): boolean => {
  if (itemDetails.dashboard_access) {
    const isDeactivatedCurlecMerchantAccount = user?.isOrgCurlec && !itemDetails.activated;
    if (isDeactivatedCurlecMerchantAccount) {
      return false;
    }
    return true;
  }
  return false;
};

const activationStatusColumn = {
  title: (
    <Box display="flex" flexDirection="row" gap="spacing.3" alignItems="center">
      Activation Status
      <Tooltip content="Current status of merchant's activation request" placement="top">
        <i className="i i-info-circle" />
      </Tooltip>
    </Box>
  ),
  value: (submerchant) => (
    <SubMerchantKycStatusLabel
      showDescriptionAsTooltip
      activation_status={submerchant.details.activation_status}
      kyc_access={submerchant.kyc_access}
    />
  ),
};

const actionsColumn: DataTableColumn = {
  title: (
    <Box display="flex" flexDirection="row" gap="spacing.3" alignItems="center">
      Actions
      <ShowWhen
        additionalCondition={(user) =>
          user.isPartner(PARTNER_TYPE.AGGREGATOR, PARTNER_TYPE.RESELLER)
        }
      >
        <Tooltip
          content="Send request to gain access to perform your referred merchant's KYC"
          placement="top"
        >
          <i className="i i-info-circle" />
        </Tooltip>
      </ShowWhen>
    </Box>
  ),

  value: (submerchant: PGAcceptedInviteItem) => (
    <>
      <ShowWhen additionalCondition={(user) => switchAccountLabel(user, submerchant)}>
        <SwitchMerchant submerchant={submerchant} />
      </ShowWhen>
      <ShowWhen additionalCondition={(user) => !switchAccountLabel(user, submerchant)}>
        <ActionButtonKYC submerchant={submerchant} productType={PRODUCT_TYPE.PG} />
      </ShowWhen>
    </>
  ),
};

export const getColumns: GetColumnsType = ({ user, org, experiments }) => {
  const { isInviteFlowEnabled } = getIsInviteFlowEnabled(PRODUCT_TYPE.PG, experiments);
  const isSubMerchantKYCAccess = user.isFeatureEnabled('partner_sub_kyc_access');
  const conditionalNameColumn = user.isPartner(PARTNER_TYPE.PURE_PLATFORM)
    ? purePlatformNameColumn
    : nameColumn;

  const conditionalIdColumn = user.isPartner(PARTNER_TYPE.PURE_PLATFORM)
    ? idColumnWithoutLink
    : idColumn;

  const switchMerchantColumn = {
    title: 'Switch Account',
    value: (item) => (
      <SwitchMerchant submerchant={item} isDisabled={!switchAccountLabel(user, item)} />
    ),
  };

  let conditionalAppIdColumn = [] as DataTableColumns;
  let conditionalSwitchMerchantColumn = [] as DataTableColumns;
  if (user.isPartner(PARTNER_TYPE.PURE_PLATFORM)) {
    conditionalAppIdColumn = [appIdColumn];
    conditionalSwitchMerchantColumn = [switchMerchantColumn];
  } else if (user.isPartner('fully_managed')) {
    conditionalSwitchMerchantColumn = [switchMerchantColumn];
  }

  if (isInviteFlowEnabled) {
    const conditionalActionsColumn =
      user.isPartner(PARTNER_TYPE.PURE_PLATFORM) && !isSubMerchantKYCAccess ? [] : [actionsColumn];

    return [
      conditionalIdColumn,
      conditionalNameColumn,
      mobileAndEmailColumn,
      activationStatusColumn,
      ...conditionalActionsColumn,
      ...conditionalSwitchMerchantColumn,
      ...conditionalAppIdColumn,
      inviteAcceptedOn,
    ];
  }

  // Note: productType is implicitly PG
  const isCombinedContactFilterEnabled = user.isOrgRZP;
  const emailOrContact = isCombinedContactFilterEnabled ? mobileAndEmailColumn : emailColumn;

  // Note: default columns for non-resellers or non-kyc access flag partners
  let columns = [
    conditionalIdColumn,
    conditionalNameColumn,
    emailOrContact,
    activationStatusColumn,
    settlementStatus,
    ...conditionalSwitchMerchantColumn,
    ...conditionalAppIdColumn,
    addedOnColumn,
  ];

  if (user.isSubMerchantKycEnabled && user.isPartner(PARTNER_TYPE.RESELLER)) {
    const orgCode = org?.custom_code || 'rzp';
    const ORG_COLUMNS = {
      rzp: [
        conditionalIdColumn,
        nameColumn,
        emailOrContact,
        ...conditionalAppIdColumn,
        activationStatusColumn,
        actionsColumn,
        addedOnColumn,
        ...conditionalSwitchMerchantColumn,
      ],
      curlec: [
        conditionalIdColumn,
        nameColumn,
        emailColumn,
        ...conditionalAppIdColumn,
        activationStatusColumn,
        addedOnColumn,
        ...conditionalSwitchMerchantColumn,
      ],
    };
    columns = ORG_COLUMNS[orgCode];
  }

  return columns;
};
