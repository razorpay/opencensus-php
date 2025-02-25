import React from 'react';
import CreatedOn from 'apps/self-serve/src/App/Transactions/v2/common/components/CreatedOn';
import { render } from 'apps/self-serve/src/services/test/test-utils';
import { useMobile } from '@libs/shared-utils';

export const useMobileMock = {
  useMobile,
};

export const renderApp = ({ created_at }) => render(<CreatedOn created_at={created_at} />);
