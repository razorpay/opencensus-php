import styled from 'styled-components';

export const PointsContainer = styled.div`
  display: flex;
  margin-top: 24px;

  .pointer-stroke {
    border-left: 3px solid #d5d7e2;
  }

  .pointer-container {
    top: -2px;
    left: -9px;
  }
`;
export const PointerHeading = styled.p`
  color: #262d3a;
  font-weight: 700;
  line-height: 8px;
`;
export const PointListContainer = styled.div`
  margin-top: 8px;
`;
export const List = styled.ul`
  padding-left: 12px;
  margin: 0;
`;
export const ListItem = styled.li`
  margin-bottom: 10px;

  &:last-child {
    margin-bottom: 0;
  }
`;

export const HighlightPoint = styled.div`
  width: 8%;
`;
export const HighlightStroke = styled.div`
  width: 92%;
`;
