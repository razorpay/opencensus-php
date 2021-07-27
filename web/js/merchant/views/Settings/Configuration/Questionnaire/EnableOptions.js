import React from 'react';
import Form from 'common/new-ui/Form';
import { Modal, ModalContent } from 'common/new-ui/Modal';
import Button from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';

const Options = ({ closeModal }) => {
  return (
    <Modal onClose={closeModal} className="enable_pg">
      <ModalContent>
        <h4>Enable International Payments</h4>
        <p>
          We would need some details about your business. How would you like to accept international
          payments.
        </p>

        <Form onSubmit={(formData) => console.log(formData)}>
          <Input.Check required name="pg" fieldLabel="On Payments Gateway" checked={true} />
          <Input.Check
            required
            name="prod_v2"
            fieldLabel="On Other Products"
            description="Payment Pages, Payment Links & Invoices"
          />
          <Button.Primary type="submit">Provide Details</Button.Primary>
        </Form>
      </ModalContent>
    </Modal>
  );
};

export default Options;
