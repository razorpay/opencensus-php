import React from 'react';
import {
  DndContext,
  closestCenter,
  KeyboardSensor,
  PointerSensor,
  useSensor,
  useSensors,
} from '@dnd-kit/core';
import {
  arrayMove,
  sortableKeyboardCoordinates,
  rectSortingStrategy,
  SortableContext,
} from '@dnd-kit/sortable';

export type SortableListItemData<T> = {
  item: T;
  id: string;
};

export type SortableListProps<T> = {
  listUpdater: (blocks: SortableListItemData<T>[]) => void;
  list: SortableListItemData<T>[];
  children: React.ReactNode;
};

export function SortableList<T>({ list, listUpdater, children }: SortableListProps<T>) {
  const sensors = useSensors(
    useSensor(PointerSensor, {
      activationConstraint: {
        delay: 100,
        tolerance: 5,
      },
    }),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    }),
  );

  function handleDragEnd(event) {
    const { active, over } = event;
    if (active.id !== over.id) {
      const oldIndex = list.findIndex((item) => item.id === active.id);
      const newIndex = list.findIndex((item) => item.id === over.id);
      const updatedList = arrayMove(list, oldIndex, newIndex);
      listUpdater(updatedList);
    }
  }

  return (
    <DndContext
      sensors={sensors}
      collisionDetection={closestCenter}
      onDragEnd={handleDragEnd}
      autoScroll={false}
    >
      <SortableContext items={list} strategy={rectSortingStrategy}>
        {children}
      </SortableContext>
    </DndContext>
  );
}
