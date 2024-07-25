import styled from 'styled-components';

export const StyledCard = styled.div`
  > div {
    ${({ isDisabled }) => (isDisabled ? 'box-shadow: none;' : '')}
    > div {
      border-width: 0.5px;
    }
  }
`;
