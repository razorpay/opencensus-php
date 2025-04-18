import React from 'react';
import {
  Box,
  Heading,
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableBody,
  TableRow,
  TableEditableDropdownCell,
  Button,
  ActionListItem,
  ActionList,
  DropdownOverlay,
  AutoComplete,
  TableCell,
  IconButton,
  TrashIcon,
} from '@razorpay/blade/components';

import type { ColumnMappingTableProps } from 'merchant/views/Reconciliations/AiIngestion/types';

const ColumnMappingTable: React.FC<ColumnMappingTableProps> = ({
  title,
  tableData,
  columnConfig,
  sourceColumnList,
  tableName,
  onAddRow,
  onDeleteRow,
  onChangeColumn,
}) => {
  return (
    <Box display="flex" flexDirection="column" gap="spacing.2">
      <Box display="flex" justifyContent="space-between" alignItems="center">
        <Heading>{title}</Heading>
        <Button onClick={() => onAddRow({ tableName })}>Add Row</Button>
      </Box>
      <Table
        data={{ nodes: tableData }}
        showBorderedCells={true}
        display="grid"
        gridTemplateColumns={`repeat(${columnConfig.length},minmax(300px,1fr)) 0.1fr`}
      >
        {(data) => (
          <>
            <TableHeader>
              <TableHeaderRow>
                {columnConfig.map((column) => (
                  <TableHeaderCell
                    key={`${column.merchant_source_id}_${column.merchant_source_name}`}
                  >
                    {column.merchant_source_name}
                  </TableHeaderCell>
                ))}
                <TableHeaderCell>{''}</TableHeaderCell>
              </TableHeaderRow>
            </TableHeader>
            <TableBody>
              {data.map((tableItem, rowIndex) => (
                <TableRow key={rowIndex} item={tableItem}>
                  {columnConfig.map((column) => (
                    <TableEditableDropdownCell key={column.merchant_source_id}>
                      <AutoComplete
                        accessibilityLabel=""
                        inputValue={tableItem[column.merchant_source_id]?.name || ''}
                        onChange={({ values }) => {
                          if (values.length) {
                            onChangeColumn({
                              tableName,
                              sourceId: column.merchant_source_id,
                              index: rowIndex,
                              values: values[0],
                            });
                          }
                        }}
                      />
                      <DropdownOverlay>
                        <ActionList>
                          {sourceColumnList[column.merchant_source_id][
                            `${tableName.slice(0, -5)}_cols`
                          ]?.map((source, idx) => (
                            <ActionListItem key={idx} title={source.name} value={source} />
                          ))}
                        </ActionList>
                      </DropdownOverlay>
                    </TableEditableDropdownCell>
                  ))}
                  <TableCell>
                    <Box display="flex" justifyContent="center" alignItems="center" width="100%">
                      <IconButton
                        accessibilityLabel="delete"
                        icon={TrashIcon}
                        onClick={() => onDeleteRow({ tableName, rowIndex })}
                      />
                    </Box>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </>
        )}
      </Table>
    </Box>
  );
};

export default ColumnMappingTable;
