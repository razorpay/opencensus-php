import React from 'react';
import styled from 'styled-components';

const NoWrap = styled.div`
  white-space: nowrap;
`;

export const withNoWrap = (content: string) => <NoWrap>{content}</NoWrap>;
