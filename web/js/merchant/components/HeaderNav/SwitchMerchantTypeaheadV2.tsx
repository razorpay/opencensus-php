import React, { useState } from 'react';
import { connect } from 'react-redux';
import { ActionList, ActionListItem, Box, SearchInput } from '@razorpay/blade/components';
import useModalComponents from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/hooks/useModalComponents';
import { useMobile } from 'common/hooks/useMobile';

const SwitchMerchantTypeaheadV2: React.FC<{
  onSwitchMerchant: (arg: string) => void;
  user: Record<string, any>;
  isOpen: boolean;
  onDismiss: () => void;
}> = ({ onSwitchMerchant, user, isOpen, onDismiss }) => {
  const isMobile = useMobile();
  const merchantList = Object.keys(user.merchants) || [];

  const [filteredMerchants, setFilteredMerchants] = useState<string[]>(merchantList);

  const onSearch = (searchValue: string | undefined) => {
    if (!searchValue) {
      setFilteredMerchants(merchantList);
      return;
    }

    const searchValueInLowerCase = searchValue.toLowerCase();

    const merchants = merchantList.filter((item) => {
      const { name = '', display_name = '' } = user.merchants[item] || {};
      const merchantId = item.toLowerCase();
      return (
        name?.toLowerCase().indexOf(searchValueInLowerCase) >= 0 ||
        display_name?.toLowerCase().indexOf(searchValueInLowerCase) >= 0 ||
        merchantId.indexOf(searchValueInLowerCase) >= 0
      );
    });

    setFilteredMerchants(merchants);
  };

  const { Modal, ModalBody, ModalHeader } = useModalComponents(isMobile);

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
                  title={user.merchants[merchantId].display_name || user.merchants[merchantId].name}
                  key={merchantId}
                  description={`MID : ${merchantId}`}
                  onClick={() => onSwitchMerchant(user.merchants[merchantId])}
                  value={merchantId}
                />
              );
            })}
          </ActionList>
        </Box>
      </ModalBody>
    </Modal>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps, null)(SwitchMerchantTypeaheadV2);
