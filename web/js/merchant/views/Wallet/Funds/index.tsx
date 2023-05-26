import React from 'react';
import { NavLink, Redirect, Route, Switch } from 'react-router-dom';

import BankDetails from 'merchant/views/Wallet/Funds/BankDetails';
import Transactions from 'merchant/views/Wallet/Funds/Transactions';
import { Box } from '@razorpay/blade/components';

export const Funds = (): JSX.Element => {
  return (
    <Box paddingTop="spacing.6">
      <header>
        <NavLink to="/wallet/funds/transactions">Transactions</NavLink>
        <NavLink to="/wallet/funds/bank_details">Bank Details</NavLink>
      </header>
      <Switch>
        <Route path="/wallet/funds/transactions" component={Transactions} />
        <Route path="/wallet/funds/bank_details" component={BankDetails} />

        <Redirect exact from="/wallet/funds/" to="/wallet/funds/transactions" />
      </Switch>
    </Box>
  );
};

export default Funds;
