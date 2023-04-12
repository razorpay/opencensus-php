import React from 'react';
import { CollapsibleFormPropsType, CollapsibleFormSectionPropTypes } from './types';

import { Form } from './components/Form';
import { Section } from './components/Section';

// final exports
export const CollapsibleForm = (props: CollapsibleFormPropsType) => <Form {...props} />;
export const CollapsibleFormSection = (props: CollapsibleFormSectionPropTypes) => (
  <Section {...props} />
);
