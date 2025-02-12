import React, { useState } from 'react';
import { ModalContent } from 'common/new-ui/Modal';
import { closeModal, notifySuccess, notifyError } from 'razorx/components/Modal';
import Form from 'razorx/components/ui/Form';
import Field from 'razorx/components/ui/Field';
import { splitzFetch } from 'razorx/helpers/fetch';

const ExperimentOwnersForm = ({ data, onEdit, header }) => {
  const [inputEmail, setInputEmail] = useState('');
  const [owners, setOwners] = useState([...data.metadata.additional_owners]);
  const [isEdited, setIsEdited] = useState(false);

  const handleEmailChange = (e) => {
    setInputEmail(e.target.value);
  };

  const handleAddOwner = () => {
    if (inputEmail.trim() !== '') {
      if (owners.includes(inputEmail.trim())) {
        notifyError('This email is already an additional owner');
      } else {
        setOwners([...owners, inputEmail.trim()]);
        setInputEmail('');
      }
    }
    setIsEdited(true);
  };

  const handleRemoveOwner = (index) => {
    const updatedOwners = owners.filter((_, i) => i !== index);
    setOwners(updatedOwners);
    setIsEdited(true);
  };

  const handleSubmit = () => {
    if (owners.length < 1) {
      notifyError('Cannot remove all owners, please add at least one additional owner');
      return;
    }

    if (!isEdited) {
      notifyError('Please add or remove at least one additional owner');
      return;
    }

    const experimentPayload = {
      id: data.id,
      additional_owners: owners,
    };

    splitzFetch({ url: 'experiment.v1.ExperimentAPI/Action', data: experimentPayload })
      .then(() => {
        notifySuccess('Experiment owners have been successfully added');
        closeModal();

        onEdit();
      })
      .catch((err) => {
        notifyError(err);
      });
  };

  return (
    <ModalContent className="modal-features" header={header}>
      <Form onSubmit={handleAddOwner}>
        <div>
          {owners.map((owner, index) => (
            <div key={`additionalOwner_${owner}`}>
              {owner}
              <button type="button" onClick={() => handleRemoveOwner(index)}>
                Remove
              </button>
            </div>
          ))}
          <div>
            <Field
              type="email"
              label="Owner Email"
              placeholder="someone@razorpay.com"
              value={inputEmail}
              onChange={handleEmailChange}
            />
            <button type="submit">Add Owner</button>
          </div>
          <button className="btn btn--primary" type="button" onClick={handleSubmit}>
            Submit
          </button>
        </div>
      </Form>
    </ModalContent>
  );
};

export default ExperimentOwnersForm;
