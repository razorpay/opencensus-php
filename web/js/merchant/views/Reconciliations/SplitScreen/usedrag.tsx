import { useRef, useEffect, useState, useCallback } from 'react';
import { throttle } from 'lodash';

import type {
  UseDragOptions,
  UseDragState,
  UseDragReturnType,
} from 'merchant/views/Reconciliations/SplitScreen/types';

const DEFAULT_OPTIONS = {
  throttleDelay: 100,
  minWidth: 0,
  maxWidth: 100,
  initialWidth: 50,
} as const;

const calculateNewWidth = (
  clientX: number,
  dragState: UseDragState,
  minWidth: number,
  maxWidth: number,
): number => {
  const deltaX = clientX - dragState.initialOffset;
  const deltaWidth = (deltaX / window.innerWidth) * 100;
  const newWidth = dragState.initialWidth + deltaWidth;
  return Math.min(Math.max(newWidth, minWidth), maxWidth);
};

const useDrag = (options: UseDragOptions): UseDragReturnType => {
  const { throttleDelay, minWidth, maxWidth, initialWidth } = {
    ...DEFAULT_OPTIONS,
    ...options,
  };

  const [dynamicWidth, setDynamicWidth] = useState(initialWidth);
  const dragStateRef = useRef<UseDragState>({
    isDragging: false,
    initialOffset: 0,
    initialWidth,
  });

  const handleMouseMove = useCallback(
    throttle((e: MouseEvent) => {
      if (!dragStateRef.current.isDragging) return;
      const newWidth = calculateNewWidth(e.clientX, dragStateRef.current, minWidth, maxWidth);
      setDynamicWidth(newWidth);
    }, throttleDelay),
    [throttleDelay, minWidth, maxWidth],
  );

  const handleDragMouseDown = useCallback(
    (e: React.MouseEvent) => {
      e.preventDefault();
      dragStateRef.current = {
        isDragging: true,
        initialOffset: e.clientX,
        initialWidth: dynamicWidth,
      };
    },
    [dynamicWidth],
  );

  const handleMouseUp = useCallback(() => {
    dragStateRef.current.isDragging = false;
  }, []);

  useEffect(() => {
    window.addEventListener('mousemove', handleMouseMove);
    window.addEventListener('mouseup', handleMouseUp);

    return () => {
      window.removeEventListener('mousemove', handleMouseMove);
      window.removeEventListener('mouseup', handleMouseUp);
    };
  }, [handleMouseMove, handleMouseUp]);

  return {
    dynamicWidth,
    handleDragMouseDown,
    isDragging: dragStateRef.current.isDragging,
  };
};

export { useDrag };
