import styled, { createGlobalStyle } from 'styled-components';

export const BodyOverflowStyle = createGlobalStyle<{ isSearchOpen: boolean }>`
  body {
    overflow: ${({ isSearchOpen }) => (isSearchOpen ? 'hidden' : 'auto')};
  }
`;

export const SearchItemWrapper = styled.div`
  cursor: pointer;
`;

export const HighlightedSpan = styled.span`
  font-weight: bold;
  color: #000;
`;
