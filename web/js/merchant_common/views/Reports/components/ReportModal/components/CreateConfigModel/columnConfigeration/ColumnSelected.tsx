import React from 'react';
import { Box, Text, CloseIcon, IconButton, ListIcon } from '@razorpay/blade/components';
import { ColumnContainer } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/utils/ColumnContainer';
import { useCreateConfigModal } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/store/createConfigModalStore';
import {
  SortViaDrag,
  DragHandle,
} from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/sortViaDrag/SortViaDrag';
import { TableNode } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/types';
import { zIndicesMap } from 'common/constant';

export const ColumnSelected = (): JSX.Element => {
  const selectedColumns = useCreateConfigModal((state) => state.selectedColumns);
  const deleteSelectedColumn = useCreateConfigModal((state) => state.deleteSelectedColumn);
  const displayColumns = useCreateConfigModal((state) => state.displayColumns);
  const setRearrangeColumns = useCreateConfigModal((state) => state.setRearrangeColumns);
  const hasSelectedColumns = Object.keys(selectedColumns).length > 0;

  return (
    <>
      <ColumnContainer title="SELECTED COLUMNS">
        {hasSelectedColumns ? (
          <SortViaDrag<TableNode<string>>
            items={displayColumns}
            onChange={setRearrangeColumns}
            renderCustomComponent={(item) => {
              return (
                <Box
                  padding="spacing.4"
                  display="flex"
                  alignItems="center"
                  justifyContent="space-between"
                  zIndex={zIndicesMap.drawer}
                >
                  <Box display="flex" alignItems="center">
                    <DragHandle>
                      <ListIcon
                        size="small"
                        marginRight="spacing.4"
                        color="interactive.icon.gray.muted"
                      />
                    </DragHandle>
                    <Box draggable>
                      <Text testID={item.value} size="small">
                        {item.value}
                      </Text>
                    </Box>
                  </Box>
                  <IconButton
                    size="small"
                    icon={CloseIcon}
                    accessibilityLabel={item.value}
                    onClick={() => deleteSelectedColumn(item.id, item.value)}
                  />
                </Box>
              );
            }}
          />
        ) : (
          <Box display="flex">
            <Text textAlign="center" marginTop="100px" color="surface.text.gray.muted" size="large">
              You can rearrange and delete specific columns here.
            </Text>
          </Box>
        )}
      </ColumnContainer>
    </>
  );
};
