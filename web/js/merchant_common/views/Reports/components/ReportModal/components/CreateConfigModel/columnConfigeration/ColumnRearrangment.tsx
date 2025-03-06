import React from 'react';
import {
  Box,
  Table,
  TableBody,
  TableCell,
  TableHeader,
  TableHeaderCell,
  TableHeaderRow,
  TableRow,
  Text,
  TextInput,
  IconButton,
  TrashIcon,
} from '@razorpay/blade/components';
import { useCreateConfigModal } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/store/createConfigModalStore';
import {
  formErrorMessages,
  tableColumnHeaders,
} from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/utils/constants';
import { ReportTableContainer } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/styled';
export const ColumnRearrangment = (): JSX.Element => {
  const displayColumns = useCreateConfigModal((state) => state.displayColumns);
  const renamedColumns = useCreateConfigModal((state) => state.renamedColumns);
  const setRenamedColumns = useCreateConfigModal((state) => state.setRenamedColumns);
  const deleteSelectedColumn = useCreateConfigModal((state) => state.deleteSelectedColumn);

  const handleRenameChange = (columnKey: string, updatedName: string) => {
    setRenamedColumns(columnKey, updatedName);
  };

  return (
    <Box backgroundColor="surface.background.gray.moderate" padding="10px">
      <ReportTableContainer>
        <Table
          data={{
            nodes: displayColumns,
          }}
          selectionType="none"
          rowDensity="normal"
          gridTemplateColumns="0.5fr 0.3fr 0.2fr"
        >
          {(tableData) => (
            <>
              <TableHeader>
                <TableHeaderRow>
                  {Object.values(tableColumnHeaders).map((header) => {
                    return (
                      <TableHeaderCell key={header}>
                        <Box width="100%">
                          <Text
                            textAlign={
                              header === tableColumnHeaders['removeColumn'] ? 'center' : 'left'
                            }
                            variant="body"
                            size="small"
                          >
                            {header}
                          </Text>
                        </Box>
                      </TableHeaderCell>
                    );
                  })}
                </TableHeaderRow>
              </TableHeader>

              <TableBody>
                {tableData.map((tableItem) => {
                  return (
                    <TableRow key={tableItem.id} item={tableItem}>
                      <TableCell>
                        <Box>
                          <Text testID={tableItem.value} size="medium">
                            {tableItem.value}
                          </Text>
                        </Box>
                      </TableCell>
                      <TableCell>
                        <Box marginTop="spacing.4">
                          <TextInput
                            label={''}
                            accessibilityLabel={renamedColumns[tableItem.id]}
                            value={renamedColumns[tableItem.id]}
                            onChange={({ value }) => {
                              handleRenameChange(tableItem.id, value as string);
                            }}
                            validationState={renamedColumns[tableItem.id] === '' ? 'error' : 'none'}
                            errorText={formErrorMessages['renamedColumnErrorText']}
                            maxCharacters={50}
                          />
                        </Box>
                      </TableCell>
                      <TableCell>
                        <Box
                          width="100%"
                          display="flex"
                          justifyContent="center"
                          alignItems="center"
                        >
                          <IconButton
                            accessibilityLabel={`deleteicon ${tableItem.id}`}
                            icon={TrashIcon}
                            size="medium"
                            onClick={() => deleteSelectedColumn(tableItem.id, tableItem.value)}
                          />
                        </Box>
                      </TableCell>
                    </TableRow>
                  );
                })}
              </TableBody>
            </>
          )}
        </Table>
      </ReportTableContainer>
    </Box>
  );
};
