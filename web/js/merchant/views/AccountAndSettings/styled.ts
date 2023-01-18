import styled from 'styled-components';
import lazy from 'merchant/routes/LazyLoader';

const Configuration = lazy(() =>
  import(/* webpackChunkName: "Configuration" */ 'merchant/views/Settings/Configuration'),
);

export const StyledConfiguration = styled(Configuration)`
  &#settings-content {
    max-width: 100%;
    padding: 0;
  }
  &.content-sm {
    min-height: auto;
  }
  .panel {
    margin-bottom: 0;
  }
  .scroll-into-view {
    border: none;
  }
`;

export const StyledHeader = styled.header`
  && {
    border-top: 0;
  }
`;

export const StyledContent = styled.div`
  &.content {
    margin-top: 20px;
  }
`;

export const StyledDivider = styled.div`
  margin: 25px 0px 0px 0px;
`;

export const StyledTabContentContainer = styled.div`
  && {
    background: white;
    padding: 24px;
    @media screen and (max-width: 768px) {
      padding: 0;
    }
  }
  .list-group,
  .panel {
    margin-bottom: 0;
  }
`;
