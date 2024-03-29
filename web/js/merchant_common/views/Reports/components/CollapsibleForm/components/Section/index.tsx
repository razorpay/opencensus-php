import React, { Children, useEffect } from 'react';
import { Text } from 'merchant_common/views/Reports/components';
import { useTheme } from 'merchant_common/views/Reports/hooks';
import { CollapsibleFormSectionPrivateTypes } from 'merchant_common/views/Reports/components/types';
import {
  CollapsibleFormChildWrapper,
  CollapsibleFormContent,
  CollapsibleFormContentWrapper,
  CollapsibleFormHeader,
  CollapsibleFormLabel,
  CollapsibleFormWrapper,
  FocusIndicator,
} from 'merchant_common/views/Reports/components/CollapsibleForm/styled';

export const Section = ({
  index,
  children,
  endComponent,
  helpText,
  title,
  isActive,
  isComplete,
  isExpandDisabled,
  isValidated,
  onClick,
  disabled,
}: CollapsibleFormSectionPrivateTypes): JSX.Element => {
  const { theme } = useTheme();

  const renderEndComponent = () => {
    if (endComponent?.visible) {
      switch (endComponent.visible) {
        case 'always':
          return endComponent.component();
        case 'on-active':
          return isActive && endComponent.component();
        case 'on-close':
          return !isActive && endComponent.component();
        default:
          return null;
      }
    }
    return null;
  };

  const handleClick = () => {
    if (!disabled && typeof onClick === 'function') {
      onClick(index);
    }
  };

  useEffect(() => {
    if (disabled && isActive) {
      onClick?.(-1);
    }
  }, [disabled, isActive]);

  return (
    <CollapsibleFormWrapper theme={theme} aria-label="Form Section" onClick={handleClick}>
      <FocusIndicator
        theme={theme}
        active={isActive}
        validation={disabled || isValidated}
        isComplete={!disabled && isComplete}
        disabled={disabled}
      />
      <CollapsibleFormContentWrapper
        validation={disabled || isValidated}
        theme={theme}
        active={isActive}
        isComplete={!disabled && isComplete}
        isExpandDisabled={isExpandDisabled || disabled}
      >
        <CollapsibleFormHeader>
          <CollapsibleFormLabel>
            <Text weight="semibold" variant="body" color="surface.text.gray.subtle">
              {title}
            </Text>
            <Text variant="body" size="small" color="surface.text.gray.muted">
              {helpText}
            </Text>
          </CollapsibleFormLabel>
          {renderEndComponent()}
        </CollapsibleFormHeader>

        {isActive && !disabled ? (
          <CollapsibleFormContent aria-label="Section Field Container" theme={theme}>
            {Children.map(
              children,
              (child: any, index: number) =>
                child && (
                  <CollapsibleFormChildWrapper key={index} theme={theme}>
                    {child}
                  </CollapsibleFormChildWrapper>
                ),
            )}
          </CollapsibleFormContent>
        ) : null}
      </CollapsibleFormContentWrapper>
    </CollapsibleFormWrapper>
  );
};
