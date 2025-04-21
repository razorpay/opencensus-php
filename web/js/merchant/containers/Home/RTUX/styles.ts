import styled from 'styled-components';

export const ResponsiveWrapper = styled.div`
  /*
    Set min-width to 1540px(8px as buffer) to prevent horizontal scroll.
  */
  @media (min-width: 1540px) {
    width: 1220px;
    margin: 0 auto;
  }
`;
