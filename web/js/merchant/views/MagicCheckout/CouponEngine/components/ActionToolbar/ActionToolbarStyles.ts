import styled from 'styled-components';

export const StyledListItem = styled.li`
  padding: 12px 18px;
  box-shadow: 0px -1px 0px 0px #e2e2e299 inset;
  width: 200px;
  transition: background-color 0.3s;
  text-transform: initial;

  &:hover {
    background-color: #f8fbff;
    cursor: pointer;
  }
`;

export const ActionText = styled.div`
  display: flex;
  align-items: center;
  gap: 8px;
  color: #333333;
  font-size: 14px;
  font-weight: 700;

  & > img {
    height: 14px;
  }
`;
