import styled from 'styled-components';

// A fix to make sure card uses up all the place
export const CardContainer = styled.div`
  > div {
    height: 100%;

    > div:nth-of-type(2) {
      height: inherit;
    }
  }
`;
