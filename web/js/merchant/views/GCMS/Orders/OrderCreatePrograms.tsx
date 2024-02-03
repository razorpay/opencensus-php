import React, { Suspense, useContext, useState } from 'react';
import { Box, ChevronLeftIcon, Link, Title } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { useLocation, useNavigate } from 'react-router-dom';

import Loader from 'common/ui/Loader';
import Spinner from 'common/ui/Spinner';
import { uniqueArray } from 'common/utils/rzp-utils';
import EmptyList from 'merchant/components/EmptyList';
import lazy from 'merchant/routes/LazyLoader';
import { OrderItem, OrderItemDenomination } from 'merchant/views/GCMS/Orders/types';
import ProgramsListItem from 'merchant/views/GCMS/Programs/ProgramsListItem';
import { fetchProgramsByResellerId } from 'merchant/views/GCMS/Programs/queries';
import { Program as ProgramType, SKU } from 'merchant/views/GCMS/Programs/types';
import { GCMSSession, SessionContext } from 'merchant/views/GCMS/shared/context';
import { ListApiResponse } from 'merchant/views/Wallet/types';

import OrderFooterSection from './OrderFooterSection';
import { GCMSOrderSession, OrderSessionContext } from './context';
import { fetchOrderItems } from './queries';

const OrderCreateProgramDenominationsModal = lazy(
  () =>
    import(
      /* webpackChunkName: "EngageHQGCMS" */ 'merchant/views/GCMS/Orders/OrderCreateProgramDenominationsModal'
    ),
);

const OrderCreatePrograms = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const [isProgramDenominationModalOpen, setIsProgramDenominationModalOpen] =
    useState<boolean>(false);
  const [selectedSku, updateSelectedSku] = useState<SKU>();
  const [selectedOrderItems, updateSelectedOrderItems] = useState<OrderItemDenomination[]>([]);

  const { mode, merchantId } = useContext<GCMSSession>(SessionContext);
  const { orderId, resellerId } = useContext<GCMSOrderSession>(OrderSessionContext);

  const { isLoading, data: skus } = useQuery<ListApiResponse<ProgramType>, Error>({
    queryKey: ['wallet:programs', merchantId, resellerId, mode],
    queryFn: () => fetchProgramsByResellerId({ mode, merchantId, resellerId }),
  });
  const { data: orderItems } = useQuery<ListApiResponse<OrderItem>, Error>({
    /* @ts-expect-error no-overload */
    queryKey: ['wallet:order:items', merchantId, orderId, mode],
    queryFn: () => fetchOrderItems({ mode, merchantId, orderId }),
    enabled: !!orderId,
  });

  const handleGoBack = () => {
    const { prevPath = '' } = location?.state ?? {};

    if (prevPath) {
      return navigate(-1);
    }
    return navigate('/gcms/resellers/');
  };

  const handleProgramDenominationsModalOpen = (sku): void => {
    const items =
      Array.isArray(orderItems) && orderItems.length > 0
        ? orderItems.map((item) => (item.sku_id === sku.id ? item : false)).filter(Boolean)
        : [];
    updateSelectedSku(sku);
    updateSelectedOrderItems(items);
    setIsProgramDenominationModalOpen(true);
  };

  const onClickViewCart = (): void => {
    navigate('/gcms/orders/create/cart', { state: { resellerId, orderId } });
  };

  const orderItemsByProgram = Array.isArray(orderItems)
    ? uniqueArray(orderItems.map((item) => item.program_id))?.length
    : 0;

  return (
    <Box>
      <div className="tabbed-container">
        <Box padding={['spacing.4', 'spacing.0']}>
          <Link variant="button" icon={ChevronLeftIcon} iconPosition="left" onClick={handleGoBack}>
            Go back
          </Link>
        </Box>
        <Box>
          <Title color="surface.text.subtle.lowContrast">Create Order</Title>
        </Box>
        <div className="content">
          {isLoading ? (
            <Box display="flex" flex={1} alignItems="center" justifyContent="center">
              <Spinner center={undefined} />
            </Box>
          ) : (
            <Box
              maxWidth={{
                l: '1200px',
                m: '100%',
                s: '100%',
              }}
            >
              <Box
                marginTop="spacing.4"
                padding="spacing.4"
                display="flex"
                flex={1}
                flexDirection="row"
                flexWrap="wrap"
              >
                {/* @ts-expect-error array-undefined-check */}
                {Array.isArray(skus?.items) && skus.items.length > 0 ? (
                  skus?.items.map((sku) => (
                    <ProgramsListItem
                      key={sku.id}
                      program={sku}
                      onClick={() => handleProgramDenominationsModalOpen(sku)}
                    />
                  ))
                ) : (
                  <Box width="100%" height="100%">
                    <EmptyList
                      description={
                        <React.Fragment>
                          <div>There are no programs yet!!</div>
                          <div>Start creating new programs now.</div>
                        </React.Fragment>
                      }
                    />
                  </Box>
                )}
              </Box>
              {selectedSku && (
                <Suspense fallback={<Loader />}>
                  <OrderCreateProgramDenominationsModal
                    isOpen={isProgramDenominationModalOpen}
                    setIsOpen={setIsProgramDenominationModalOpen}
                    sku={selectedSku}
                    orderItems={selectedOrderItems}
                  />
                </Suspense>
              )}
            </Box>
          )}
          {!!orderItemsByProgram && (
            <OrderFooterSection items={orderItemsByProgram} onClickViewCart={onClickViewCart} />
          )}
        </div>
      </div>
    </Box>
  );
};

export default OrderCreatePrograms;
