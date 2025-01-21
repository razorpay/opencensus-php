import React from 'react';
import {
  useToast,
  Modal,
  ModalHeader,
  ModalBody,
  Alert,
  Box,
  Text,
  ModalFooter,
  Button,
} from '@razorpay/blade/components';
import {
  DndContext,
  closestCorners,
  DragEndEvent,
  UniqueIdentifier,
  useSensors,
  useSensor,
  PointerSensor,
  TouchSensor,
  KeyboardSensor,
  MouseSensor,
} from '@dnd-kit/core';
import {
  SortableContext,
  verticalListSortingStrategy,
  arrayMove,
  sortableKeyboardCoordinates,
} from '@dnd-kit/sortable';
import Draggable from './Draggable';

import { useBillsTableConfigStore } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/stores/billsTableConfigStore';
import {
  BILL_AMOUNT_COL_KEY,
  BILL_DATE_COL_KEY,
  BILL_ID_COL_KEY,
} from '@apps/digital-bills/src/utils/constants';

type EditColumnsModalProps = {
  isOpen: boolean;
  dismiss: () => void;
};

const EditColumnsModal = ({ isOpen, dismiss }: EditColumnsModalProps) => {
  const { editableColumns, sortColumns, toggleColumnSelection, setTableColumns } =
    useBillsTableConfigStore();
  const toast = useToast();
  const getColumnPosition = (id: UniqueIdentifier | undefined) =>
    editableColumns.findIndex((column) => column.id === id);

  const handleDragEnd = (event: DragEndEvent) => {
    const { active, over } = event;
    if (active.id === over?.id) return;
    const originalPos = getColumnPosition(active.id);
    const newPos = getColumnPosition(over?.id);
    const sortedColumns = arrayMove(editableColumns, originalPos, newPos);
    sortColumns(sortedColumns);
  };

  const handleSave = () => {
    const filterSortedTableColumns = editableColumns
      .filter((col) => col.isSelected)
      .map((col) => ({ key: col.id, name: col.name }));
    setTableColumns(filterSortedTableColumns);
    dismiss();
  };

  const sensors = useSensors(
    useSensor(PointerSensor),
    useSensor(MouseSensor),
    useSensor(TouchSensor),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    }),
  );
  return (
    <Modal isOpen={isOpen} onDismiss={dismiss} size="medium">
      <ModalHeader title="Edit Columns" />
      <ModalBody>
        <Box paddingX="spacing.9">
          <Alert
            color="information"
            description="Select and reorganise columns that you want to view on the dashboard."
            emphasis="subtle"
            isDismissible={false}
            title="Instructions"
            isFullWidth
            marginBottom="spacing.6"
          />
          <Box display="flex" gap="spacing.5" paddingLeft="spacing.5">
            <Box display="flex" flexDirection="column" gap="spacing.4">
              {editableColumns.map((col, index) => (
                <Box display="flex" alignItems="center" height="43px" key={`${col.id}_index`}>
                  <Text size="large">{index + 1}</Text>
                </Box>
              ))}
            </Box>
            <DndContext
              sensors={sensors}
              collisionDetection={closestCorners}
              onDragEnd={handleDragEnd}
            >
              <Box display="flex" flexDirection="column" flex={1} gap="spacing.4">
                <SortableContext items={editableColumns} strategy={verticalListSortingStrategy}>
                  {editableColumns.map((col) => (
                    <Draggable
                      key={col.id}
                      id={col.id}
                      name={col.name}
                      isSelected={col.isSelected}
                      mandatoryColumns={[BILL_ID_COL_KEY, BILL_AMOUNT_COL_KEY, BILL_DATE_COL_KEY]}
                      onMandatoryColumDeselection={() => {
                        toast.show({
                          color: 'negative',
                          content: 'Mandatory columns cannot be deselected.',
                          autoDismiss: true,
                          duration: 3000,
                        });
                      }}
                      toggleColumnSelection={toggleColumnSelection}
                    />
                  ))}
                </SortableContext>
              </Box>
            </DndContext>
          </Box>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button variant="tertiary" onClick={dismiss}>
            Cancel
          </Button>
          <Button onClick={handleSave}>Save</Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default EditColumnsModal;
