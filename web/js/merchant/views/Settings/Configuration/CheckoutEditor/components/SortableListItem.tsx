import React from 'react';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

import { SortableItem } from './styles';

export type SortableListItemProps = {
  id: string;
  children: React.ReactNode;
  showDragHandle: boolean;
};

export function SortableListItem({ id, children, showDragHandle }: SortableListItemProps) {
  const { attributes, listeners, setNodeRef, transform, transition } = useSortable({ id });
  return (
    <SortableItem
      ref={setNodeRef}
      transform={CSS.Transform.toString(transform)}
      transition={transition}
      attributes={showDragHandle ? { ...attributes } : {}}
      listeners={showDragHandle ? { ...listeners } : {}}
    >
      {children}
    </SortableItem>
  );
}
