import styled from 'styled-components';

// Note: this wrapper is needed because BatchValidate component has tightly coupled styles
export const StyledBulkAddForm = styled.div`
  .partner-submerchant-modal {
    .modal-body {
      padding: 0px;
      .Dropzone-cavity .Dropzone-content-desc--primary {
        font-size: 13px;
      }
    }
    .batch-upload-modal {
      margin-top: 0px;
    }
  }
`;
