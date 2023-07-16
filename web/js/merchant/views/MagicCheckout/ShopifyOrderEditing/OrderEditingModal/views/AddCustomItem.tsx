import React, { useState, useContext } from 'react';

// UI imports
import QuantityCounter from 'merchant/views/MagicCheckout/ShopifyOrderEditing/OrderEditingModal/components/QuantityCounter';
import {
  CtaContainer,
  FormContent,
  FormGroup,
  FormWrapper,
  FormLabel,
  FormGroupWithCheckbox,
  FormCheckboxInput,
  ItemNameFormGroup,
  ModalContent,
  PriceFormGroup,
  Title,
  CustomItemCheckBoxWrapper,
} from 'merchant/views/MagicCheckout/ShopifyOrderEditing/styled';
import Input from 'common/new-ui/Input';

// Util / Constants imports
import { ModalContext } from 'merchant/views/MagicCheckout/ShopifyOrderEditing/context';
import { ORDER_EDITING_SUBTABS } from 'merchant/views/MagicCheckout/ShopifyOrderEditing/constants';

// Type imports
import { ShowNotificationType } from 'common/typings';

// API imports
import { addCustomLineItem } from 'merchant/views/MagicCheckout/ShopifyOrderEditing/api';

interface FormData {
  name: string;
  price: string;
  quantity: number;
  requires_shipping: boolean;
  taxable: boolean;
}

interface AddCustomItemViewProps {
  showNotification: ShowNotificationType;
}

const initialState: FormData = {
  name: '',
  price: '',
  quantity: 1,
  requires_shipping: false,
  taxable: false,
};

const AddCustomItemView: React.FC<AddCustomItemViewProps> = ({ showNotification }) => {
  const [formData, setFormData] = useState<FormData>(initialState);
  const { setView, edit_id, setOrder } = useContext(ModalContext);
  const [isLoading, setLoading] = useState(false);

  const handleQuantityChange = (value: number) => {
    setFormData((prevFormData) => ({
      ...prevFormData,
      quantity: value,
    }));

    // this true is required to update the quantity  and is consumed in QuantityCounter Component
    return true;
  };

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    // eslint-disable-next-line @typescript-eslint/naming-convention
    const { name, value, type, checked } = e.target;
    const newValue = type === 'checkbox' ? checked : value;

    setFormData((prevFormData) => ({
      ...prevFormData,
      [name]: newValue,
    }));
  };

  const handleReset = () => {
    setFormData(initialState);
    setView(ORDER_EDITING_SUBTABS.DEFAULT);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    try {
      const response = await addCustomLineItem(edit_id, formData);
      setOrder(response.data.edited_order);
      handleReset();
    } catch (error: any) {
      const errorMessage = error.errors || 'Something went wrong';
      showNotification({
        type: 'error',
        message: errorMessage,
      });
    } finally {
      setLoading(false);
    }
  };

  return (
    <ModalContent>
      <Title>Add custom item</Title>
      <FormWrapper>
        <FormContent>
          <ItemNameFormGroup>
            <FormLabel htmlFor="name">Item name*</FormLabel>
            <Input
              name="name"
              type="text"
              id="custom-item-name"
              required
              value={formData.name}
              onChange={handleInputChange}
            />
          </ItemNameFormGroup>
          <PriceFormGroup>
            <FormLabel htmlFor="price">Price*</FormLabel>
            <Input
              name="price"
              type="number"
              id="price"
              required
              value={formData.price}
              onChange={handleInputChange}
            />
          </PriceFormGroup>

          <FormGroup>
            <FormLabel htmlFor="quantity">Quantity</FormLabel>
            <QuantityCounter initialState={1} onQuantityChange={handleQuantityChange} />
          </FormGroup>
        </FormContent>

        <br />

        <CustomItemCheckBoxWrapper>
          <FormGroupWithCheckbox>
            <FormCheckboxInput
              type="checkbox"
              id="shipping-charges"
              name="requires_shipping"
              checked={formData.requires_shipping}
              onChange={handleInputChange}
            />
            <FormLabel htmlFor="shipping-charges">Shipping Charges</FormLabel>
          </FormGroupWithCheckbox>

          <FormGroupWithCheckbox>
            <FormCheckboxInput
              type="checkbox"
              id="taxable"
              name="taxable"
              checked={formData.taxable}
              onChange={handleInputChange}
            />
            <FormLabel htmlFor="taxable">Taxable</FormLabel>
          </FormGroupWithCheckbox>
        </CustomItemCheckBoxWrapper>

        <hr />

        <CtaContainer>
          <button onClick={handleReset} className="secondary-cta">
            Cancel
          </button>
          <button
            onClick={handleSubmit}
            className="primary-cta"
            disabled={
              !formData.name || !Number(formData.price) || formData.quantity < 1 || isLoading
            }
          >
            Add Items
          </button>
        </CtaContainer>
      </FormWrapper>
    </ModalContent>
  );
};

export default AddCustomItemView;
