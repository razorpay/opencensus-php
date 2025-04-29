import React, { createContext, ReactNode, useRef, useEffect } from 'react';
import styled from 'styled-components';

type LayerHostPosition = 'absolute';

const LayerHost = styled.div<{ position?: LayerHostPosition }>`
  position: ${({ position }) => position || 'static'};
`;

export interface DocClickHandlerT {
  (event: MouseEvent): void;
}

export interface EscapeKeyHandlerT {
  (event: KeyboardEvent): void;
}

export interface LayerContextT {
  onAddDocClickHandler: (docClickHandler: DocClickHandlerT) => void;
  onRemoveDocClickHandler: (docClickHandler: DocClickHandlerT) => void;
  onAddEscapeKeyHandler: (escapeKeyHandler: EscapeKeyHandlerT) => void;
  onRemoveEscapeKeyHandler: (escapeKeyHandler: EscapeKeyHandlerT) => void;
  host: React.MutableRefObject<HTMLDivElement | null>;
}

const layerContext = createContext<LayerContextT | undefined>(undefined);

export const useLayer = (): LayerContextT => {
  const context = React.useContext(layerContext);
  if (!context) {
    throw Error('useLayer should be used within a LayerProvider');
  }
  return context;
};

interface ProviderPropsT {
  children: ReactNode;
  layerHostPosition?: LayerHostPosition;
}

const Wrapper = styled.div<{ isFullHeight: boolean }>`
  height: ${(props) => (props.isFullHeight ? '100%' : 'auto')};
`;

export const LayerProvider: React.FC<ProviderPropsT> = (props) => {
  let docClickHandlers: Array<DocClickHandlerT> = [];
  let escapeKeyHandlers: Array<EscapeKeyHandlerT> = [];
  const onAddDocClickHandler = (docClickHandler: DocClickHandlerT) => {
    docClickHandlers.push(docClickHandler);
  };
  const onRemoveDocClickHandler = (docClickHandler: DocClickHandlerT) => {
    docClickHandlers = docClickHandlers.filter((handler) => handler !== docClickHandler);
  };
  const onAddEscapeKeyHandler = (escapeKeyHandler: EscapeKeyHandlerT) => {
    escapeKeyHandlers.push(escapeKeyHandler);
  };
  const onRemoveEscapeKeyHandler = (escapeKeyHandler: EscapeKeyHandlerT) => {
    escapeKeyHandlers = escapeKeyHandlers.filter((handler) => handler !== escapeKeyHandler);
  };
  const hostEl = useRef<HTMLDivElement | null>(null);
  const isOpenedInOneDashboard = Boolean(window?.ONE_DASHBOARD);

  useEffect(() => {
    const docHandler = (event: MouseEvent) => {
      const handler = docClickHandlers[docClickHandlers.length - 1];
      if (handler) {
        handler(event);
      }
    };
    const keyUpHandler = (event: KeyboardEvent) => {
      const handler = escapeKeyHandlers[escapeKeyHandlers.length - 1];
      if (event.key === 'Escape') {
        if (handler) {
          handler(event);
        }
      }
    };
    document.addEventListener('click', docHandler);
    document.addEventListener('keyup', keyUpHandler);
    return () => {
      document.removeEventListener('click', docHandler);
      document.removeEventListener('keyup', keyUpHandler);
    };
  });

  return (
    <layerContext.Provider
      value={{
        host: hostEl,
        onAddDocClickHandler,
        onAddEscapeKeyHandler,
        onRemoveDocClickHandler,
        onRemoveEscapeKeyHandler,
      }}
    >
      <Wrapper isFullHeight={isOpenedInOneDashboard}>{props.children}</Wrapper>
      <LayerHost
        id="layerHost"
        data-testid="layerTestId"
        ref={hostEl}
        position={props.layerHostPosition}
      />
    </layerContext.Provider>
  );
};

export default layerContext;
