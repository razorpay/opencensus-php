import React from 'react';
import {
  ActionList,
  ActionListItem,
  AutoComplete,
  Box,
  Dropdown,
  DropdownOverlay,
} from '@razorpay/blade/components';
import { ColumnContainer } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/utils/ColumnContainer';
import { useCreateConfigModal } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/store/createConfigModalStore';
import { headerTitles } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/utils/constants';

export const ColumnSelection = (): JSX.Element => {
  const availableColumns = useCreateConfigModal((state) => state.availableColumns);
  const setSelectedColumns = useCreateConfigModal((state) => state.setSelectedColumns);
  const selectedColumns = useCreateConfigModal((state) => state.selectedColumns);

  return (
    <>
      <ColumnContainer title={headerTitles.availableColumns}>
        <Box>
          {Object.keys(availableColumns?.fields || {}).map((key) => (
            <Dropdown key={key} selectionType="multiple" marginTop="spacing.4">
              <AutoComplete
                name={key}
                size="medium"
                label={key}
                placeholder={`Select Columns for ${key}`}
                onChange={({ name, values }) => {
                  setSelectedColumns(name as string, values);
                }}
                value={selectedColumns[key] || []}
              />
              <DropdownOverlay>
                <ActionList isVirtualized>
                  {availableColumns.fields[key].map((value) => (
                    <ActionListItem key={value} title={value} value={value} />
                  ))}
                </ActionList>
              </DropdownOverlay>
            </Dropdown>
          ))}
        </Box>
      </ColumnContainer>
    </>
  );
};
