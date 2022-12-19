import React from 'react';
import styled from 'styled-components';

const StyledDivider = styled.div`
  border: 0.5px solid #454b64;
  flex: none;
  order: 3;
  align-self: stretch;
  flex-grow: 0;
  margin: 18px 0;
`;

const Divider = (): JSX.Element => <StyledDivider />;

export default Divider;
