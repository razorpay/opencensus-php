import styled from 'styled-components';

export const DraggableCardWrapper = styled.div`
  cursor: pointer;
  touch-action: none;
  &:active {
    cursor: grabbing;
  }
`;
