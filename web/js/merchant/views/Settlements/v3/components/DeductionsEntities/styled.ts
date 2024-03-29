import styled from 'styled-components';

export const StyledEntityContainer = styled.div`
  h5 {
    background-color: ${({ theme }) => `${theme.colors.surface.background.gray.intense}`};
    padding-top: ${({ theme }) => `${theme.spacing[7]}px`};
    padding-right: ${({ theme }) => `${theme.spacing[0]}px`};
    padding-bottom: ${({ theme }) => `${theme.spacing[0]}px`};
    padding-left: ${({ theme }) => `${theme.spacing[7]}px`};
  }
`;
