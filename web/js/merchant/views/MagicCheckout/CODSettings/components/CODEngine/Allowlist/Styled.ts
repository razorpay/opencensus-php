import styled from 'styled-components';

export const AllowlistWrapper = styled.div`
  padding: 24px;
  background: #fff;
  min-height: 200px;
`;

export const ContentLoader = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 200px;
`;

export const AllowlistContainer = styled.div`
  padding: 0;

  .table.cod-engine-allowlist-table {
    td {
      text-align: left;
    }

    td:last-child {
      text-align: right;
    }
  }

  .btn-toolbar {
    margin-top: 28px;
  }
`;

export const TabHeader = styled.div`
  margin-bottom: 30px;

  .heading {
    font-size: 16px;
    font-weight: 600;
    color: #262d3a;
    line-height: 20px;
  }

  .upload-cta {
    .i-plus {
      margin-right: 4px;
    }
  }

  .sub-text {
    margin-top: 8px;
    line-height: 20px;
    color: #162f56bd;
    text-align: start;
  }
`;

export const DeleteAllToolbarContainer = styled.div`
  background-color: #eaeff0;
  padding: 12px;
  display: flex;
  align-items: center;
  justify-content: end;
`;

export const ModalActionsWrapper = styled.div`
  display: flex;
  justify-content: end;
  padding: 15px 24px;
  background-color: #528ff01a;
`;
