import React, { useEffect, useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import { connect } from 'react-redux';
import { Navigate, Route, Routes, useLocation } from 'react-router-dom';

import Wrapper from 'merchant/views/GCMS/shared/Wrapper';
import { GCMSSession } from 'merchant/views/GCMS/shared/context';
import { RouteGuard } from 'merchant_common/components/RouteGuard';

import GCMSOrdersCart from './OrderCart';
import GCMSOrdersCreatePrograms from './OrderCreatePrograms';
import { OrderSessionContext } from './context';
import { orderCreate } from './queries';

const OrderCreate = ({ mode, merchantId }: GCMSSession) => {
  const { state } = useLocation();
  const resellerId = state?.resellerId;
  const [orderId, setOrderId] = useState<string>('');
  const { mutate: orderCreateMutation } = useMutation({
    mutationFn: orderCreate,
    onSuccess: (data) => {
      setOrderId(data?.id);
    },
  });

  useEffect(() => {
    // Create / Update a draft order for a new reseller
    if (resellerId) {
      orderCreateMutation({ resellerId, merchantId, mode });
    }
  }, [resellerId]);

  const hasResellerId = () => {
    return !!resellerId;
  };

  return (
    <Wrapper>
      <OrderSessionContext.Provider value={{ orderId, setOrderId, resellerId }}>
        <Routes>
          <Route
            path="programs/*"
            element={
              /* @ts-expect-error withRouterProps-check */
              <RouteGuard additionalCondition={() => hasResellerId()}>
                <GCMSOrdersCreatePrograms />
              </RouteGuard>
            }
          />
          <Route
            path="cart/*"
            element={
              /* @ts-expect-error withRouterProps-check */
              <RouteGuard additionalCondition={() => hasResellerId()}>
                <GCMSOrdersCart />
              </RouteGuard>
            }
          />
          <Route
            path="*"
            element={<Navigate to="/gcms/orders/create/programs" state={resellerId} replace />}
          />
        </Routes>
      </OrderSessionContext.Provider>
    </Wrapper>
  );
};

export default connect((state) => ({
  mode: state.session?.mode,
  merchantId: state.session?.user?.current,
}))(OrderCreate);
