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

export const StyledRCODShippingNoteWrapper = styled.div`
  padding: 8px;
  border: 1px solid #bd7a03;
  border-left: 3px solid #bd7a03;
  border-radius: 4px;
  background: #fff;
  color: #435775;
`;

export const StyledHelperText = styled.div`
  color: #768ea7;
  font-size: 12px;
  font-weight: 400;
  line-height: 24px;
  letter-spacing: 0;
`;
