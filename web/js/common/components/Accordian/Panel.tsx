import Icon from '@razorpay/blade-old/src/atoms/Icon';
import Size from '@razorpay/blade-old/src/atoms/Size';
import View from '@razorpay/blade-old/src/atoms/View';
import React, { ReactNode } from 'react';
import { Content, Header, PanelContainer } from './Styled';
/* 
 Future additions 
  - Aria controls 
  - Accessibility 
*/
export interface PanelPropsT {
  disabled?: boolean;
  expanded?: boolean;
  onChange?: () => void;
  onClick?: (e: Event) => void;
  title?: ReactNode;
  children: ReactNode;
  _ref?: ReactNode;
}

const Panel: React.FC<PanelPropsT> = ({
  disabled,
  expanded,
  onChange,
  title,
  children,
  onClick,
  _ref,
}) => {
  const _onClick = (e: Event) => {
    if (disabled) {
      return;
    }
    if (typeof onChange === 'function') {
      onChange();
    }
    if (typeof onClick === 'function') {
      onClick(e);
    }
  };

  return (
    <PanelContainer>
      <Header
        ref={_ref}
        role="button"
        aria-expanded={expanded}
        aria-disabled={disabled || null}
        onClick={_onClick}
        $disabled={disabled}
      >
        <Size width="95%">
          <View>{title}</View>
        </Size>
        {expanded ? (
          <Icon name="chevronUp" size="medium" />
        ) : (
          <Icon name="chevronDown" size="medium" />
        )}
      </Header>
      <Content $expanded={expanded}>{expanded ? children : null}</Content>
    </PanelContainer>
  );
};

export default Panel;
