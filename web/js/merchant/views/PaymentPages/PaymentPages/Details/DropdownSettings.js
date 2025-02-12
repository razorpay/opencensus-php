import React, { useCallback } from 'react';
import { withRouter } from 'common/deprecated/withRouter';

import Dropdown, { DropdownTrigger, DropdownContent } from 'common/ui/Dropdown';
import Button from 'common/new-ui/Button';
import Tooltip from 'common/ui/Tooltip';

import track from './track';
import { getProductBaseLink } from 'merchant/views/PaymentPages/PaymentPages/utils';

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

  const onShow = useCallback(() => {
    track.settingsDropdown({
      pageId: paymentPageEntity?.id,
      published_page_url: paymentPageEntity?.short_url,
      product_page: isStorefrontPage ? 'Storefront Page' : 'Payment Page',
    });
  }, [paymentPageEntity]);

  return (
    <span className="d-inline-block">
      <Dropdown closeOnClick={false} onShow={onShow}>
        <DropdownTrigger className="dropdown-toggle Dropdown--Notifications-toggle}">
          <Button className="Button--primary--invert">
            <i className="i i-settings-outline" />
            <i className="i i-chevron-down" />
            <i className="i i-chevron-up" />
            <Tooltip theme="dark" align="top">
              Settings
            </Tooltip>
          </Button>
        </DropdownTrigger>
        <DropdownContent>
          <ul className="dropdown-menu">
            {!isStorefrontPage ? (
              <li
                type="button"
                className="btn"
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
              className="btn"
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
