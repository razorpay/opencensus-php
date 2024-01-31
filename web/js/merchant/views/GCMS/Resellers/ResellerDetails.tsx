import React from 'react';
import { NavLink, Navigate, Route, Routes } from 'react-router-dom';
import { connect } from 'react-redux';
import { RESELLER_PROGRAMS_PATH } from 'merchant/views/GCMS/shared/constants';
import { Wrapper } from 'merchant/views/MagicCheckout/ShippingSettings/styles';
import ResellerPrograms from 'merchant/views/GCMS/Resellers/ResellerPrograms';
import { ModeT } from 'common/services/mode';
import ResellerDetailsHeader from './ResellerDetailsHeader';

const ResellerDetails = ({ mode, merchantId }: { mode: ModeT; merchantId: string }) => {
  return (
    <Wrapper>
      <div className="tabbed-container">
        <ResellerDetailsHeader merchantId={merchantId} />
        <header>
          <NavLink to={RESELLER_PROGRAMS_PATH}>Programs</NavLink>
        </header>
        <div className="content">
          <Routes>
            <Route path={'/'} element={<Navigate to={RESELLER_PROGRAMS_PATH} replace />} />
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
