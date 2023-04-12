import React from 'react';

export interface CollapsibleFormPropsType {
  children: JSX.Element[] | JSX.Element;
  /**
   * If passed with a section index, the section will be in expanded state by default.
   */
  defaultOpen?: number;
  /**
   * State to track error in a section.
   */
  errorSectionIndex?: number;
  /**
   * An array containing validation states of each section in `boolean`.
   */
  validationsForEachSections?: boolean[];
  /**
   * True by default. Disables other sections if there is an error in the ref section.
   */
  disableSectionsExpandOnError?: boolean;
  style?: React.CSSProperties | undefined;
}

export interface CollapsibleFormSectionPropTypes {
  children: unknown;
  /**
   * Title for each section.
   */
  title: string;
  helpText?: string;
  /**
   * If disabled, section cannot be expanded.
   */
  disabled?: boolean;
  endComponent?: {
    /**
     * A component to render at the end of section header.
     */
    component: () => JSX.Element;
    /**
     * When to show the endComponent.
     */
    visible: 'on-active' | 'on-close' | 'always';
  };
}

export interface CollapsibleFormSectionPrivateTypes extends CollapsibleFormSectionPropTypes {
  isExpandDisabled?: boolean;
  isActive?: boolean;
  isComplete?: boolean;
  isValidated?: boolean;
  onClick?: (x?) => void;
  index?: number;
}
