import styled from 'styled-components';

export const StyledTabsWrapper = styled.div`
  .magic-settings-tabs > .tabs-content.analytics-settings {
    padding: 24px;

    .tabs-component {
      height: 100%;
      margin: 0;
    }

    .tabs-component-wrapper {
      height: 100%;

      .Input-elWrapper > .Input-el:disabled {
        color: #000;
        border: 1px solid #000;
        opacity: 1;
        background: #fff;
        cursor: not-allowed;
      }

      .spinner-container {
        background: #fff;
        display: flex;
        height: 100%;
      }
    }
  }
`;
