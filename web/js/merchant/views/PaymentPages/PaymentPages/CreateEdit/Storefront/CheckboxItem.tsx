import React from 'react';
import { Text } from '@razorpay/blade/components';
import { CheckboxContent, CheckboxLeftContent, CheckboxRightContent } from './styled';
import { ProductImage } from 'merchant/reducers/paymentPages/storefront';
import { getPrimaryImage } from './utils';
import Input from 'common/new-ui/Input';
import { shouldShowStrikethrough } from 'merchant/views/PaymentPages/common/Products/utils';

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
  const showStrikethrough = shouldShowStrikethrough(discounted_amount, amount);
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
          <CheckboxRightContent isDiscounted={showStrikethrough}>
            <Text
              variant="body"
              size="medium"
              weight="regular"
              color={showStrikethrough ? 'surface.text.gray.muted' : 'surface.text.gray.normal'}
            >
              {amount}
            </Text>
            {showStrikethrough && (
              <Text variant="body" size="medium" weight="regular" color="surface.text.gray.normal">
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
