import styled from 'styled-components';

export const SuperSetDashboardWrapper = styled.div<{ height: string }>`
  width: 100%;
  height: ${(props) => props.height};

  & > iframe {
    width: 100%;
    height: 100%;
    border: 0;
  }
`;
