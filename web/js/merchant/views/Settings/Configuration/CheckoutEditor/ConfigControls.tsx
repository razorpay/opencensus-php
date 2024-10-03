import React from 'react';
import { Box, Button } from '@razorpay/blade/components';

import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/createContext';

const ConfigControls = () => {
  const { isSaving, isValueModified, handleDiscardAllChanges, handleSave } = useCheckoutEditor();
  return (
    <Box display="flex" gap="spacing.5" justifyContent="flex-end">
      <Button
        variant="tertiary"
        isDisabled={!isValueModified || isSaving}
        onClick={handleDiscardAllChanges}
      >
        Discard changes
      </Button>
      <Button isDisabled={!isValueModified || isSaving} isLoading={isSaving} onClick={handleSave}>
        Save all changes
      </Button>
    </Box>
  );
};

export default ConfigControls;
