import React from 'react';
import SSOHome from 'merchant/views/MagicCheckout/Settings/containers/SSO/components/Home';
import { SSOProvider } from 'merchant/views/MagicCheckout/Settings/containers/SSO/context/SSOProvider';
import { ToastContainer } from '@razorpay/blade/components';

const SSO = () => {
  return (
    <SSOProvider>
      <SSOHome />
      <ToastContainer />
    </SSOProvider>
  );
};

export default SSO;
