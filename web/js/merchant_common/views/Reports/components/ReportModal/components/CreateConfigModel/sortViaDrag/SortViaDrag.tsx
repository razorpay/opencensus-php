import React from 'react';
import {
  SortMoveHandler,
  SortStartHandler,
  SortableContainer,
  SortableElement,
  SortableHandle,
} from 'react-sortable-hoc';
import noop from 'lodash/noop';
import { Box, MenuIcon, Text } from '@razorpay/blade/components';
import styled from 'styled-components';

// Styled component for the drag handle container
export const DragHandleContainer = styled.div(
  ({ theme }) => `
  cursor: pointer;
  overflow: hidden;
  border-radius: ${theme.spacing[2]}px;
`,
);

// Function to reorder an array based on drag-and-drop index changes
export const arrayIndexing = (array, oldIndex, newIndex): any[] => {
  const updatedArray = [...array];
  if (oldIndex >= 0 && oldIndex < updatedArray.length) {
    const [item] = updatedArray.splice(oldIndex, 1);
    updatedArray.splice(newIndex, 0, item);
  }
  return updatedArray;
};

// Draggable handle component used for sorting
export const DragHandle = SortableHandle<{ children: JSX.Element }>(
  ({ children }): JSX.Element => (
    <DragHandleContainer>
      <Box>{children}</Box>
    </DragHandleContainer>
  ),
);

// Sortable item component that wraps each item in the list
const SortableItem = SortableElement<{ children: JSX.Element }>(({ children }): JSX.Element => {
  return <>{children}</>;
});

// Sortable container that wraps the entire list
const SortableList = SortableContainer<{ children: JSX.Element[] }>(({ children }): JSX.Element => {
  return (
    <Box position="relative" maxHeight="330px">
      {children}
    </Box>
  );
});

export const SortViaDrag = <T,>({
  items,
  onChange,
  onStart,
  onMove,
  keyCodes,
  enableKeyCodes,
  renderCustomComponent,
}: {
  items: T[];
  onChange: (x: T[]) => void;
  onStart?: SortStartHandler;
  onMove?: SortMoveHandler;
  keyCodes?: {
    lift?: number[];
    drop?: number[];
    cancel?: number[];
    up?: number[];
    down?: number[];
  };
  enableKeyCodes?: boolean;
  renderCustomComponent?: (item: T, index: number) => JSX.Element;
}): JSX.Element => {
  // Function to handle the sorting event and update item positions
  const onSortEnd = ({ oldIndex, newIndex }) => {
    if (oldIndex !== newIndex) {
      const updatedItems = arrayIndexing(items, oldIndex, newIndex);
      onChange && onChange(updatedItems);
    }
  };
  return (
    <SortableList
      onSortEnd={onSortEnd}
      onSortStart={onStart}
      onSortMove={onMove}
      keyCodes={enableKeyCodes ? keyCodes : {}}
      useDragHandle
    >
      {items?.map((item, index) => (
        <SortableItem key={index} index={index}>
          {renderCustomComponent ? (
            renderCustomComponent(item, index)
          ) : (
            <Box
              padding="spacing.4"
              display="flex"
              alignItems="center"
              borderColor="surface.border.gray.muted"
              borderWidth="thinner"
              borderRadius="medium"
            >
              <MenuIcon marginRight="spacing.4" size="medium" color="currentColor" />
              <Text>{item}</Text>
            </Box>
          )}
        </SortableItem>
      ))}
    </SortableList>
  );
};

SortViaDrag.defaultProps = {
  onChange: noop,
  keyCodes: {
    lift: [32],
    drop: [32],
    cancel: [27],
    up: [38, 37],
    down: [40, 39],
  },
  enableKeyCodes: false,
  onStart: noop,
  onMove: noop,
};
