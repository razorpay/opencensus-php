import React, { useState } from 'react';
import { ActionList, ActionListItem, Box, SearchInput, Button } from '@razorpay/blade/components';
import { useModalComponents } from '../ModalComponent';

export const SwitchMerchantTypeaheadV2: React.FC<{
  onSwitchMerchant: (arg: string) => void;
  isOpen: boolean;
  onDismiss: () => void;
  isMobile: boolean;
  onSearch: (value: string | undefined) => void;
  filteredMerchants: {};
  user: any;
  merchants: string[];
  handleCreateNewAccount: () => void;
  showCreateMerchantCTAEnabled: boolean;
}> = ({
  onSwitchMerchant,
  isOpen,
  onDismiss,
  isMobile,
  onSearch,
  filteredMerchants,
  user,
  merchants,
  handleCreateNewAccount,
  showCreateMerchantCTAEnabled,
}) => {
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
      {showCreateMerchantCTAEnabled ? (
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
