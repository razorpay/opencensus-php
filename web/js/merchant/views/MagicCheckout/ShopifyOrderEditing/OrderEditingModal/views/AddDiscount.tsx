import React, { useState, useContext } from 'react';

// UI imports
import Input from 'common/new-ui/Input';
import {
  CtaContainer,
  FormContent,
  FormWrapper,
  FormLabel,
  ModalContent,
  Title,
  DiscountFieldsFormGroup,
} from 'merchant/views/MagicCheckout/ShopifyOrderEditing/styled';

// Util / Constants imports
import { ModalContext } from 'merchant/views/MagicCheckout/ShopifyOrderEditing/context';
import { ORDER_EDITING_SUBTABS } from 'merchant/views/MagicCheckout/ShopifyOrderEditing/constants';

// Type Imports
import { ShowNotificationType } from 'common/typings';

// API imports
import {
  addLineItemDiscount,
  removeLineItemDiscount,
} from 'merchant/views/MagicCheckout/ShopifyOrderEditing/api';

interface FormData {
  discountType: string;
  discount: string;
  reason: string;
}

interface AddDiscountProps {
  showNotification: ShowNotificationType;
}

interface AddDiscountPayload {
  fixed: number | null;
  percentage: number | null;
  currency: string;
  description: string;
  line_item_ids: string[];
}

const initialState: FormData = {
  discountType: 'amount',
  discount: '',
  reason: '',
};

const DISCOUNT_TYPES = [
  { label: 'Amount', name: 'amount' },
  { label: 'Percentage', name: 'percentage' },
];

const AddDiscount: React.FC<AddDiscountProps> = ({ showNotification }) => {
  const [formData, setFormData] = useState<FormData>(initialState);
  const [isLoading, setLoading] = useState(false);
  const { setView, lineItemEditId, edit_id, setOrder, customDiscountID } = useContext(ModalContext);

  const handleReset = () => {
    setFormData(initialState);
    setView(ORDER_EDITING_SUBTABS.DEFAULT);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);

    try {
      if (formData.discount === '0') {
        const response = await removeLineItemDiscount(edit_id, {
          discount_id: customDiscountID,
          edit_line_item_id: lineItemEditId,
        });
        setOrder(response.data.edited_order);
        handleReset();
      } else {
        let payload: AddDiscountPayload;
        if (formData.discountType === 'amount') {
          payload = {
            fixed: Number(formData.discount),
            percentage: null,
            currency: 'INR',
            description: formData.reason,
            line_item_ids: [lineItemEditId],
          };
        } else {
          payload = {
            fixed: null,
            percentage: Number(formData.discount),
            currency: 'INR',
            description: formData.reason,
            line_item_ids: [lineItemEditId],
          };
        }

        const response = await addLineItemDiscount(edit_id, payload);
        setOrder(response.data.edited_order);
        handleReset();
      }
    } catch (error: any) {
      showNotification({
        type: 'error',
        message: error.errors || 'Something went wrong',
      });
    } finally {
      setLoading(false);
    }
  };

  const handleInputChange = (name: string, value: string) => {
    setFormData((prevFormData) => ({
      ...prevFormData,
      [name]: value,
    }));
  };

  return (
    <ModalContent>
      <Title>Add Discount to New Year Gift Card</Title>
      <FormWrapper>
        <FormContent>
          <DiscountFieldsFormGroup>
            <FormLabel htmlFor="discount-type">Discount type*</FormLabel>
            <Input.Select
              id="discount-type"
              name="discount-type"
              options={DISCOUNT_TYPES}
              value={formData.discountType}
              onChange={(e: React.ChangeEvent<HTMLSelectElement>) =>
                handleInputChange('discountType', e.target.value)
              }
            />
          </DiscountFieldsFormGroup>

          <DiscountFieldsFormGroup>
            <FormLabel htmlFor="discount">Discount value*</FormLabel>
            <Input
              name="discount"
              type="number"
              required
              value={formData.discount}
              onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                handleInputChange('discount', e.target.value)
              }
            />
          </DiscountFieldsFormGroup>
        </FormContent>
        <br />
        <FormContent>
          <DiscountFieldsFormGroup>
            <FormLabel htmlFor="reason">Discount reason*</FormLabel>
            <Input
              name="reason"
              type="text"
              required
              value={formData.reason}
              onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                handleInputChange('reason', e.target.value)
              }
            />
          </DiscountFieldsFormGroup>
        </FormContent>
        <hr />
        <CtaContainer>
          <button onClick={handleReset} className="secondary-cta">
            Cancel
          </button>
          <button
            onClick={handleSubmit}
            className="primary-cta"
            disabled={!formData.discount || !formData.reason || isLoading}
          >
            Apply Discount
          </button>
        </CtaContainer>
      </FormWrapper>
    </ModalContent>
  );
};

export default AddDiscount;
