import React from 'react';
import { withRouter } from 'common/deprecated/withRouter';

import Dropdown, { DropdownTrigger, DropdownContent } from 'common/ui/Dropdown';
import Button from 'common/new-ui/Button';
import Tooltip from 'common/ui/Tooltip';

import track from './track';
import { getProductBaseLink } from 'merchant/views/PaymentPages/PaymentPages/utils';

const onShow = () => {
  track.settingsDropdown();
};

const DropdownSettings = ({
  history,
  paymentPageEntity,
  isStorefrontPage,
  isBatchPaymentPages,
}) => {
  const productBaseUrl = getProductBaseLink(
    isStorefrontPage,
    paymentPageEntity.id,
    isBatchPaymentPages,
  );

  return (
    <span className="d-inline-block">
      <Dropdown closeOnClick={false} onShow={onShow}>
        <DropdownTrigger className="dropdown-toggle Dropdown--Notifications-toggle}">
          <Button class="Button--primary--invert">
            <i className="i i-settings-outline" />
            <i className="i i-chevron-down" />
            <i className="i i-chevron-up" />
            <Tooltip theme="dark" align="top">
              Settings
            </Tooltip>
          </Button>
        </DropdownTrigger>
        <DropdownContent>
          <ul class="dropdown-menu">
            {!isStorefrontPage ? (
              <li
                type="button"
                class="btn"
                onClick={() => {
                  track.receiptSettings();

                  history.push(`${productBaseUrl}/edit?modal=receipt`);
                }}
              >
                <Button.Transparent className="button--highlight">
                  <i className="i i-receipt mr-10" />
                  Receipt Settings
                </Button.Transparent>
              </li>
            ) : null}
            <li
              type="button"
              class="btn"
              onClick={() => {
                track.pageSettings();

                history.push(`${productBaseUrl}/edit?modal=page`);
              }}
            >
              <Button.Transparent className="button--highlight">
                <i className="i i-settings-outline mr-10" />
                Page Settings
              </Button.Transparent>
            </li>
          </ul>
        </DropdownContent>
      </Dropdown>
    </span>
  );
};

export default withRouter(DropdownSettings);
