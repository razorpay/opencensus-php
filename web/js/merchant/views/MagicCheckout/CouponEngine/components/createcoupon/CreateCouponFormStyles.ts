import { Link } from 'react-router-dom';
import styled from 'styled-components';

export const Card = styled.div`
  padding: 18px;
  border-radius: 10px;
  border: 1px solid #e2e3e3;
  background: white;
  margin-top: 16px;
`;

export const CreateCouponFormWrapper = styled.div`
  padding: 20px;
  background: #f9fbfc;

  .Input {
    margin: 0;
  }

  .form-label {
    color: #344a6c;
    font-weight: 700;
    width: 200px;
  }

  .w-350 {
    width: 100%;
    max-width: 350px;
  }

  .w-200 {
    width: 100%;
    max-width: 200px;
  }

  .widget-wrapper {
    margin: 16px 0;
  }
`;

export const Container = styled.div`
  display: flex;
  justify-content: space-between;
  padding: 20px;
  width: 100%;
  min-height: 400px;
  position: relative;
  background-color: #ffffff;
`;

export const BackLink = styled(Link)`
  text-decoration: none;
  font-size: 14px;
  font-weight: 700;
  color: #00000080;
  display: flex;
  align-items: flex-end;
  margin-bottom: 20px;
  cursor: pointer;

  i {
    font-size: 16px;
  }
`;

export const CouponName = styled.div`
  font-weight: 700;
  color: #262d3a;
  line-height: 20px;
  font-size: 20px;

  .coupon-slash {
    opacity: 0.4;
    margin-left: 1px;
    font-size: 16px;
  }

  .coupon-type {
    font-size: 16px;
  }
`;

export const FormGroup = styled.div`
  display: flex;
  margin-bottom: 16px;

  .Input {
    margin: 0;
  }

  .form-label {
    font-weight: 600;
  }

  .form-input {
    flex-grow: 1;
    max-width: 350px;
  }

  .mb-12 {
    margin-bottom: 12px;
  }

  .mt-12 {
    margin-top: 12px;
  }

  .mb-4 {
    margin-bottom: 4px;
  }

  .mt-4 {
    margin-top: 4px;
  }

  .discount-amount-label {
    font-size: 12px;
    font-weight: 400;
    line-height: 20px;
    color: #344a6c;
  }

  .error-message {
    font-size: 12px;
    font-weight: 400;
    line-height: 14px;
    margin-top: 6px;
    color: #f05050;
  }

  .error-field {
    border: 1px solid #f05050;
  }

  .max-width-100 {
    max-width: 100%;
  }

  .mt-16 {
    margin-top: 16px;
  }
`;

export const CheckboxGroup = styled.div`
  display: flex;
  margin-top: 16px;

  input[type='checkbox'] {
    margin-right: 8px;
  }

  span {
    font-size: 12px;
    font-weight: 600;
    line-height: 20px;
    color: #58666e;
  }
`;

export const CtaContainer = styled.div`
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
  align-items: center;
  margin-top: 20px;
  justify-content: end;

  button {
    font-weight: 600;
    font-size: 14px;
    line-height: 150%;
    padding: 12px 16px;
    cursor: pointer;
    border: none;
    width: 160px;
  }

  // reason fro adding this was, I am using Async Button for making my primary cta, but on hover its background color is changing to white, so I am overriding it here
  .primary-cta,
  .primary-cta:hover {
    background: #2a86f3;
    color: #fafbfc;
    border-radius: 2px;
  }

  .secondary-cta {
    color: #162f56;
    background: #ffffff;
  }

  button:disabled {
    background: #d9d9d9;
    color: rgba(65, 68, 73, 0.8);
  }
`;

export const AddItemContainer = styled.div`
  background: #fff;
  width: 100%;
  padding: 16px 24px;
  display: flex;
  flex-direction: column;

  .scroll {
    max-height: 256px;
    overflow: scroll;
  }

  .scroll::-webkit-scrollbar {
    -webkit-appearance: none;
    width: 4px;
  }

  .scroll::-webkit-scrollbar-thumb {
    background-color: #2a86f3;
  }

  .scroll::-webkit-scrollbar-track:vertical {
    background: #f6f6f7;
  }

  .page-spinner-container {
    height: 240px;
  }
`;

export const ModalHeader = styled.div`
  display: flex;
  align-items: center;
  justify-content: space-between;

  .title {
    font-size: 16px;
    font-weight: 600;
    line-height: 150%;
    color: #171a1e;
  }

  .exit-cta {
    font-size: 16px;
    color: #162f5661;
  }
`;

export const ModalCtaWrapper = styled.div`
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
  background: #528ff01a;
  padding: 16px 24px;
  justify-content: space-between;
  align-items: center;

  button {
    font-weight: 600;
    font-size: 14px;
    line-height: 150%;
    padding: 12px 16px;
    cursor: pointer;
    border: none;
    width: 90px;
  }

  .primary-cta {
    background: #2a86f3;
    color: #fafbfc;
    border-radius: 2px;
  }

  .secondary-cta {
    border: 1px solid #2a86f3;
    background: #eaf2fd;
    color: #2b83ea;
    border-radius: 2px;
    margin-right: 16px;
  }

  button:disabled {
    background: #d9d9d9;
    color: rgba(65, 68, 73, 0.8);
  }
`;

export const DottedButtonWrapper = styled.div`
  background: #fafbfc;
  padding: 16px 20px;
  border: 1px solid #ecedee;
  border-radius: 6px;
  margin: 10px 0;
`;

export const DottedButton = styled.button`
  height: 75px;
  border: 1.5px dotted #528ff0;
  background: white;
  border-radius: 10px;
  color: #0b70e7;
`;

export const HorizontalRadioButtons = styled.div`
  display: flex;
  flex-direction: column;
  border: 1px solid #f6f6f6;
  padding: 0 16px;
  max-height: 350px;
  overflow: scroll;

  ::-webkit-scrollbar {
    -webkit-appearance: none;
    width: 4px;
  }

  ::-webkit-scrollbar-thumb {
    background-color: #2a86f3;
  }

  ::-webkit-scrollbar-track:vertical {
    background: #f6f6f7;
  }

  .Input {
    margin: 0;
  }

  .form-input {
    width: 100%;
  }

  .Input--radioLabels {
    display: flex;
    flex-direction: column;
  }

  label {
    margin-right: 0;
    padding: 18px 0;
    border-top: 1px dashed #0000001a;
  }

  .add-cta {
    font-weight: 700;
    color: #2a86f3;
    font-size: 14px;
    line-height: 20px;
    padding: 18px 0;
    cursor: pointer;
    width: fit-content;
  }

  .Input-radio {
    margin-right: 14px;
  }

  .Input-inlineLabel {
    vertical-align: top;
  }

  .main-label {
    color: #171a1e;
    font-size: 14px;
    line-height: 20px;
    font-weight: 500;
  }
`;

export const CollectionsList = styled(HorizontalRadioButtons)`
  margin-top: 24px;

  label:first-child {
    border-top: none;
  }
`;

export const MinimumQuantityWrapper = styled.div`
  display: flex;
  gap: 24px;
  max-width: 100%;
  flex-grow: 1;
`;

export const GreyContainer = styled.div`
  padding: 12px;
  background: white;
  border: 1px solid #dbdbdc;
  border-rdius: 2px;
  display: flex;
  align-items: center;
  justify-content: space-between;
`;

export const RemoveIcon = styled.i`
  color: red;
  font-size: 20px;
  cursor: pointer;
`;

export const Label = styled.div`
  font-size: 12px;
  font-weight: 600;
  line-height: 20px;
  color: #58666e;
`;

export const MoreDetailsContainer = styled.div`
  background: #fafbfc;
  padding: 16px 20px;
  border: 1px solid #ecedee;
  borderradius: 6px;
  margin: 10px 0;
`;

export const SubTitle = styled.div`
  font-size: 12px;
  font-weight: 600;
  line-height: 20px;
  color: #58666e;
`;

// making this InputIcon styled component to override the default css written for input icon in Input component
export const InputIcon = styled.i`
  font-size: 10px !important;
`;
