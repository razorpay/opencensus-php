import React from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render } from 'test-utils';

import SourceMapping from 'merchant/views/Reconciliations/AiIngestion/SourceMapping';

const mockShowNotification = jest.fn();

const defaultProps = {
  sourcesData: [],
  processName: 'test-process',
  mlConfigIds: {
    sessionId: '',
    auditLogId: '',
  },
  isGoBackClicked: false,
  setMlConfigIds: jest.fn(),
  setAiIngestionStages: jest.fn(),
  showNotification: mockShowNotification,
};

const renderComponent = (props = defaultProps) => {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  });
  return render(
    <QueryClientProvider client={queryClient}>
      <SourceMapping {...props} />
    </QueryClientProvider>,
  );
};

describe('SourceMapping Component', () => {
  test('renders without errors', () => {
    expect(() => renderComponent()).not.toThrow();
  });
});
