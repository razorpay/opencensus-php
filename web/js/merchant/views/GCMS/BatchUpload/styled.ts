import styled from 'styled-components';

export const BatchUploadContainer = styled.div`
  .rto-history-upload {
    .modal-header {
      color: #162f56de;
    }

    .validate-body {
      display: flex;
      flex-direction: column-reverse;

      .modal-body {
        .modal-heading {
          color: #162f56de;
          text-align: start;
        }

        .modal-file {
          margin: 0;
          width: 100%;
        }
      }
    }
  }
`;

export const OrderedList = styled.ol`
  padding-inline-start: 24px;
`;
