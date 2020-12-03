import React from 'react';
import { Story, Meta } from '@storybook/react/types-6-0.d';
import useActivation from '../hooks/useActivation';
import DocumentUpload from './index';

export default {
  title: 'Onboarding/DocumentUpload',
  component: DocumentUpload,
  decorators: [
    (StoryFn) => {
      const { status } = useActivation();
      if (status === 'loading') {
        return <div>Loading...</div>;
      }
      if (status === 'error') {
        return <div>Something went wrong.</div>;
      }
      return <StoryFn />;
    },
  ],
} as Meta;

const Template: Story = () => <DocumentUpload />;

export const Default = Template.bind({});
