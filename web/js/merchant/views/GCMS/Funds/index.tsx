import React from 'react';
import { NavLink, Route, Routes, Navigate } from 'react-router-dom';

import Wrapper from 'merchant/views/GCMS/shared/Wrapper';
import { ShowWhen } from 'merchant_common/components/RouteGuard';

import BrandAccount from './BrandAccount';
import ResellerAccounts from './ResellerAccounts';
import { fundsPaths } from './constants';
import PageLayout from 'merchant/views/GCMS/shared/PageLayout';

const Funds = (): JSX.Element => {
  return (
    <Wrapper>
      <PageLayout title="Funds" subtitle="Overview of the brand and reseller funds.">
        <div className="tabbed-container">
          <header>
            <ShowWhen
              additionalCondition={(user) =>
                user.isIssuingGcmsEnabled && user.isIssuingDashboardEnabled
              }
            >
              <NavLink to={fundsPaths.brandAccount}>Brand Account</NavLink>
            </ShowWhen>
            <ShowWhen
              additionalCondition={(user) =>
                user.isIssuingGcmsEnabled && user.isIssuingDashboardEnabled
              }
            >
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
      </PageLayout>
    </Wrapper>
  );
};

export default Funds;
