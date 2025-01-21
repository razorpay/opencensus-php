import React from 'react';
import { Box, Text, Card, CardBody, Checkbox, MoreVerticalIcon } from '@razorpay/blade/components';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

import { DraggableCardWrapper } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/EditColumnsModal/styled';

import type { DraggableProps } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/EditColumnsModal/types';

const Draggable = ({
  id,
  name,
  isSelected,
  mandatoryColumns,
  onMandatoryColumDeselection,
  toggleColumnSelection,
}: DraggableProps): React.ReactElement => {
  const { attributes, listeners, setNodeRef, transform, transition } = useSortable({ id });
  const style = {
    transition,
    transform: CSS.Transform.toString(transform),
  };
  return (
    <DraggableCardWrapper ref={setNodeRef} {...attributes} {...listeners} style={style}>
      <Card elevation="none" padding="spacing.3">
        <CardBody>
          <Box display="flex" justifyContent="space-between" alignItems="center">
            <Box display="flex" alignItems="center" gap="spacing.4">
              <Box display="flex">
                <MoreVerticalIcon position="relative" left="11px" />
                <MoreVerticalIcon />
              </Box>
              <Text size="large" weight="semibold">
                {name}
              </Text>
            </Box>
            <div
              role="checkbox"
              tabIndex={0}
              aria-checked={isSelected}
              onKeyDown={(e) => e.stopPropagation()}
              onMouseDown={(e) => e.stopPropagation()}
              onPointerDown={(e) => e.stopPropagation()}
            >
              <Checkbox
                isChecked={isSelected}
                onChange={(e) => {
                  if (mandatoryColumns.includes(id)) {
                    onMandatoryColumDeselection();
                    return;
                  }
                  toggleColumnSelection(e.isChecked, id);
                }}
              />
            </div>
          </Box>
        </CardBody>
      </Card>
    </DraggableCardWrapper>
  );
};

export default Draggable;
