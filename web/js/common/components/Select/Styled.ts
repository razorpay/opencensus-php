import styled from 'styled-components';

export const OptionCheckIcon = styled.div`
  margin-left: auto;
`;

export const OptionContainer = styled.div<any>`
  opacity: ${(props) => (props.$disabled ? 0.5 : 1)};
`;
