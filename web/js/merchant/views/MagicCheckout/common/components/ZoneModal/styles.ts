import styled from 'styled-components';
import {
  CheckboxWrapper,
  ModalItem,
} from 'merchant/views/MagicCheckout/common/components/SettingsModal/styles';

export const ModalWrapper = styled.div`
  width: 100%;
  height: 340px;
  .countries-virtualised-list {
    &::-webkit-scrollbar {
      width: 5px;
    }
    &::-webkit-scrollbar-track {
      background: #f6f6f7;
    }
    &::-webkit-scrollbar-thumb {
      background-color: ${({ theme }) => theme.colors.brand.primary[500]};
    }
  }
`;

export const ZoneItem = styled(ModalItem)`
  .states-trigger {
    font-size: 14px;
    cursor: pointer;
    margin-right: 15px;
    i {
      margin-left: 10px;
    }
  }
`;

export const Country = styled.div`
  width: 40px;
  height: 40px;
  margin-left: 20px;
  overflow: hidden;
  display: flex;
  align-items: center;
  img {
    width: 100%;
  }
`;

export const ZoneCheckboxWrapper = styled(CheckboxWrapper)`
  &&& {
    .flag {
      margin-left: 20px;
      display: inline-block;
      height: 21px;
      width: 40px;
      background-repeat: no-repeat;
      border-radius: 0;
      background-position-x: 0;
    }
    label {
      padding: 15px;
      width: 100%;
      cursor: pointer;
      margin-bottom: 0;
    }
  }
`;
