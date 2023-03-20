import styled from 'styled-components';

export const CollapsibleContainer = styled.div`
  transition: 0.6s;
`;

export const Content = styled.div<any>`
  overflow: hidden;
  transition: height 0.4s ease-in-out;
  height: ${({ height }) => height}px;
`;
