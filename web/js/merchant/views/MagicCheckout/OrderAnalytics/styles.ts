import styled from 'styled-components';

export const TabsContainer = styled.div`
  width: 100%;
  min-height: 400px;
  position: relative;
  background-color: #fff;
  display: flex;
`;

export const Tabs = styled.div`
  background-color: #f3f4f6;
  width: 175px;
  border: 1px solid #e0e8f4;
  border-top-width: 0;
  border-bottom-width: 0;
`;

export const Tab = styled.div`
  background-color: transparent;
  border-left: 4px solid transparent;
  padding: 12px 16px;
  line-height: 32px;
  cursor: pointer;
  margin-bottom: 4px;
  color: #999;
  font-weight: 600;
  font-size: 14px;
  &.active {
    background-color: #fff;
    border-color: #528ff0;
    color: #528ff0;
    font-weight: 600;
    border-right: 1px solid #fff;
  }
`;
