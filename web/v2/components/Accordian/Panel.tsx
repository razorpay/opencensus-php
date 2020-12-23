import React, { ReactNode } from 'react';
import Icon from '@razorpay/blade/src/atoms/Icon';
import { PanelContainer, Header, Content } from './Styled';
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
        {title}
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
