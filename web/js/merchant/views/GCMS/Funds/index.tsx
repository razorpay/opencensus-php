import React from 'react';
import { Box, Title } from '@razorpay/blade/components';
import { NavLink, Route, Routes, Navigate } from 'react-router-dom';

import Wrapper from 'merchant/views/GCMS/shared/Wrapper';
import { ShowWhen } from 'merchant_common/components/RouteGuard';

import BrandAccount from './BrandAccount';
import ResellerAccounts from './ResellerAccounts';
import { fundsPaths } from './constants';

const Funds = () => {
  return (
    <Wrapper>
      <div className="tabbed-container">
        <Box marginBottom="spacing.4">
          <Title color="surface.text.subtle.lowContrast">Funds</Title>
        </Box>
        <header>
          <ShowWhen additionalCondition={(user) => user.isIssuingBulkUploadEnabled}>
            <NavLink to={fundsPaths.brandAccount}>Brand Account</NavLink>
          </ShowWhen>
          <ShowWhen additionalCondition={(user) => user.isIssuingDashboardEnabled}>
            <NavLink to={fundsPaths.resellerAccounts}>Reseller Accounts</NavLink>
          </ShowWhen>
        </header>
      </div>
      <div className="content">
        <Routes>
          <Route path="/brand-account/*" element={<BrandAccount />} />
          <Route path="/reseller-account/*" element={<ResellerAccounts />} />
          <Route index element={<Navigate to="/gcms/funds/brand-account" replace />} />
        </Routes>
      </div>
    </Wrapper>
  );
};

export default Funds;
