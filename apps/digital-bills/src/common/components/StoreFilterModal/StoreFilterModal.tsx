import React, { useState } from 'react';
import {
  Box,
  Modal,
  Button,
  ModalBody,
  ModalFooter,
  ModalHeader,
} from '@razorpay/blade/components';

import { zIndicesMap } from '@apps/digital-bills/src/utils/constants';
import StoreFilter from '@apps/digital-bills/src/common/components/StoreFilterModal/StoreFilter';

import type { StoresType } from '@apps/digital-bills/src/common/components/StoreFilterModal/types';

type StoreFilterModalProps = {
  isOpen: boolean;
  dismiss: () => void;
  onStoresSelect: (stores: string[], selectedStores: StoresType) => void;
  selectedStores: StoresType;
};

const StoreFilterModal = ({
  isOpen,
  dismiss,
  onStoresSelect,
  selectedStores = {},
}: StoreFilterModalProps): React.ReactElement => {
  const [stores, setStores] = useState<StoresType>(selectedStores || {});

  const handleModalSubmit = () => {
    onStoresSelect(Object.keys(stores), stores);
    dismiss();
  };
  return (
    <Modal isOpen={isOpen} onDismiss={dismiss} size="medium" zIndex={zIndicesMap.modalOverlay}>
      <ModalHeader title="Store Filter" />
      <ModalBody>
        <StoreFilter selectedStores={stores} onSelectStores={(stores) => setStores(stores)} />
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.5" justifyContent="flex-end" width="100%">
          <Button variant="tertiary" onClick={dismiss}>
            Cancel
          </Button>
          <Button onClick={handleModalSubmit}>Submit</Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default StoreFilterModal;
