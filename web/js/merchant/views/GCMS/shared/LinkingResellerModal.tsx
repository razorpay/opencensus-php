import React, { useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import {
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Button,
  Box,
  useToast,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';
import ResellerTable from '../Resellers/ResellerTable';
import { linkResellerToProgram } from '../Programs/queries';
import { RESELLER_SERVICE } from '../Resellers/ResellerTable/constants';

function LinkingResellerModal({ program, mode, merchantId, closeModal, refetchQuery }) {
  const { show } = useToast();
  const [selectedResellers, setSelectedResellers] = useState([]);
  const {
    mutateAsync: linkResellerMutation,
    isLoading,
    error,
    isError,
  } = useMutation({
    mutationFn: async (params) => {
      await linkResellerToProgram(params);
    },
    onError: () => {
      show({
        type: 'informational',
        content: 'Failed to link reseller to the program',
      });
    },
    onSuccess: () => {
      show({
        color: 'positive',
        type: 'informational',
        content: 'Reseller successfully linked to the program',
      });
      closeModal();
      refetchQuery();
    },
  });
  function selectResellers(resellers) {
    setSelectedResellers(resellers);
  }

  async function addResellers() {
    try {
      await linkResellerMutation({
        merchantId,
        programId: program.id,
        resellerIds: selectedResellers,
        defaultDiscount: program.policies?.min_discount_percent,
      });
    } catch (err) {}
  }
  return (
    <Modal isOpen={true} onDismiss={closeModal} size="large">
      <ModalHeader title={`Add reseller to ${program.name}`} />
      <ModalBody>
        <ResellerTable
          mode={mode}
          merchantId={merchantId}
          accessMode="edit"
          onSelection={selectResellers}
          filterOptions={{ statusVisible: false }}
          service={RESELLER_SERVICE.UNMAPPED_RESELLERS}
          programId={program.id}
        />
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button
            onClick={addResellers}
            isDisabled={isLoading || selectedResellers.length === 0}
            isLoading={isLoading}
          >
            Add Reseller
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
}

export default connect((state) => ({
  mode: state.session?.mode,
  merchantId: state.session?.user?.current,
}))(LinkingResellerModal);
