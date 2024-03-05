import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';
// import { Provider } from 'react-redux';
// import store from '../Store';

const Wrapper = ({ children }: { children: React.ReactNode }): JSX.Element => {
  return (
    <>
      {/* TODO: uncomment Provider after migrating all Transactions v2 tabs */}
      {/* <Provider store={store}> */}
      {/* TODO: this should be exposed from shell as part of common wrapper */}
      <BladeProvider themeTokens={paymentTheme}>{children}</BladeProvider>
      {/* </Provider> */}
    </>
  );
};

export default Wrapper;
