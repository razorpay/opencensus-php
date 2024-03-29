import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const StyledFieldContainer = styled.div`
  .container {
    display: flex;
    justify-content: flex-start;
    width: 100%;
    margin-top: 15px;
  }

  .document-label {
    position: relative;
    color: #58666e;
    font-size: 14px;
    padding: 8px;
    margin-left: 20px;
    font-weight: 600;
    max-width: 150px;
    text-align: right;

    &.required::after {
      content: '*';
      font-size: 12px;
      position: absolute;
      margin-left: 1px;
      top: 7px;
      color: #f05050;
    }
  }

  .document-label-info {
    color: #8895a8;
    font-size: 12px;
    padding: 10px;
    width: 250px;
  }

  .Input {
    margin-top: 0;
  }

  .file-uploader {
    margin-top: 15px;
  }

  @media (max-width: 820px) {
    .container {
      flex-direction: column;
      align-items: flex-start;
      margin-left: -25px;
    }

    .document-label {
      margin-left: 0;
      max-width: initial;
      text-align: left;
    }
  }
`;

export const StyledPurposeCodeWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
  .Input--radio {
    @media (max-width: ${theme.breakpoints.s}px) {
      display: flex;
      flex-direction: column;
    }
    .Input-content {
      width: unset !important;
    }
  }

  .Input--radioLabels {
    label {
      display: flex;
      margin-bottom: ${theme.spacing[5]}px; 
      
      input {
        width: 0;
      }

      .Input-radio {
        min-width: 18px;
      }
    }
  }
`,
);
