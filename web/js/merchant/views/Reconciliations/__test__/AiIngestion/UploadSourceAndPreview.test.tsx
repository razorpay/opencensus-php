import React from 'react';
import userEvent from '@testing-library/user-event';
import { render, screen } from 'test-utils';

import UploadSourceAndPreview from 'merchant/views/Reconciliations/AiIngestion/UploadSourceAndPreview';
import { showNotification } from 'merchant_common/reducers/notifications';

jest.mock('merchant_common/reducers/notifications', () => ({
  showNotification: jest.fn(),
}));

const defaultProps = {
  sourcesData: [
    {
      id: 'source-1',
      name: 'Source A',
      fileUploadUrl: null,
      fileUploadPath: null,
      fileData: null,
      isUploaded: false,
    },
  ],
  processName: 'Test Process',
  isEditingProcessName: false,
  setSourcesData: jest.fn(),
  setAiIngestionStages: jest.fn(),
  setIsEditingProcessName: jest.fn(),
  showNotification,
};

const renderComponent = (props = defaultProps) => {
  return render(<UploadSourceAndPreview {...props} />);
};

describe('UploadSourceAndPreview Component', () => {
  test('renders without errors', () => {
    expect(() => renderComponent()).not.toThrow();
  });

  test('displays initial source file upload section', () => {
    renderComponent();
    expect(screen.getByText(/Upload your source file/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/Add source name/i)).toHaveValue('Source A');
  });
});
