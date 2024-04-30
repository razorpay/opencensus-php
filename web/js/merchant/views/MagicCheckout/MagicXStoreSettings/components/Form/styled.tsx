import styled from 'styled-components';

export const ColorPickerWrapper = styled.div`
  display: flex;
  position: relative;

  .color-picker {
    position: absolute;
    overflow: hidden;
    height: 43px;
    width: 42px;
  }

  input[type='color'] {
    height: 55px;
    width: 60px;
    position: absolute;
    z-index: 1;
    top: -6px;
    right: -8px;
  }

  .Input {
    width: 100%;

    input {
      padding-left: 52px;
    }
  }
`;
