import React, { useEffect, useState } from 'react';
import {
  Box,
  Button,
  ChevronLeftIcon,
  IconButton,
  Link,
  ShoppingCartIcon,
} from '@razorpay/blade/components';
import { useMutation, useQuery } from '@tanstack/react-query';
import { connect } from 'react-redux';
import {
  NavLink,
  Navigate,
  Route,
  Routes,
  useParams,
  useNavigate,
  useLocation,
} from 'react-router-dom';

import { ModeT } from 'common/services/mode';
import { uniqueArray } from 'common/utils/rzp-utils';
import { fetchOrderItems, orderCreate } from 'merchant/views/GCMS/Orders/queries';
import { OrderItem } from 'merchant/views/GCMS/Orders/types';
import ResellerPrograms from 'merchant/views/GCMS/Resellers/ResellerPrograms';
import Wrapper from 'merchant/views/GCMS/shared/Wrapper';
import { RESELLER_PROGRAMS_PATH } from 'merchant/views/GCMS/shared/constants';
import { ListApiResponse } from 'merchant/views/Wallet/types';

import ResellerDetailsHeader from './ResellerDetailsHeader';
import { ShoppingCartRedDot } from './styled';

const ResellerDetails = ({ mode, merchantId }: { mode: ModeT; merchantId: string }) => {
  const [orderId, setOrderId] = useState<string>('');
  const { resellerId } = useParams<{ resellerId: string }>();
  const navigate = useNavigate();
  const location = useLocation();

  const handleOrderCreate = () => {
    navigate(`/gcms/orders/create/programs`, { state: { resellerId } });
  };

  const { mutate: orderCreateMutation } = useMutation({
    mutationFn: orderCreate,
    onSuccess: (data) => {
      setOrderId(data?.id);
    },
  });

  const { data: orderItems } = useQuery<ListApiResponse<OrderItem>, Error>({
    /* @ts-expect-error no-overload */
    queryKey: ['wallet:order:items', merchantId, orderId, mode],
    queryFn: () => fetchOrderItems({ mode, merchantId, orderId }),
    enabled: !!orderId,
  });

  useEffect(() => {
    // Create / Update a draft order for a new reseller
    if (resellerId) {
      orderCreateMutation({ resellerId, merchantId, mode });
    }
  }, [resellerId]);

  const orderItemsByProgram = Array.isArray(orderItems)
    ? uniqueArray(orderItems.map((item) => item.program_id))?.length
    : 0;

  const handleGoBack = () => {
    const { prevPath = '' } = location?.state ?? {};

    if (prevPath) {
      navigate(-1);
    }
    navigate('/gcms/resellers');
  };

  return (
    <Wrapper>
      <Box>
        <Box
          padding={['spacing.6', 'spacing.6', 'spacing.0']}
          display="flex"
          flexDirection="row"
          justifyContent="space-between"
          alignItems="center"
        >
          <Box>
            <Link
              variant="button"
              icon={ChevronLeftIcon}
              iconPosition="left"
              onClick={handleGoBack}
            >
              Go back
            </Link>
          </Box>
          <Button variant="primary" onClick={handleOrderCreate}>
            Create Order
          </Button>
        </Box>
      </Box>
      <div className="tabbed-container">
        <ResellerDetailsHeader merchantId={merchantId} resellerId={resellerId || ''} />
        <header>
          <NavLink to={RESELLER_PROGRAMS_PATH}>Programs</NavLink>
          <Box position="absolute" right="0px" top="0px" padding={['spacing.5', 'spacing.6']}>
            <IconButton
              size="large"
              icon={ShoppingCartIcon}
              accessibilityLabel="Cart"
              onClick={() => {
                if (orderItemsByProgram > 0) {
                  navigate(`/gcms/orders/create/cart`, { state: { resellerId } });
                }
              }}
            />
            {orderItemsByProgram > 0 && (
              <Box position="absolute" top="spacing.4" right="spacing.5">
                <ShoppingCartRedDot />
              </Box>
            )}
          </Box>
        </header>
        <div className="content">
          <Routes>
            <Route path="/" element={<Navigate to={RESELLER_PROGRAMS_PATH} replace />} />
            <Route path={RESELLER_PROGRAMS_PATH} element={<ResellerPrograms mode={mode} />} />
          </Routes>
        </div>
      </div>
    </Wrapper>
  );
};

const mapStateToProps = (state) => ({
  mode: state.session.mode,
  merchantId: state.session.user.current,
});

export default connect(mapStateToProps)(ResellerDetails);
