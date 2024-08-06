import React from 'react';
import Form from 'merchant/views/MagicCheckout/MagicXStoreSettings/components/Form';
import { Header, Wrapper } from 'merchant/views/MagicCheckout/MagicXStoreSettings/styled';
import { checkMagicConfigurationFlow } from 'merchant/views/MagicCheckout/utils/Configuration';
import { MagicXBanner } from 'merchant/views/MagicCheckout/components/MagicXShopifyBanner';

const MagicXStoreSettings: React.FC = () => {
  return (
    <Wrapper>
      <Header>Store settings</Header>
      {checkMagicConfigurationFlow() && <MagicXBanner isStoreSettingPage />}
      <Form />
    </Wrapper>
  );
};

export default MagicXStoreSettings;
