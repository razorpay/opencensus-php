import styled from 'styled-components';

export const ModesDropdownWrapper = styled.div(({ theme }) => ({
  display: 'flex',
  gap: theme.spacing[1],
  alignItems: 'center',
}));

export const RayWrapper = styled.li(({ theme }) => ({
  top: theme.spacing[5],
  [`@media (max-width: ${theme.breakpoints.m}px)`]: {
    top: 0,
  },
}));
