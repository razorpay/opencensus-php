import styled from 'styled-components';

export const StyledButtonText = styled.button`
  color: ${({ theme }) => theme.colors.brand.primary['500']};
  background: transparent;
  border: none;
  outline: none;
  cursor: pointer;
`;
