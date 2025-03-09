import styled from 'styled-components';

const ReconDivider = styled.div(({ theme }) => ({
  width: theme.spacing[2],
  background: '#fff',
  cursor: 'col-resize',
  zIndex: '1',
  display: 'flex',
  alignItems: 'center',
  justifyContent: 'center',
  border: `0.5px solid hsla(197, 13%, 11%, 0.15)`,
  borderRadius: theme.spacing[2],
  backgroundColor: 'hsla(0, 0%, 100%, 1)',
  padding: theme.spacing[2],
  boxShadow: theme.elevation.highRaised,

  '&:hover': {
    backgroundColor: 'hsla(218, 89%, 51%, 0.18)',
  },

  '&:active': {
    backgroundColor: 'hsla(218, 89%, 51%, 0.18)',
  },

  transition: 'background-color 0.2s ease',
}));

export { ReconDivider };
