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
    <ColumnContainer title="SELECTED COLUMNS">
      <Box marginTop="spacing.2">
        {hasSelectedColumns ? (
          <SortViaDrag<TableNode<string>>
            items={displayColumns}
            onChange={setRearrangeColumns}
            renderCustomComponent={(item) => {
              return (
                <Box display="flex" justifyContent="space-between" zIndex={zIndicesMap.drawer}>
                  <Box display="flex" gap="spacing.3" padding="spacing.3">
                    <Box maxWidth="spacing.4">
                      <DragHandle>
                        <ListIcon size="small" color="interactive.icon.gray.muted" />
                      </DragHandle>
                    </Box>
                    <Box draggable>
                      <Text testID={item.value} size="small" wordBreak="break-all">
                        {item.value}
                      </Text>
                    </Box>
                  </Box>
                  <Box maxWidth="spacing.3" padding="spacing.4">
                    <IconButton
                      size="small"
                      icon={CloseIcon}
                      accessibilityLabel={item.value}
                      onClick={() => deleteSelectedColumn(item.id, item.value)}
                    />
                  </Box>
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
      </Box>
    </ColumnContainer>
  );
};
