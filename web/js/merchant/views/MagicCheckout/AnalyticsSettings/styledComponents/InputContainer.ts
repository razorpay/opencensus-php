import styled from 'styled-components';
import Input from 'common/new-ui/Input';

export const InputContainerTable = styled.div`
  display: flex;
  flex-direction: column;

  .field-wrapper {
    .Input > .Input-content > .Input-elWrapper.Select-elWrapper::before {
      margin-top: 17px;
      right: 12px;

      #integration-options.Input-el:disabled {
        color: #000;
        border: 1px solid #e2e2e2;
        background: #fff;
        cursor: not-allowed;
      }
    }
  }
`;
export const TableHeader = styled.div`
  border: 1px solid #e2e2e2;
  padding: 16px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
`;

export const TableBody = styled.div`
  border: 1px solid #e2e2e2;
  border-top: none;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 20px;
`;

export const StyledInput = styled(Input.Select)`
  margin: 0;
  width: 64%;
  height: 36px;
  color: #000;

  .Input-elWrapper.Select-elWrapper {
    cursor: ${(props) => (props.disabled ? 'not-allowed' : 'pointer')};
  }
`;
export const InfoTextContainer = styled.div`
  border-radius: 4px;
  border: 1px solid #df8700;
  background: #fad9a652;
  padding: 10px 16px;
  margin-top: 16px;
`;

export const TableFooter = styled.div`
  border: 1px solid #e2e2e2;
  border-top: none;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 20px;
  gap: 12px;
`;
export const ConfigContainer = styled.div`
  display: flex;
  flex-direction: column;
`;
export const ConfigLabel = styled.p`
  color: #5a6870;
  font-weight: 500;
  font-size: 13px;
  word-wrap: break-word;
`;
export const ConfigValue = styled.p`
  color: #000;
  font-weight: 600;
  word-wrap: break-word;
`;
export const IntegrationFieldWrapper = styled.div`
  display: flex;
  justify-content: space-between;
  align-items: center;
`;
export const HeadingText = styled.div`
  display: flex;
  align-items: center;
  gap: 10px;
`;
export const DeleteActionContainer = styled(HeadingText)`
  gap: 4px;
  .remove-txt {
    color: #f04f50;
    font-weight: 600;
  }
`;
