import styled from 'styled-components';

export const StyledProgramTypeContainer = styled.div<{ backgroundColor: string }>`
  margin-left: 8px;
  background-color: ${(props) => props.backgroundColor};
  border-radius: 4px;
  padding: 2px 6px 2px 6px;
`;

export const StyledPopupOverlay = styled.div`
  position: absolute;
  right: 0;
  left: 0;
  top: 0;
  bottom: 0;
`;
