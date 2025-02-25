import Input from 'common/new-ui/Input';
import ModalHeader from 'common/ui/ModalHeader';
import React from 'react';

export default function ChooseEmail({
  closeModal,
  selectedEmails,
  emails,
  onChange,
}) {
  return (
    <div>
      <ModalHeader title="Choose Email" onCloseClick={closeModal} />
      <div className="modal-body">
        <p className="text-muted">
          Select email addresses from below to which you want to send the
          reports.
        </p>
        <div>
          {emails.map(email => (
            <Input.Check
              key={email}
              fieldLabel={email}
              name={email}
              defaultValue={selectedEmails.includes(email)}
              onChange={onChange}
            />
          ))}
        </div>
      </div>
    </div>
  );
}
