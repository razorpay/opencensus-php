import React from 'react';
import { Story, Meta } from '@storybook/react/types-6-0.d';
import Button from '@razorpay/blade-old/src/atoms/Button';
import Modal, { ModalPropsT } from './Modal';
import { ModalBody, ModalHeader, ModalFooter } from './Styled';

export default {
  title: 'Modal',
  component: Modal,
} as Meta;

// eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
export const DefaultModal = () => {
  const [isOpen, setIsOpen] = React.useState(false);
  return (
    <>
      <Button onClick={() => setIsOpen(true)}> Open modal</Button>
      <Modal isOpen={isOpen} onClose={() => setIsOpen(false)}>
        <ModalHeader> Default Modal Header </ModalHeader>
        <ModalBody>
          Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has
          been the industry&apos;s standard dummy text ever since the 1500s, when an unknown printer
          took a galley of type and scrambled it to make a type specimen book. It has survived not
          only five centuries, but also the leap into electronic typesetting, remaining essentially
          unchanged
        </ModalBody>
        <ModalFooter>
          <Button variant="tertiary"> Cancel</Button>
          <Button onClick={() => setIsOpen(false)}> Okay </Button>
        </ModalFooter>
      </Modal>
    </>
  );
};

export const BottomSheet: React.FC = () => {
  const [isOpen, setIsOpen] = React.useState(true);
  return (
    <>
      <Button onClick={() => setIsOpen(true)}> Open modal</Button>
      <Modal bottomsheet={true} isOpen={isOpen} onClose={() => setIsOpen(false)}>
        <ModalBody>
          Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has
          been the industry&apos;s standard dummy text ever since the 1500s, when an unknown printer
          took a galley of type and scrambled it to make a type specimen book. It has survived not
          only five centuries, but also the leap into electronic typesetting, remaining essentially
          unchanged
        </ModalBody>
      </Modal>
    </>
  );
};

const Template: Story<ModalPropsT> = (args) => <Modal {...args} />;

export const ModalWithControls = Template.bind({});

ModalWithControls.args = {
  children: (
    <>
      <ModalHeader> Default Modal Header </ModalHeader>
      <ModalBody>
        Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has
        been the industry&apos;s standard dummy text ever since the 1500s, when an unknown printer
        took a galley of type and scrambled it to make a type specimen book. It has survived not
        only five centuries, but also the leap into electronic typesetting, remaining essentially
        unchanged
      </ModalBody>
      <ModalFooter> Default Modal Footer</ModalFooter>
    </>
  ),
  isOpen: true,
};
