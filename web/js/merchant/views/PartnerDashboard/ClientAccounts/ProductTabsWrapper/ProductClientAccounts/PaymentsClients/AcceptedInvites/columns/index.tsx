import React from 'react';
import { Badge, Box } from '@razorpay/blade/components';
import { Link as RouterLink } from 'react-router-dom';

import { DataTableColumns, User } from 'common/typings';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import Time from 'common/ui/Time';
import { SubmerchantSettlementLabel } from 'merchant/components/StatusLabel';
import { handleClientAccountSelected } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/PaymentsClients/AcceptedInvites/analytics';
import { GetColumnsType } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/DataTableWrapper';
import {
  emailColumn,
  nameColumn,
  addedOnColumn,
  appIdColumn,
  mobileAndEmailColumn,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/DataTableWrapper/columns';
import { PGAcceptedInviteItem } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/api';
import { getIsInviteFlowEnabled } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/utils/tabsData';
import SubMerchantKycStatusLabel from 'merchant/views/PartnerDashboard/SubMerchant/components/SubMerchantKycStatusLabel';
import { isInviteRecentlyAccepted } from 'merchant/views/PartnerDashboard/SubMerchant/utils';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

import ActionButtonKYC from './ActionButtonKYC';
import SwitchMerchant from './SwitchMerchant';

const idColumnWithoutLink = {
  title: 'Account ID',
  value: (item) => item.id,
};

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

const inviteAcceptedOn = {
  title: 'Invite Accepted On',
  value: (item) => (
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

const settlementStatus = {
  title: (
    <>
      Settlement Status&nbsp;
      <span>
        <i className="i i-info-circle" />
        &nbsp;
        <PopoverComponent align="top" theme="dark">
          <PopoverBody>
            Current status of whether the merchant can receive the settlement
          </PopoverBody>
        </PopoverComponent>
      </span>
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
    <>
      Activation Status&nbsp;
      <span>
        <i className="i i-info-circle" />
        &nbsp;
        <PopoverComponent align="top" theme="dark">
          <PopoverBody>Current status of merchant&apos;s activation request</PopoverBody>
        </PopoverComponent>
      </span>
    </>
  ),
  value: (submerchant) => (
    <SubMerchantKycStatusLabel
      showDescriptionAsTooltip
      activation_status={submerchant.details.activation_status}
      kyc_access={submerchant.kyc_access}
    />
  ),
};

// TODO v2: Separate out curlec logic completely
export const getColumns: GetColumnsType = ({ user, org, experiments }) => {
  const isInviteFlowEnabled = getIsInviteFlowEnabled(PRODUCT_TYPE.PG, experiments);
  const isSubMerchantKYCAccess = user.isFeatureEnabled('partner_sub_kyc_access');

  const conditionalNameColumn = user.isPartner('pure_platform')
    ? purePlatformNameColumn
    : nameColumn;

  const conditionalIdColumn = user.isPartner('pure_platform') ? idColumnWithoutLink : idColumn;
  const actionsColumn = {
    title: 'Actions',
    value: (submerchant) => (
      <ActionButtonKYC
        activation_status={submerchant.details.activation_status}
        kyc_access={submerchant.kyc_access}
        submerchant={submerchant}
        isPGProductWithInviteFlow={isInviteFlowEnabled}
      />
    ),
  };

  const switchMerchantColumn = {
    title: 'Switch Account',
    value: (item) => {
      if (switchAccountLabel(user, item)) {
        return <SwitchMerchant submerchant={item} />;
      }
      return 'No Access';
    },
  };

  let conditionalAppIdColumn = [] as DataTableColumns;
  let conditionalSwitchMerchantColumn = [] as DataTableColumns;
  if (user.isPartner('pure_platform')) {
    conditionalAppIdColumn = [appIdColumn];
  } else if (user.isPartner('aggregator', 'fully_managed')) {
    conditionalSwitchMerchantColumn = [switchMerchantColumn];
  }

  if (isInviteFlowEnabled) {
    const conditionalActionsColumn =
      user.isPartner('pure_platform') && !isSubMerchantKYCAccess ? [] : [actionsColumn];

    return [
      conditionalIdColumn,
      conditionalNameColumn,
      mobileAndEmailColumn,
      ...conditionalAppIdColumn,
      activationStatusColumn,
      ...conditionalActionsColumn,
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
    ...conditionalAppIdColumn,
    addedOnColumn,
    activationStatusColumn,
    settlementStatus,
    ...conditionalSwitchMerchantColumn,
  ];

  if (user.isSubMerchantKycEnabled && user.isPartner('reseller')) {
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
