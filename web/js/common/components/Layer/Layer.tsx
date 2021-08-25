import React, { useEffect, ReactNode, useState, useCallback } from 'react';
import ReactDOM from 'react-dom';
import { useLayer } from './LayerContext';

export interface LayerPropsT {
  children: ReactNode;
  onDocClick?: (e: MouseEvent) => void;
  onEscape?: (e: KeyboardEvent) => void;
}
const Layer: React.FC<LayerPropsT> = ({ children, onDocClick, onEscape }) => {
  const {
    host,
    onAddDocClickHandler,
    onRemoveDocClickHandler,
    onAddEscapeKeyHandler,
    onRemoveEscapeKeyHandler,
  } = useLayer();
  const [container, setContainer] = useState<HTMLDivElement | undefined>(undefined);
  const onDocClickHandler = useCallback(
    (event: MouseEvent) => {
      if (onDocClick) {
        onDocClick(event);
      }
    },
    [onDocClick],
  );
  const onEscapeKeyHandler = useCallback(
    (e: KeyboardEvent) => {
      if (onEscape) {
        onEscape(e);
      }
    },
    [onEscape],
  );

  useEffect(() => {
    const _host = host.current || document.body;
    const _container = _host.ownerDocument.createElement('div');
    _host.appendChild(_container);
    setContainer(_container);
    return () => {
      _host.removeChild(_container);
    };
  }, [host]);

  useEffect(() => {
    onAddDocClickHandler(onDocClickHandler);
    return () => {
      onRemoveDocClickHandler(onDocClickHandler);
    };
  }, [onDocClickHandler, onAddDocClickHandler, onRemoveDocClickHandler]);

  useEffect(() => {
    onAddEscapeKeyHandler(onEscapeKeyHandler);
    return () => {
      onRemoveEscapeKeyHandler(onEscapeKeyHandler);
    };
  }, [onEscapeKeyHandler, onAddEscapeKeyHandler, onRemoveEscapeKeyHandler]);

  if (!container) {
    return null;
  }
  return ReactDOM.createPortal(children, container);
};

export default Layer;
