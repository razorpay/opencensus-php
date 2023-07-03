import React from 'react';

interface ActivationStatusFilterProps {
  input: { value: string; onChange: React.ChangeEventHandler };
}

export const activationStatusMenu = [
  { label: 'Select Option', name: '' },
  { label: 'Activated', name: 'activated' },
  { label: 'Rejected', name: 'rejected' },
  { label: 'Needs Clarification', name: 'needs_clarification' },
  { label: 'Under Review', name: 'under_review' },
  { label: 'Not Submitted', name: 'not_submitted' },
];

const ActivationStatusFilter = ({
  input: { value, onChange },
}: ActivationStatusFilterProps): JSX.Element => {
  return (
    <div>
      <select className="form-control input-sm" value={value} onChange={onChange}>
        {activationStatusMenu.map((o) => (
          <option key={o.name} value={o.name}>
            {o.label}
          </option>
        ))}
      </select>
    </div>
  );
};

export default ActivationStatusFilter;
