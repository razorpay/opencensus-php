import React, { useState } from 'react';
import { useStore } from '@apps/shell/src/client/store/commonStore';
import { SwitchMerchantTypeaheadV2 } from '@libs/shared-ui';
import { createNewAccountTrack } from '@libs/shared-utils';
import { EXISTING_ACCOUNT_LABEL } from './constants';

const SwitchMerchantTypeahead: React.FC<{
  onSwitchMerchant: (arg: string) => void;
  isOpen: boolean;
  onDismiss: () => void;
  isMobile: boolean;
}> = ({ onSwitchMerchant, isOpen, onDismiss, isMobile }) => {
  const user = useStore((state) => state.session.user);
  const merchantIds = Object.keys(user.merchants) || [];
  let merchantsWithoutNameCount = 1;

  // Assigning name 'Existing Account <n>' to all the accounts whose name is null
  const merchants = merchantIds.reduce((acc, merchantID) => {
    const merchant = { ...user.merchants[merchantID] };
    if (!merchant.name) {
      merchant.name = `${EXISTING_ACCOUNT_LABEL} ${merchantsWithoutNameCount}`;
      merchantsWithoutNameCount++;
    }
    acc[merchantID] = merchant;
    return acc;
  }, {});

  const [filteredMerchants, setFilteredMerchants] = useState<string[]>(merchantIds);

  const handleCreateNewAccount = () => {
    createNewAccountTrack();
  };

  const onSearch = (searchValue: string | undefined) => {
    if (!searchValue) {
      setFilteredMerchants(merchantIds);
      return;
    }

    const searchValueInLowerCase = searchValue.toLowerCase();

    const searchedMerchants = merchantIds.filter((item) => {
      const { name = '', display_name = '' } = merchants[item] || {};
      const merchantId = item.toLowerCase();
      return (
        name?.toLowerCase().indexOf(searchValueInLowerCase) >= 0 ||
        display_name?.toLowerCase().indexOf(searchValueInLowerCase) >= 0 ||
        merchantId.indexOf(searchValueInLowerCase) >= 0
      );
    });

    setFilteredMerchants(searchedMerchants);
  };

  const isCreateMerchantCTAEnabled = window?.IS_CREATE_MERCHANT_CTA_EANABLED;

  return (
    <SwitchMerchantTypeaheadV2
      onSwitchMerchant={onSwitchMerchant}
      isOpen={isOpen}
      isMobile={isMobile}
      onDismiss={onDismiss}
      onSearch={onSearch}
      filteredMerchants={filteredMerchants}
      user={user}
      merchants={merchants}
      handleCreateNewAccount={handleCreateNewAccount}
      showCreateMerchantCTAEnabled={isCreateMerchantCTAEnabled}
    />
  );
};

export default SwitchMerchantTypeahead;
