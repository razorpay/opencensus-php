import React from 'react';

export const sourceTypesMenu = [
  { label: 'Select Options', name: '' },
  { label: 'Payment', name: 'payment' },
  { label: 'Refund', name: 'refund' },
];

const SourceTypeFilter = ({ input: { value, onChange } }) => {
  return (
    <div>
      <select className="form-control input-sm" value={value} onChange={onChange}>
        {sourceTypesMenu.map(({ name, label }) => (
          <option key={name} value={name}>
            {label}
          </option>
        ))}
      </select>
    </div>
  );
};

export default SourceTypeFilter;
