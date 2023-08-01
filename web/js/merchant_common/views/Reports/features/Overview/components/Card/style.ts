import styled from 'styled-components';

// A fix to make sure card uses up all the place
export const CardContainer = styled.div`
  > div:nth-of-type(1) {
    height: 100%;
  }
  > div {
    > div:nth-of-type(1) {
      height: 100%;
      > div:nth-of-type(1) {
        > div:nth-of-type(1) {
          > div:nth-of-type(1) {
            align-items: center;
          }
        }
      }
      > div:nth-of-type(2) {
        height: inherit;
      }
    }
  }
`;
