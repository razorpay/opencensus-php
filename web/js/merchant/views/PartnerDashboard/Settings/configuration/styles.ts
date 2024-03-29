import { Link } from 'react-router-dom';
import styled from 'styled-components';

export const StyledHeader = styled.div(() => ({
  display: 'flex',
  alignItems: 'center',
}));

export const StyledLink = styled(Link)(({ theme }) => ({
  display: 'flex',
  alignItems: 'center',
  color: theme.colors.interactive.icon.primary.subtle,
}));
