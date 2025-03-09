import React, { useEffect, useState } from 'react';
import {
  Drawer,
  DrawerHeader,
  DrawerBody,
  Table,
  TableHeaderCell,
  TableHeader,
  TableHeaderRow,
  TableBody,
  TableCell,
  TableRow,
  Box,
  Text,
} from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';

import { fetchingSplitScreenMatchingRecord } from 'merchant/views/Reconciliations/api';
import { RenderErrorLoadingOrChild } from 'merchant/views/Reconciliations/commonComponents';

import type {
  IData,
  SplitScreenDrawerProps,
} from 'merchant/views/Reconciliations/SplitScreen/types';

const SplitScreenDrawer: React.FC<SplitScreenDrawerProps> = ({
  isDrawerOpen,
  setIsDrawerOpen,
  recordId,
}) => {
  const [dataNode, setDataNode] = useState<IData[]>([]);

  const {
    data: matchingRecordData,
    isLoading: isLoadingForMatchingRecord,
    isError: isErrorForisLoadingForMatchingRecord,
    isSuccess: isSuccessForisLoadingForMatchingRecord,
  } = useQuery({
    queryKey: ['matchingrecord', recordId],
    queryFn: () => fetchingSplitScreenMatchingRecord({ recordId }),
    enabled: !!isDrawerOpen && !!recordId,
  });

  useEffect(() => {
    if (isSuccessForisLoadingForMatchingRecord && matchingRecordData?.data?.items?.length) {
      const matchingRecordSourceMap = matchingRecordData.data.items.reduce((map, item) => {
        map[item.merchant_source_id] = item.data || {};
        return map;
      }, {});

      const matchingRecordsTableData = matchingRecordData.data.cols.map((column) => {
        const row = {
          column,
          ...Object.entries(matchingRecordSourceMap).reduce((acc, [sourceId, sourceData]) => {
            acc[sourceId] = sourceData?.[column] || '';
            return acc;
          }, {}),
        };
        return row;
      });

      if (matchingRecordsTableData.length) {
        setDataNode(matchingRecordsTableData);
      }
    }
  }, [isSuccessForisLoadingForMatchingRecord]);

  return (
    <Drawer isOpen={isDrawerOpen} onDismiss={() => setIsDrawerOpen(false)} showOverlay={true}>
      <DrawerHeader title="Reconciled Data" />
      <DrawerBody>
        <RenderErrorLoadingOrChild
          isError={isErrorForisLoadingForMatchingRecord}
          isLoading={isLoadingForMatchingRecord}
        >
          <Box backgroundColor="surface.background.sea.intense">
            <Table
              data={{ nodes: dataNode }}
              gridTemplateColumns={`repeat(${
                matchingRecordData?.data?.items.length
                  ? matchingRecordData.data.items.length + 1
                  : 1
              },1fr)`}
              rowDensity="compact"
            >
              {(tableData) => (
                <>
                  <TableHeader>
                    <TableHeaderRow>
                      <TableHeaderCell>{''}</TableHeaderCell>
                      {matchingRecordData.data.items.map((item) => (
                        <TableHeaderCell key={item.merchant_source_name}>
                          {item.merchant_source_name}
                        </TableHeaderCell>
                      ))}
                    </TableHeaderRow>
                  </TableHeader>
                  <TableBody>
                    {tableData.map((tableItem, index) => (
                      <TableRow key={index} item={tableItem}>
                        <TableCell>
                          <Text>{tableItem?.column || ''}</Text>
                        </TableCell>
                        {matchingRecordData.data.items.map((item) => (
                          <TableCell key={item.merchant_source_id}>
                            <Text>{tableItem[item.merchant_source_id] || ''}</Text>
                          </TableCell>
                        ))}
                      </TableRow>
                    ))}
                  </TableBody>
                </>
              )}
            </Table>
          </Box>
        </RenderErrorLoadingOrChild>
      </DrawerBody>
    </Drawer>
  );
};

export default SplitScreenDrawer;
