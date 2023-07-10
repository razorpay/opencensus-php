import React, { useState, Children, cloneElement, useEffect } from 'react';
import { CollapsibleFormPropsType } from 'merchant_common/views/Reports/components/types';
import { FormContainer } from 'merchant_common/views/Reports/components/CollapsibleForm/styled';

export const Form = ({
  children,
  defaultOpen,
  disableSectionsExpandOnError = true,
  errorSectionIndex,
  validationsForEachSections,
}: CollapsibleFormPropsType): JSX.Element => {
  const [activeSectionIndex, setActiveSectionIndex] = useState<number>(defaultOpen ?? -1);

  useEffect(() => {
    if (typeof errorSectionIndex === 'number') setActiveSectionIndex(errorSectionIndex);
  }, [errorSectionIndex]);

  const handleClick = (index: number) => {
    if (!disableSectionsExpandOnError) {
      setActiveSectionIndex(index);
    } else if (typeof errorSectionIndex === 'number') {
      if (validationsForEachSections) {
        const hasRefSectionErrorValidated = Boolean(validationsForEachSections[errorSectionIndex]);
        if (hasRefSectionErrorValidated) setActiveSectionIndex(index);
      }
    } else {
      setActiveSectionIndex(index);
    }
  };

  return (
    <FormContainer aria-label="Form">
      {Children.map(children, (child, index) => {
        const isActive = index === activeSectionIndex;
        const isErrorInSection = index === errorSectionIndex;
        const validate = () =>
          Boolean(validationsForEachSections && validationsForEachSections[index]);

        const isValidated = isErrorInSection ? validate() : true;
        const isComplete = validate();
        const isExpandDisabled = disableSectionsExpandOnError
          ? typeof errorSectionIndex === 'number' &&
            errorSectionIndex != index &&
            Boolean(validationsForEachSections && !validationsForEachSections[errorSectionIndex])
          : false;
        const onClick = (index?) => handleClick(index);

        return cloneElement(child, {
          index,
          isActive,
          isComplete,
          isExpandDisabled,
          isValidated,
          onClick,
        });
      })}
    </FormContainer>
  );
};
