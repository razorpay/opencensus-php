import Input from 'component/Input';
import { states } from 'rzp/utils/constants';

const INDIVIDUAL = 2;

var stateOptions = ['--Select--'].concat(
  Object.keys(states).map(c => {
    return {
      name: c,
      label: states[c],
    };
  })
);

function excludeFor_Indiv(activation) {
  const currentBusinessType =
    activation.state.dirty.business_type || activation.props.data.business_type;

  return [INDIVIDUAL].indexOf(Number(currentBusinessType)) === -1;
}

export default [
  [
    {
      name: 'business_registered_address',
      label: 'Registered Address',
      _cmp: Input.Textarea,
      dynamicLabel: true,
      getLabel: condition => {
        return condition ? 'Address' : 'Registered Address';
      },
    },
    {
      name: 'business_registered_pin',
      label: 'Pincode',
      size: 'small',
      maxLength: '6',
      validator: isPinValid,
    },
    {
      name: 'business_registered_city',
      label: 'City',
      _autoRenderImpure: true, // Re-evaluate errors if pincode is updated
    },
    {
      name: 'business_registered_state',
      label: 'State',
      _cmp: Input.Select,
      options: stateOptions,
      _autoRenderImpure: true, // Re-evaluate errors if pincode is updated
    },
  ],
  {
    _name: 'same_address',
    fieldLabel: 'Operational Address same as above',
    description: 'Physical Verification may take place at this address',
    _cmp: Input.Check,
    _when: excludeFor_Indiv,
  },
  [
    {
      name: 'business_operation_address',
      // placeholder: 'Enter Street Address',
      label: 'Operational Address',
      _cmp: Input.Textarea,
      _when: differentAddress,
    },
    {
      name: 'business_operation_pin',
      label: 'Pincode',
      size: 'small',
      maxLength: '6',
      validator: isPinValid,
      _when: differentAddress,
    },
    {
      name: 'business_operation_city',
      label: 'City',
      _autoRenderImpure: true, // Re-evaluate errors if pincode is updated
      _when: differentAddress,
    },
    {
      name: 'business_operation_state',
      label: 'State',
      _cmp: Input.Select,
      options: stateOptions,
      _autoRenderImpure: true, // Re-evaluate errors if pincode is updated
      _when: differentAddress,
    },
  ],
];

/* Show fields if same_address is not ticked */
function differentAddress(activation) {
  return activation.state.same_address === '0';
}

function isPinValid(value) {
  let pin = Number(value);
  if (!pin || pin < 100000 || pin > 999999) {
    return 'Please enter 6 digit pincode';
  }
}
