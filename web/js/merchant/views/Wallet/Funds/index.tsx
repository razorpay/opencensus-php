import React from 'react';
import { NavLink, Navigate, Route, Routes } from 'react-router-dom';

import BankDetails from 'merchant/views/Wallet/Funds/BankDetails';
import Transactions from 'merchant/views/Wallet/Funds/Transactions';
import { Box } from '@razorpay/blade/components';
import { RouteGuard } from 'merchant/components/ShowWhen';

export const Funds = (): JSX.Element => {
  return (
    <Box paddingTop="spacing.6">
      <header>
        <NavLink to="/wallet/funds/transactions">Transactions</NavLink>
        <NavLink to="/wallet/funds/bank_details">Bank Details</NavLink>
      </header>
      <Routes>
        <Route
          path="transactions/*"
          element={
            <RouteGuard>
              <Transactions />
            </RouteGuard>
          }
        />
        <Route
          path="bank_details/*"
          element={
            <RouteGuard>
              <BankDetails />
            </RouteGuard>
          }
        />

        <Route path="*" element={<Navigate to="/wallet/funds/transactions" replace />} />
      </Routes>
    </Box>
  );
};

export default Funds;
