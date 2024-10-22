import React from 'react';
import Form from 'merchant/views/MagicCheckout/MagicXStoreSettings/components/Form';
import { Header, Wrapper } from 'merchant/views/MagicCheckout/MagicXStoreSettings/styled';
// import { checkMagicConfigurationFlow } from 'merchant/views/MagicCheckout/utils/Configuration';
// import { MagicXBanner } from 'merchant/views/MagicCheckout/components/MagicXShopifyBanner';

const MagicXStoreSettings: React.FC = () => {
  return (
    <Wrapper>
      <Header>Checkout Settings</Header>
      {/**
       * This banner was added for public app approval. We do not need it for Checkout360.
       * Commenting out incase we need to show this banner in future
       */}
      {/* {checkMagicConfigurationFlow() && <MagicXBanner isStoreSettingPage />} */}
      <Form />
    </Wrapper>
  );
};

export default MagicXStoreSettings;
