import React, { useState } from 'react';
import { ActionList, ActionListItem, Box, SearchInput, Button } from '@razorpay/blade/components';
import { connect } from 'react-redux';

import { useMobile } from 'common/hooks/useMobile';
import { useSplitzService } from 'common/splitz';
import { isExperimentActive } from 'common/utils/rzp-utils';
import useModalComponents from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/hooks/useModalComponents';

import { createNewAccountTrack } from './track';

const EXISTING_ACCOUNT_LABEL = 'Existing Account';

const SwitchMerchantTypeaheadV2: React.FC<{
  onSwitchMerchant: (arg: string) => void;
  user: Record<string, any>;
  isOpen: boolean;
  onDismiss: () => void;
}> = ({ onSwitchMerchant, user, isOpen, onDismiss }) => {
  const isMobile = useMobile();
  const {
    abExperiments: { create_merchant_cta },
  } = useSplitzService();

  const isCreateMerchantCTAEnabled = isExperimentActive(create_merchant_cta);

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

  const { Modal, ModalBody, ModalHeader, ModalFooter } = useModalComponents(isMobile);

  return (
    <Modal
      isOpen={isOpen}
      onDismiss={onDismiss}
      zIndex={9999}
      size="small"
      snapPoint={['0.15, 0.35, 0.5']}
    >
      <ModalHeader title="Switch Merchant" />
      <ModalBody>
        <Box display="flex" flexDirection="column" gap={isMobile ? 'spacing.5' : 'spacing.3'}>
          <Box paddingX={isMobile ? 'spacing.0' : 'spacing.3'}>
            <SearchInput
              placeholder="Search Merchant"
              onClearButtonClick={() => onSearch('')}
              onChange={({ value }) => onSearch(value)}
              label=""
            />
          </Box>
          <ActionList>
            {filteredMerchants.map((merchantId) => {
              const isActive = merchantId === user.current;
              return (
                <ActionListItem
                  isSelected={isActive}
                  title={merchants[merchantId].display_name || merchants[merchantId].name}
                  key={merchantId}
                  description={`MID : ${merchantId}`}
                  onClick={() => onSwitchMerchant(merchants[merchantId])}
                  value={merchantId}
                />
              );
            })}
          </ActionList>
        </Box>
      </ModalBody>
      {isCreateMerchantCTAEnabled ? (
        <ModalFooter>
          <Box display="flex" alignItems="center" justifyContent="end" columnGap="spacing.5">
            <Button
              onClick={handleCreateNewAccount}
              href={`${window.RAZORPAY_ACCOUNTS_URL}/merchants/new`}
              target="_blank"
              isFullWidth={true}
              testID="create-new-account-cta"
            >
              Create A New Account
            </Button>
          </Box>
        </ModalFooter>
      ) : null}
    </Modal>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps, null)(SwitchMerchantTypeaheadV2);
