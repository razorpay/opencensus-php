import styled from 'styled-components';

export const StyledBottomAction = styled.div`
  display: flex;
  gap: 16px;
  justify-content: flex-end;
  button {
    width: fit-content;
    @media screen and (max-width: 768px) {
      &:nth-child(2) {
        flex: 1;
      }
    }
  }
`;
