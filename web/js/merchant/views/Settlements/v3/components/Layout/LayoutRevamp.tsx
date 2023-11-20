import React from 'react';
import { withRouter } from 'common/deprecated/withRouter';
import { Box } from '@razorpay/blade/components';

import { LayoutPropsInterface } from 'merchant/views/Settlements/v3/typings';
import { StyledGoBackBtn } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/styled';
import GoBack from 'merchant/views/Transactions/v2/common/components/GoBack';

const Layout = ({ children, history, location }: LayoutPropsInterface): JSX.Element => {
  const { state } = location ?? {};
  const { prevPath = '' } = state ?? {};

  const handleGoBack = (): void => {
    if (prevPath) {
      history.goBack();
    }
    history.push('/settlements');
  };

  return (
    <div className="tabbed-container" data-testid="settlement-details">
      <StyledGoBackBtn>
        <GoBack onClickCb={handleGoBack} />
      </StyledGoBackBtn>
      <Box display="flex" flexDirection="column" gap={{ base: 'spacing.4', m: 'spacing.5' }}>
        {children}
      </Box>
    </div>
  );
};

export default withRouter(Layout);
