import React from 'react';
import Popover, { PopoverBody } from 'common/ui/Popover';
import SwitchField from 'common/ui/Forms/SwitchField';

export function WalletAutoDebit({ label, provider, changeEnableAutoDebitSwitch }) {
  return (
    <div className="col-xs-12" key={label}>
      <div className="row">
        <div className="col-xs-3">
          <label for="name" className="gateway-detail-title">
            <span>Wallet auto-debit</span>
            <small className="help-content ml-4">
              <i className="i i-help-outline" />
              <Popover align="top" theme="dark">
                <PopoverBody>
                  <div>
                    Wallet auto-debit will allow your users to pay via wallet balance directly
                    without switching apps or exiting your website. Please check if you&#39;re
                    eligible before enabling auto-debit on your account.
                  </div>
                </PopoverBody>
              </Popover>
            </small>
          </label>
        </div>
        <div className="col-xs-9">
          <div className="auto-debit-switch-wrapper">
            <SwitchField
              type="prime round"
              defaultChecked={provider?.Gateway_details?.[label]}
              onChange={changeEnableAutoDebitSwitch}
            />
            <span>{provider?.Gateway_details?.[label] ? 'Enabled' : 'Disabled'}</span>
          </div>
        </div>
      </div>
    </div>
  );
}
