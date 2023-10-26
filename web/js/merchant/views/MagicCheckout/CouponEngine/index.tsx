import React from 'react';
import { Routes, Route } from 'react-router-dom';
import lazy from 'merchant/routes/LazyLoader';

// ui components
import { MainPage } from 'merchant/views/MagicCheckout/CouponEngine/pages';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

// context import
import { ModalProvider } from 'merchant/views/MagicCheckout/CouponEngine/context';

const CreateCouponForm = lazy(
  () =>
    import(
      /* webpackChunkName: 'MagicCouponEngineCreateCouponForm' */ 'merchant/views/MagicCheckout/CouponEngine/pages/CreateCouponForm'
    ),
);

const CouponEngine: React.FC = () => {
  return (
    <ModalProvider>
      <Routes>
        <Route
          index
          path="*"
          element={
            <SuspenseWithLoader type="center">
              <MainPage />
            </SuspenseWithLoader>
          }
        />

        {/* Route for create component */}
        <Route
          path="create/:couponName/"
          element={
            <SuspenseWithLoader type="center">
              <CreateCouponForm flow="created" />
            </SuspenseWithLoader>
          }
        />

        {/* Route for edit component */}
        <Route
          path="coupons/edit/:couponName/:code/"
          element={
            <SuspenseWithLoader type="center">
              <CreateCouponForm flow="edit" />
            </SuspenseWithLoader>
          }
        />

        {/* Route for duplicate component */}
        <Route
          path="coupons/duplicate/:couponName/:code/"
          element={
            <SuspenseWithLoader type="center">
              <CreateCouponForm flow="duplicate" />
            </SuspenseWithLoader>
          }
        />
      </Routes>
    </ModalProvider>
  );
};

export default CouponEngine;
