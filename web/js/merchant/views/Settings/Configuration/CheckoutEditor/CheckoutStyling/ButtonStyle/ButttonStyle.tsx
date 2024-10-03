import React from 'react';
import { Text } from '@razorpay/blade/components';

import { RoundedIcon } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/ButtonStyle/icons/RoundedIcon';
import { SharpIcon } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/ButtonStyle/icons/SharpIcon';
import {
  RightChildrenWrapper,
  SingleChildren,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/ButtonStyle/styled';
import { AVAILABLE_BORDER_STYLE } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/constants/DefaultValue';
import LineItems from 'merchant/views/Settings/Configuration/components/Configuration/LineItems';

import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context';

import { CHECKOUT_EDITOR_FIELDS } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import {
  ButtonStyleItem,
  RightChildrenProps,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types';

const BORDER_TITLE = 'Border style';

const BORDER_STYLE_ITEMS: ButtonStyleItem[] = [
  {
    name: AVAILABLE_BORDER_STYLE.ROUNDED,
    icon: <RoundedIcon />,
  },
  {
    name: AVAILABLE_BORDER_STYLE.SHARP,
    icon: <SharpIcon />,
  },
];

const RightChildren: React.FC<RightChildrenProps> = ({
  buttons,
  selectedButton,
  handleButtonStyleChange,
}) => {
  return (
    <RightChildrenWrapper>
      {buttons.map((button) => {
        const isSelected = selectedButton === button.name;

        return (
          <SingleChildren
            key={button.name}
            isSelected={isSelected}
            onClick={() => handleButtonStyleChange(button.name)}
          >
            {React.cloneElement(button.icon, { selectedButton })}
            <Text>{button.name}</Text>
          </SingleChildren>
        );
      })}
    </RightChildrenWrapper>
  );
};

const ButtonStyle = () => {
  const { values, handleButtonStyleChange } = useCheckoutEditor();

  return (
    <LineItems
      title={BORDER_TITLE}
      rightChildren={
        <RightChildren
          buttons={BORDER_STYLE_ITEMS}
          selectedButton={values[CHECKOUT_EDITOR_FIELDS.BORDER_STYLE]}
          handleButtonStyleChange={handleButtonStyleChange}
        />
      }
    />
  );
};

export default ButtonStyle;
