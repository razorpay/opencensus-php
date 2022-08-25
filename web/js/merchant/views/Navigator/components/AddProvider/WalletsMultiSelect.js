import React from 'react';
import { MultiSelectDropdownWithSearch } from 'merchant/components/MultiSelectDropdownWithSearch';

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
          <label className="title-left">Wallets</label>
        </div>
        <div className="col-xs-6">
          <MultiSelectDropdownWithSearch
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
