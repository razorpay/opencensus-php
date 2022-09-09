import React from 'react';
import { MultiSelectDropdownWithSearch } from 'merchant/components/MultiSelectDropdownWithSearch';
import { WalletLabels } from '../util';

export const WalletsMultiSelect = ({
  walletOptions,
  walletSelected,
  changeGatewayWallets,
  disabled,
}) => {
  return (
    <div className="col-xs-12">
      <div className="row">
        <div className="col-xs-3">
          <label className="title-left gateway-detail-title">Wallets</label>
        </div>
        <div className="col-xs-6">
          <MultiSelectDropdownWithSearch
            labelMapping={WalletLabels}
            options={walletOptions}
            selected={walletSelected}
            disabled={disabled}
            placeholder="Select wallets"
            searchBarPlaceholder="Search Wallet"
            handleCheckBoxChange={changeGatewayWallets}
          />
        </div>
      </div>
    </div>
  );
};
