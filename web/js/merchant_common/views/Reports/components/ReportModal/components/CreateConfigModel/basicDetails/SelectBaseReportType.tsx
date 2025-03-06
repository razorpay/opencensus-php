import React from 'react';
import {
  ActionList,
  ActionListItem,
  Dropdown,
  DropdownOverlay,
  SelectInput,
} from '@razorpay/blade/components';
import { useCreateConfigModal } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/store/createConfigModalStore';
import { zIndicesMap } from 'common/constant';

export const SelectBaseReportType = (): JSX.Element => {
  const standardReportName = useCreateConfigModal((state) => state.standardReportName);
  const updateStandardReportName = useCreateConfigModal((state) => state.updateStandardReportName);
  const setIsReportTypeChanged = useCreateConfigModal((state) => state.setIsReportTypeChanged);
  const standardConfigs = useCreateConfigModal((state) => state.standardConfigs);
  const setSelectedConfigId = useCreateConfigModal((state) => state.setSelectedConfigId);

  const errorInSections = useCreateConfigModal((state) => state.errorInSections);
  return (
    <Dropdown selectionType="single">
      <SelectInput
        accessibilityLabel="Select Base Report Type"
        label="Select Base Report Type"
        placeholder="Choose a Type"
        helpText="Select from dropdown"
        necessityIndicator="required"
        onChange={({ values }) => {
          setIsReportTypeChanged(true);
          updateStandardReportName(values[0]);
        }}
        validationState={errorInSections['reportType'] ? 'error' : 'none'}
        errorText={errorInSections['reportType'] ?? ''}
        value={standardReportName}
      />

      <DropdownOverlay zIndex={zIndicesMap.dropdownOverlay}>
        <ActionList>
          {standardConfigs.map((option) => (
            <ActionListItem
              key={option.id}
              title={option.name}
              value={option.name}
              onClick={() => setSelectedConfigId(option.id)}
            />
          ))}
        </ActionList>
      </DropdownOverlay>
    </Dropdown>
  );
};
