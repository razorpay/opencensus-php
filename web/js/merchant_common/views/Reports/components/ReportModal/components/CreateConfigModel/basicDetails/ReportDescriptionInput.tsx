import React from 'react';
import { TextArea } from '@razorpay/blade/components';
import { useCreateConfigModal } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/store/createConfigModalStore';

export const ReportDescriptionInput = (): JSX.Element => {
  const reportDescription = useCreateConfigModal((state) => state.reportDescription);
  const updateReportDescription = useCreateConfigModal((state) => state.updateReportDescription);
  const errorInSections = useCreateConfigModal((state) => state.errorInSections);

  return (
    <TextArea
      label="Report Description"
      placeholder="Enter the report description"
      helpText="Enter a description for this report."
      necessityIndicator="required"
      value={reportDescription}
      onChange={({ value }) => {
        updateReportDescription(value ?? '');
      }}
      validationState={errorInSections['reportDescription'] ? 'error' : 'none'}
      errorText={errorInSections['reportDescription'] ?? ''}
    />
  );
};
