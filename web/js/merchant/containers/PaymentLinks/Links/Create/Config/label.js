/*
  Data map structure
  -----------------------------
  {
    defaultLabel: '', // Fallback
    alias: [
      {
        label: 'Reference Number', // Output label
        when: state => state.isPLReferenceNumberForReceiptEnabled // User experiment
      }
    ]
  }

  Label renaming reference
  -----------------------------
  is-PL-ReferenceNumber-Receipt-Enabled
  is - [PRODUCT]- [Changed label] - [Default Label] - Enabled
*/

const LABEL_MAP = {
  receipt: {
    defaultLabel: 'Receipt No.',
    alias: [
      {
        label: 'Reference Number',
        when: state => state.isPLReferenceNumberForReceiptEnabled,
      },
    ],
  },
  description: {
    defaultLabel: 'Payment For',
    alias: [
      {
        label: 'Policy/Vehicle Registration  Number',
        when: state => state.isPLPolicyRegistrationNumberForDescriptionsEnabled,
      },
    ],
  },
};

export default function getLabel(name, user) {
  const labelData = LABEL_MAP[name];

  if (!labelData) return name;

  let outputLabel = labelData.defaultLabel;

  labelData.alias.forEach(item => {
    if (item.when(user)) {
      outputLabel = item.label;
    }
  });

  return outputLabel;
}
