import NACHFormEkyc from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/MerchantAdditionalDetails/NACHFormEkyc/NACHFormEkyc';
import { render, screen, userEvent } from 'apps/pos/src/services/test/test-utils';
import React from 'react';
import { NachFormKeyNames } from '../NACHFormEkyc';

const nachProps = {
  nachForm: {
    [NachFormKeyNames.NACH_FORM_DOCUMENT_FIELD]: [
      {
        fileStoreId: 'sample',
        name: 'sampleFile',
        size: 1234,
      },
    ],
    [NachFormKeyNames.NACH_FORM_COMMENTS_FIELD]: 'Sample comment',
  },
  onNachTextAreaChange: jest.fn(),
  onNachFileUploadChange: jest.fn(),
  onNachSubmitClick: jest.fn(),
  onNachSkipClick: jest.fn(),
  isFormDisabled: false,
  isModularLoading: false,
  isUpdateModularLoading: false,
};

describe('<NACHFormEkyc />', () => {
  test('should render NACH form component for EKYC', () => {
    render(<NACHFormEkyc {...nachProps} />);

    expect(screen.getByRole('heading', { name: /Upload NACH Form/i })).toBeInTheDocument();
    expect(screen.getByRole('textbox')).toBeInTheDocument();
    expect(screen.getByRole('textbox')).toHaveValue('Sample comment');
    expect(screen.getByRole('button', { name: /save & continue/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /skip & add later/i })).toBeInTheDocument();
  });

  test('should call onNachTextAreaChange when typing in the comments field', async () => {
    const mockOnNachTextAreaChange = jest.fn();
    nachProps['onNachTextAreaChange'] = mockOnNachTextAreaChange;

    render(<NACHFormEkyc {...nachProps} />);

    const textBox = screen.getByRole('textbox');
    await userEvent.type(textBox, 'abcd');

    expect(mockOnNachTextAreaChange).toHaveBeenCalledTimes(4);
  });

  test('should call onNachSubmitClick when "Save & Continue" button is clicked', async () => {
    const mockOnNachSubmitClick = jest.fn();
    nachProps['onNachSubmitClick'] = mockOnNachSubmitClick;

    render(<NACHFormEkyc {...nachProps} />);

    const saveButton = screen.getByRole('button', { name: /Save & Continue/i });
    await userEvent.click(saveButton);

    expect(mockOnNachSubmitClick).toHaveBeenCalledTimes(1);
  });
});
