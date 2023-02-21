import styled from 'styled-components';

export const DropDownMenu = styled.div`
  cursor: pointer;
  width: 100%;
  height: auto;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
`;

export const SearchResult = styled.div`
  height: calc(100% - 40px);
  display: flex;
  flex-direction: column;
  overflow: scroll;
`;

export const DropDownItems = styled.div`
  border-radius: 4px;
  min-width: 220px;
  display: flex;
  flex-direction: column;
  position: absolute;
  background: #fff;
  padding: 8px;
  box-shadow: 4px 4px 10px #0000000d;
  max-height: 15rem;
  overflow-y: scroll;
  z-index: 99;
  box-sizing: border-box;
  top: 32px;
  left: -1px;
`;

export const InputContainer = styled.div`
  position: relative;

  i {
    position: absolute;
    right: 6px;
    top: 10px;
  }
`;

export const InputField = styled.input`
  color: #555;
  height: 32px;
  line-height: 48px;
  box-sizing: border-box;
  background: transparent;
  font-size: 14px;
  letter-spacing: -0.08px;
  text-overflow: ellipsis;
  margin: 0;
  white-space: nowrap;
  cursor: pointer;
  width: 100%;
  text-align: left;
  border: 1px solid #d1dadd;
  border-radius: 3px;
  padding: 9px 30px 9px 12px;
  &:focus {
    outline: 0;
  }
`;

export const DropDownItem = styled.span`
  position: relative;
  left: 0;
  cursor: pointer;
  padding-bottom: 1rem;
  border-bottom: 1px solid #eeeeee;
  padding-top: 1rem;
  font-size: 12px;
  line-height: 14px;
  padding-left: 30px;
`;

export const DropdownValue = styled.span`
  position: relative;
  display: flex;
  align-items: center;
  padding: 4px 8px;
  background: rgba(22, 47, 86, 0.05);
  border: 1px solid #d1dadd;
  border-radius: 3px 0px 0px 3px;
  font-weight: 700;
  font-size: 12px;
  line-height: 14px;
  color: #555555;

  span.flag {
    margin-right: 4px;
  }

  i {
    font-size: 18px;
    align-self: flex-end;
    width: 16px;
  }
`;

export const LeftIconContainer = styled.span`
  position: absolute;
  left: 4px;
  top: 12px;
`;

export const ValueContainer = styled.div`
  display: flex;

  input {
    border: 1px solid #cfdadd;
    padding: 0 5px;
  }
`;
