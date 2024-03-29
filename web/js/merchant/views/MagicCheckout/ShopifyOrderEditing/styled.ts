import styled from 'styled-components';

export const OrderEditingModalContent = styled.div`
  background: initial;
`;

export const OrderEditingModalWrapper = styled.div`
  .modal-body-wrapper {
    background: #f6f6f7;
  }

  .callout-msg {
    margin: 0 16px;
    color: #212529;
    padding: 8px 16px 16px;
    font-weight: 300;
    font-size: 10px;
  }

  .modal-body {
    padding: 16px;
    display: flex;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    background: #f6f6f7;
    height: 572px;
  }

  @media only screen and (max-width: 767px) {
    .modal-body {
      height: auto !important;
    }
  }

  .scroll {
    max-height: 200px;
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

  .modal-header {
    background: #fff;
    color: black;
    padding: 14px 16px;

    .close {
      color: black;
      opacity: inherit;
    }
  }

  .Input {
    margin-top: 0;
  }
`;

export const Card = styled.div`
  background: #fff;
  padding: 16px;
`;

export const OrdersList = styled(Card)`
  flex-grow: 1;
`;

export const Title = styled.div`
  font-size: 16px;
  font-weight: 600;
  line-height: 150%;
  color: #171a1e;
`;

export const Subtitle = styled.div`
  color: #414449;
  font-weight: 400;
  font-size: 14px;
`;

export const TextBold = styled.div`
  font-weight: 600;
`;

export const ModalBody = styled.div`
  padding: 16px;
  display: flex;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  background: #f6f6f7;
  height: 540px;
`;

export const Aside = styled.div`
  flex-grow: 1;
  flex-direction: column;
  gap: 16px;
  display: flex;
`;

export const Main = styled.div`
  flex-grow: 2;
  display: flex;
  flex-direction: column;
  gap: 16px;
`;

export const AddItems = styled.div`
  .section-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    align-items: center;
  }

  .add-custom-item-cta {
    font-weight: 600;
    font-size: 14px;
    line-height: 150%;
    color: #2a86f3;
    cursor: pointer;
  }

  .search-placeholder {
    display: flex;
    align-items: center;
    padding: 8px 10px;
    gap: 4px;
    background: #f6f6f6;
    color: #4f5663;
    font-weight: 400;
    font-size: 10px;
    line-height: 150%;
    cursor: pointer;
    letter-spacing: 0.5px;
  }
`;

export const Scroll = styled.div`
  max-height: 250px;
  overflow: scroll;

  &::-webkit-scrollbar {
    -webkit-appearance: none;
    width: 4px;
  }

  &::-webkit-scrollbar-thumb {
    background-color: #055bea;
  }

  &::-webkit-scrollbar-track:vertical {
    background: #f6f6f7;
  }
`;

export const OrderSummary = styled.div`
  max-height: 296px;
`;

export const LineItemWrapper = styled.div`
  display: flex;
  justify-content: space-between;
  margin: 16px 0;
  margin-right: 10px;
`;

export const Product = styled.div`
  display: flex;
  gap: 8px;
`;

export const ProductDetails = styled.div`
  display: flex;
  flex-direction: column;
`;

export const ProductImage = styled.img`
  height: 95px;
  width: 78px;
  object-fit: cover;
`;

export const ProductName = styled.div`
  color: #171a1e;
  font-weight: 600;
  font-size: 14px;
  line-height: 130%;
  margin-bottom: 8px;
  text-transform: capitalize;
`;

export const ProductDetail = styled.div`
  font-weight: 400;
  font-size: 12px;
  line-height: 100%;
  margin-bottom: 8px;
  text-transform: capitalize;
`;

export const ProductQuantity = styled.div`
  font-weight: 500;
  font-size: 12px;
  line-height: 130%;
  color: #171a1e;
  flex-grow: 1;
  display: flex;
  align-items: center;
  gap: 10px;
`;

export const ProductPrice = styled.div`
  font-weight: 500;
  font-size: 14px;
  color: #171a1e;
  line-height: 100%;
`;

export const PaymentBreakup = styled.div`
  ul {
    margin: 0;
    padding: 0;
    list-style: none;
  }

  li {
    display: flex;
    justify-content: space-between;
    margin: 8px 0;
  }

  hr {
    opacity: 0.1;
    border-top: 1px dashed #000;
    margin: 12px 0;
  }

  li:last-child {
    margin-bottom: 0;
  }
`;

export const ModalHeader = styled.div`
  background: #fff;
  color: black;
  padding: 14px 16px;
`;

export const CloseButton = styled.div`
  color: black;
  opacity: inherit;
`;

export const QuantityWrapper = styled.div`
  display: flex;
  gap: 12px;
  align-items: center;
  justify-content: center;
`;

export const CircleButton = styled.button`
  font-weight: 500;
  border: none;
  border-radius: 50%;
  height: 24px;
  width: 24px;
  font-size: 18px;
  display: flex;
  justify-content: center;
  align-items: center;
  background: #fff;
  color: #414449;

  &:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
`;

export const ModalContent = styled.div`
  background: #fff;
  width: 100%;
  padding: 20px 16px;

  hr {
    opacity: 0.1;
    border-top: 1px dashed #000;
    margin: 12px 0;
  }
`;

export const CircleButtonIcon = styled.img`
  height: 14px;
  width: 14px;
  display: flex;
  justify-content: center;
  align-items: center;
  margin: auto;
`;

export const OfferOutline = styled.i`
  font-size: 14px;
  color: #1ac635;
`;

export const CtaContainer = styled.div`
  display: flex;
  gap: 16px;
  justify-content: flex-end;
  flex-wrap: wrap;

  button {
    font-weight: 600;
    font-size: 14px;
    line-height: 150%;
    padding: 12px 16px;
    cursor: pointer;
    border: none;
    width: 150px;
  }

  .primary-cta {
    background: #2a86f3;
    color: #fafbfc;
  }

  .secondary-cta {
    background: #fff;
    border: 1px solid #2a86f3;
    color: #2a86f3;
  }

  button:disabled {
    background: #d9d9d9;
    color: rgba(65, 68, 73, 0.8);
  }

  .w-100 {
    width: 100%;
  }
`;

export const FormWrapper = styled.div`
  margin-top: 16px;
  padding: 0;
`;

export const FormInput = styled.input`
  background: #fff;
  border: 1px solid rgba(169, 179, 194, 0.29);
  padding: 12px 10px;
`;

export const FormLabel = styled.label`
  font-size: 14px;
  line-height: 130%;
  color: #171a1e;
  font-weight: 600;
`;

export const FormCheckboxInput = styled.input`
  height: 18px;
  width: 18px;
  margin-top: 0 !important;
`;

export const FormGroupWithCheckbox = styled.div`
  display: flex;
  gap: 8px;
`;

export const FormContent = styled.div`
  display: flex;
  align-items: baseline;
  gap: 42px;
`;

export const FormGroup = styled.div`
  display: flex;
  flex-direction: column;
  gap: 6px;
`;

export const ItemNameFormGroup = styled(FormGroup)`
  flex-grow: 3;
  flex-wrap: wrap;
`;

export const PriceFormGroup = styled(FormGroup)`
  flex-grow: 2;
`;

export const DiscountFieldsFormGroup = styled(FormGroup)`
  flex-grow: 1;
`;

export const PlaceholderImage = styled.div`
  height: 95px;
  width: 78px;
  background: #e7f0ff;
  display: flex;
  justify-content: center;
  align-items: center;
  font-weight: 600;
  font-size: 20px;
  line-height: 24px;
  color: #0b2144;
`;

export const SearchInput = styled.input`
  background: #f6f6f6;
  border: none;
  width: 100%;
`;

export const AddItemContainer = styled.div`
  background: #fff;
  width: 100%;
  padding: 20px 16px;
  display: flex;
  flex-direction: column;
`;

export const SearchItemWrapper = styled.div`
  display: flex;
  align-items: center;
`;

export const AvailableItem = styled.div`
  flex-grow: 3;
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
`;

export const ItemImage = styled.img`
  height: 40px;
  width: 40px;
  object-fit: cover;
`;

export const ItemTotalQuantity = styled.div`
  font-weight: 500;
  font-size: 12px;
  line-height: 130%;
  color: #171a1e;
  flex-grow: 1;
`;

export const ItemPrice = styled.div`
  flex-grow: 1;
  font-weight: 500;
  font-size: 14px;
  line-height: 130 %;
  color: #171a1e;
`;

export const ItemQuantity = styled.div`
  flex-grow: 1;
  text-align: end;
`;

export const ProductList = styled.div`
  height: 256px;
  overflow: auto;
  margin: 16px 0;
  max-height: inherit !important;
  flex-grow: 1;
`;

export const SearchItemContainer = styled.div`
  hr {
    opacity: 0.1;
    border-top: 1px dashed #000;
    margin: 12px 0;
  }

  &:last-child hr {
    display: none;
  }
`;

export const SearchIcon = styled.img`
  height: 14px;
  width: 14px;
  display: flex;
  justify-content: center;
  align-items: center;
`;

export const SearchBox = styled.div`
  display: flex;
  background: #f6f6f6;
  padding: 12px 18px;
  border: none;
  margin: 16px 0;
  align-items: center;
  gap: 8px;
  width: 100%;
`;

export const EditReasonWrapper = styled.div`
  flex-grow: 20;
  display: flex;
  flex-direction: column;
  background: #fff;
  padding: 16px;
`;
export const CommentBoxWrapper = styled.div`
  margin-top: 8px;
  flex-grow: 1;
`;

export const CommentBox = styled.textarea`
  width: 100%;
  height: 100%;
  resize: none;
  border: none;
  background: #f6f6f7;
  padding: 12px;
`;

export const StrikedPrice = styled.div`
  margin-top: 12px;
  text-align: end;
  text-decoration: line-through;
  font-size: 12px;
  color: #414449;
`;

export const CustomItemCheckBoxWrapper = styled.div`
  display: flex;
  justify-content: start;
  gap: 32px;
  align-items: center;
`;

export const PaymentBreakupSubtext = styled.span`
  margin-left: 4px;
  font-weight: 300;
  font-size: 10px;
`;

export const SmallText = styled.div`
  font-size: 10px;
`;

export const ActionToolbarWrapper = styled.div`
  display: flex;
  justify-content: center;
`;
