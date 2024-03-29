import styled from 'styled-components';

export const FormWrapper = styled.div`
  margin-top: 28px;
  display: flex;
  flex-direction: column;
  gap: 30px;
`;
export const FieldLabel = styled.label`
  color: #262d3a;
  font-weight: 600;

  sup {
    color: red;
    font-size: 14px;
    position: relative;
    top: -2px;
  }
`;

export const FieldContainer = styled.div`
  .Input {
    margin: 0;
  }
`;
