import styled from 'styled-components';

import { AsyncBtn } from 'common/new-ui/Button';

export const ModalHeader = styled.div`
  .modal-title {
    font-size: 1.5rem;
    margin: 0;
  }
  .font-12 {
    font-size: 1rem;
    margin: 12px 0;
  }
`;

export const ModalFooter = styled.div`
  border-top: none;
  text-align: left;
`;

export const CancelButton = styled(AsyncBtn)`
  margin-right: 1rem;
`;

export const ActionButton = styled(AsyncBtn.Primary)`
  width: 176px;
  margin-right: 0;
`;
