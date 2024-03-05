import React from 'react';
import { Box } from '@razorpay/blade/components';
import { useLocation, useNavigate } from 'react-router-dom';

import { LayoutPropsInterface } from 'merchant/views/Settlements/v3/typings';
import { StyledGoBackBtn } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/styled';
import GoBack from 'merchant/views/Transactions/v2/common/components/GoBack';

const Layout = ({ children }: LayoutPropsInterface): JSX.Element => {
  const location = useLocation();
  const navigate = useNavigate();

  const { state } = location ?? {};
  const { prevPath = '' } = state ?? {};

  const handleGoBack = (): void => {
    if (prevPath) {
      navigate(-1);
    }
    navigate('/settlements');
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

export default Layout;
