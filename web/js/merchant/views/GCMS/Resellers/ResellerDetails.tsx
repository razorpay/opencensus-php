import React from 'react';
import { Box, Button } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { NavLink, Navigate, Route, Routes, useParams, useNavigate } from 'react-router-dom';

import { ModeT } from 'common/services/mode';
import ResellerPrograms from 'merchant/views/GCMS/Resellers/ResellerPrograms';
import { RESELLER_PROGRAMS_PATH } from 'merchant/views/GCMS/shared/constants';
import { Wrapper } from 'merchant/views/MagicCheckout/ShippingSettings/styles';

import ResellerDetailsHeader from './ResellerDetailsHeader';

const ResellerDetails = ({ mode, merchantId }: { mode: ModeT; merchantId: string }) => {
  const { resellerId } = useParams<{ resellerId: string }>();
  const navigate = useNavigate();

  const handleOrderCreate = () => {
    navigate(`/gcms/orders/create/programs`, { state: { resellerId } });
  };

  return (
    <Wrapper>
      <div className="tabbed-container">
        <ResellerDetailsHeader merchantId={merchantId} resellerId={resellerId || ''} />
        <header>
          <NavLink to={RESELLER_PROGRAMS_PATH}>Programs</NavLink>
          <Box position="absolute" right="0px" top="0px" padding={['spacing.1', 'spacing.4']}>
            <Button variant="primary" onClick={handleOrderCreate}>
              Create Order
            </Button>
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
