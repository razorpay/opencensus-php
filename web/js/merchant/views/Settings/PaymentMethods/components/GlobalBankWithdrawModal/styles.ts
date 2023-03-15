import styled from 'styled-components';

export const Wrapper = styled.div`
  background-color: #fafafa;
  width: 700px;
  max-width: 100%;
`;

export const ModalHeader = styled.div`
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.4rem 0;
  margin: 0 1.875rem;
  border-bottom: 1px solid #e2e2e2;
`;

export const ModalFooter = styled.div`
  display: flex;
  justify-content: flex-end;
  background-color: #f6f6f6;
  box-shadow: 0px -1px 0px rgba(0, 0, 0, 0.05);
  padding: 1.25rem 1.875rem;
`;

export const ModalBody = styled.div<{ small?: boolean }>`
  position: relative;
  margin-left: auto;
  margin-right: auto;
  padding: 1.5rem 1.875rem;
  max-width: 550px;
  width: 100%;
  ${({ small }) => (small ? 'max-width: 100%; width: 375px;' : '')}
`;

export const FormRow = styled.div<{
  flex?: boolean;
  textCenter?: boolean;
  mt?: string;
  mb?: string;
}>`
  ${({ mt }) => (mt ? `margin-top: ${mt};` : '')}
  ${({ mb }) => `margin-bottom: ${mb || '1rem'};`}
  ${({ flex }) => (flex ? 'display: flex; gap: 1rem;' : '')}
  ${({ textCenter }) => (textCenter ? 'text-align: center;' : '')}
`;

export const AmountRow = styled.div`
  margin-top: 3rem;
`;

export const LoaderOverlay = styled.div`
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  z-index: 100;
  background: rgba(255, 255, 255, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
`;
