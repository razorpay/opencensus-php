import styled from 'styled-components';

export const Container = styled.div`
  font-size: 13px;
  line-height: 18px;
  color: rgba(22, 47, 86, 0.54);

  span {
    color: rgba(11, 112, 231, 0.87);
    cursor: pointer;
  }
`;

export const ModalContainer = styled.div`
  .Modal-container--wrapper {
    width: 664px;
    height: calc(100vh - 10%);
    overflow: auto;

    &::-webkit-scrollbar {
      width: 0;
    }

    &::-webkit-scrollbar-thumb {
      background-color: transparent;
    }
  }

  .content {
    font-size: 14px;
    line-height: 18px;
    color: rgba(22, 47, 86, 0.74);
    margin-bottom: 30px;
    padding: 24px;

    span {
      color: rgba(22, 47, 86, 0.74);
      font-weight: 700;
    }
  }

  .modal-header {
    h3 {
      color: rgba(22, 47, 86, 0.87);
    }
  }
`;
