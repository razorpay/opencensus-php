import React from 'react';
import { Box, TextInput } from '@razorpay/blade/components';
import { useCreateConfigModal } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/store/createConfigModalStore';

export const ReportNameInput = (): JSX.Element => {
  const reportName = useCreateConfigModal((state) => state.reportName);
  const updateReportName = useCreateConfigModal((state) => state.updateReportName);
  const errorInSections = useCreateConfigModal((state) => state.errorInSections);

  return (
    <Box>
      <TextInput
        label="Report Name"
        placeholder="Type name of your report here"
        helpText="Enter a report name."
        necessityIndicator="required"
        value={reportName}
        onChange={({ value }) => {
          updateReportName(value ?? '');
        }}
        validationState={errorInSections['reportName'] ? 'error' : 'none'}
        errorText={errorInSections['reportName'] ?? ''}
      />
    </Box>
  );
};
