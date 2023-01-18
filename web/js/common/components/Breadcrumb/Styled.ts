import styled from 'styled-components';
import { NavLink } from 'react-router-dom';

export const StyledBreadcrumb = styled.header`
  && {
    border-bottom: 0;
  }
`;

export const StyledBreadcrumbItem = styled(NavLink)`
  && {
    margin-right: 0;
    border: none;
    min-width: auto;
    @media screen and (max-width: 767px) {
      font-size: 12px;
      margin-left: 8px;
    }
    &.active {
      pointer-events: none;
    }
    &:nth-child(n + 3) {
      margin-left: 8px;
    }
  }
`;
