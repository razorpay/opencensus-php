import React from 'react';
import { Button } from '@razorpay/blade/components';
import { Link as RouterLink } from 'react-router-dom';

import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import { CapitalSubMerchantStatusLabel } from 'merchant/components/StatusLabel';
import { handleClientAccountSelected } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/CapitalClients/analytics';
import { GetColumnsType } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/DataTableWrapper';
import {
  emailColumn,
  accountNameColumn,
  addedOnColumn,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/DataTableWrapper/columns';
import { PGAcceptedInviteItem } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/api';
import {
  CAPITAL_STATUS,
  PRODUCT_TYPE,
  PARTNERSHIPS_WEBSITE_LINKS,
} from 'merchant/views/PartnerDashboard/constants';

const idColumnWithoutLink = {
  title: 'Account ID',
  value: (item) => item.id,
};
const capitalName = {
  ...accountNameColumn,
  value: (item) => (
    <RouterLink
      onClick={() => {
        handleClientAccountSelected(PRODUCT_TYPE.CAPITAL, item.id);
      }}
      to={`/partners/submerchants/capital/${item.id}`}
    >
      {item.name}
    </RouterLink>
  ),
};

const capitalStatus = {
  title: (
    <>
      Activation Status&nbsp;
      <span>
        <i className="i i-info-circle" />
        &nbsp;
        <PopoverComponent align="top" theme="dark">
          <PopoverBody>
            Click{' '}
            <a
              target="_blank"
              href={PARTNERSHIPS_WEBSITE_LINKS.CAPITAL_ADD_PARTNERS_KNOW_MORE_URL}
              rel="noopener noreferrer"
            >
              here
            </a>{' '}
            to know more
          </PopoverBody>
        </PopoverComponent>
      </span>
    </>
  ),
  value: (submerchant) => (
    <span>
      {submerchant.capitalActivationStatus ? (
        <CapitalSubMerchantStatusLabel status={submerchant.capitalActivationStatus} />
      ) : (
        <span>Not Available</span>
      )}
    </span>
  ),
};

type customColumnsGetterArgs = {
  handleCreateBureauLinkClick: (args: PGAcceptedInviteItem) => void;
  isCreateBureauButtonDisabled: Record<string, boolean>;
};
export const customColumnsGetter = ({
  handleCreateBureauLinkClick,
  isCreateBureauButtonDisabled,
}: customColumnsGetterArgs): GetColumnsType => {
  const actionsColumn = {
    title: 'Actions',
    value: (item) => {
      return (
        <Button
          variant="secondary"
          onClick={() => {
            handleCreateBureauLinkClick(item);
          }}
          size="small"
          isDisabled={
            isCreateBureauButtonDisabled[item.id] ||
            item?.capitalActivationStatus?.toLowerCase() !== CAPITAL_STATUS.bureau_submission
          }
        >
          Create Bureau Link
        </Button>
      );
    },
  };

  const getColumns: GetColumnsType = ({ experiments }) => {
    const { isPartnershipCapitalBureauLinkEnabled } = experiments;
    const capitalColumns = [
      capitalName,
      idColumnWithoutLink,
      emailColumn,
      addedOnColumn,
      capitalStatus,
    ];
    if (isPartnershipCapitalBureauLinkEnabled) {
      capitalColumns.push(actionsColumn);
    }
    return capitalColumns;
  };

  return getColumns;
};
