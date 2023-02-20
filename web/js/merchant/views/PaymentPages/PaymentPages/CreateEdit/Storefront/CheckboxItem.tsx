import React from 'react';
import { Text } from '@razorpay/blade/components';
import { CheckboxContent, CheckboxLeftContent, CheckboxRightContent } from './styled';
import { ProductImage } from 'merchant/reducers/paymentPages/storefront';
import { getPrimaryImage } from './utils';
import Input from 'common/new-ui/Input';

interface ICheckboxItem {
  checked: boolean;
  id: string;
  disabled: boolean;
  product_name: string;
  images: Array<ProductImage>;
  amount: string;
  discounted_amount: string;

  onChange: (data: any) => void;
}

const CheckboxItem = ({
  checked,
  id,
  disabled,
  product_name,
  images,
  amount,
  discounted_amount,
  onChange,
}: ICheckboxItem): React.ReactElement => {
  const image = getPrimaryImage(images);
  return (
    <Input.Check
      name={id}
      key={id}
      autoRender
      fieldLabel={
        <CheckboxContent>
          <CheckboxLeftContent>
            <img src={image} alt={`${product_name} image`} width={28} height={28} />
            {product_name}
          </CheckboxLeftContent>
          <CheckboxRightContent isDiscounted={!!discounted_amount}>
            <Text
              type={discounted_amount ? 'muted' : 'normal'}
              variant="body"
              size="medium"
              weight="regular"
              contrast="low"
            >
              {amount}
            </Text>
            {discounted_amount && (
              <Text type="normal" variant="body" size="medium" weight="regular" contrast="low">
                {discounted_amount}
              </Text>
            )}
          </CheckboxRightContent>
        </CheckboxContent>
      }
      className="spacing"
      onChange={onChange}
      checked={checked}
      disabled={disabled}
    />
  );
};

export default CheckboxItem;
