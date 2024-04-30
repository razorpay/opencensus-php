import React from 'react';

import Form from 'merchant/views/MagicCheckout/MagicXStoreSettings/components/Form';
import { Header, Wrapper } from 'merchant/views/MagicCheckout/MagicXStoreSettings/styled';

const MagicXStoreSettings: React.FC = () => {
  return (
    <Wrapper>
      <Header>Store settings</Header>
      <Form />
    </Wrapper>
  );
};

export default MagicXStoreSettings;
