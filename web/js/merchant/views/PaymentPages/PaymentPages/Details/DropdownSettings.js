import React from 'react';
import { withRouter } from 'react-router';
import Dropdown, { DropdownTrigger, DropdownContent } from 'common/ui/Dropdown';
import Button from 'common/new-ui/Button';
import Tooltip from 'common/ui/Tooltip';

const DropdownSettings = ({ onShow, onHide, history, paymentPageEntity }) => {
  return (
    <span className="d-inline-block">
      <Dropdown closeOnClick={false} onShow={onShow} onHide={onHide}>
        <DropdownTrigger
          className={`dropdown-toggle Dropdown--Notifications-toggle${
            false ? ' dropdown-toggle--large-icon' : ''
          }`}
        >
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
            <li
              type="button"
              class="btn"
              onClick={() =>
                history.push(`/paymentpages/${paymentPageEntity.id}/edit?modal=receipt`)
              }
            >
              <Button.Transparent className="button--highlight">
                <i className="i i-receipt mr-10"></i>
                Receipt Settings
              </Button.Transparent>
            </li>
            <li
              type="button"
              class="btn"
              onClick={() => history.push(`/paymentpages/${paymentPageEntity.id}/edit?modal=page`)}
            >
              <Button.Transparent className="button--highlight">
                <i className="i i-settings-outline mr-10"></i>
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
