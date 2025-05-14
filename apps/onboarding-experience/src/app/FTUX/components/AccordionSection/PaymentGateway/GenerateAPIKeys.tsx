import React from 'react';
import { Box, Button, EyeIcon, RefreshIcon } from '@razorpay/blade/components';
import { useStore } from '@federated/apps/shell/commonStore';
import { useMerchantContext } from '@FTUX/context/MerchantContext';

const GenerateAPIKeys = () => {
  const { mode } = useStore((state) => state.session);
  const { merchantData } = useMerchantContext();
  const hasApiKeys = Boolean(merchantData?.merchantById?.apiKeys?.[0]?.id);
  const activeMode = mode === 'test' ? 'Test' : 'Live';

  return (
    <Box>
      {hasApiKeys ? (
        <Button variant="secondary" size="medium" icon={RefreshIcon} onClick={() => {}}>
          Regenerate {activeMode} API Keys
        </Button>
      ) : (
        <Button color="primary" size="medium" icon={EyeIcon} onClick={() => {}}>
          Reveal {activeMode} API Keys
        </Button>
      )}
    </Box>
  );
};

export default GenerateAPIKeys;
