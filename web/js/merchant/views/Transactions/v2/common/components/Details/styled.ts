import { Link } from 'react-router-dom';
import styled from 'styled-components';

export const RouterLink = styled(Link)`
  color: ${({ theme }) => theme.colors.interactive.text.primary.normal};
  display: flex;
  align-items: center;
  gap: ${({ theme }) => theme.spacing[2]}px;
  font-weight: ${({ theme }) => theme.typography.fonts.weight.medium};
  &:hover {
    text-decoration: underline;
  }
`;
