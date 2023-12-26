import React from 'react';
import {
  Box,
  Text,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  ActionListItem,
} from '@razorpay/blade/components';

import { WalletLabels } from 'merchant/views/Navigator/components/util';

const WalletsMultiSelect = (props) => {
  const { walletOptions, walletSelected, changeGatewayWallets, disabled, isFormEdit } = props;

  return (
    <Box display="flex" alignItems="center">
      <Box minWidth="180px">
        <Text>Wallets</Text>
      </Box>
      {isFormEdit ? (
        <Box minWidth="280px">
          <Dropdown selectionType="multiple" testID="wallet-select">
            <SelectInput
              placeholder="Select Wallets"
              name="wallets"
              value={walletSelected}
              isDisabled={disabled}
              onChange={changeGatewayWallets}
            />
            <DropdownOverlay>
              <ActionList>
                {walletOptions.map((wallet) => (
                  <ActionListItem
                    key={wallet}
                    title={WalletLabels[wallet] ?? wallet}
                    value={wallet}
                  />
                ))}
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
        </Box>
      ) : (
        <Text weight="bold">
          {walletSelected.map((wallet) => WalletLabels[wallet])?.join(', ')}
        </Text>
      )}
    </Box>
  );
};

export default WalletsMultiSelect;
