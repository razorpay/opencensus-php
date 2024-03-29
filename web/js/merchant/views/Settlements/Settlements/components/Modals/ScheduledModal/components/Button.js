import React from 'react';
import styled from 'styled-components';

import { AsyncBtn } from 'common/new-ui/Button';

const EnableButton = styled(AsyncBtn.Primary)`
  font-weight: 600;
  line-height: 17px;
  width: 100%;
`;

export default function Button({ onClick, pendingState, disabled, children }) {
  return (
    <EnableButton pendingState={pendingState} onClick={onClick} disabled={disabled}>
      {children}
    </EnableButton>
  );
}
