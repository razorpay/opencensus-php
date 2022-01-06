import Input from 'common/new-ui/Input';
import { states } from 'merchant/helpers/data';
import { excludeFor_Indiv } from 'merchant/components/Activation/ActivationUtils';

const stateOptions = ['--Select--'].concat(
  Object.keys(states).map((c) => {
    return {
      name: c,
      label: states[c],
    };
  }),
);

export default [
  [
    {
      name: 'business_registered_address',
      label: 'Address',
      placeholder: 'Enter Street Address',
      _cmp: Input.Textarea,
      onBlur: function onBlur(e, error) {
        this.sendInputToSegment({
          'Field Name': 'Address',
          'Field Type': 'Text',
          'Tab Title': 'Business Details',
          Mandatory: 'Yes',
        });
        this.sendErrorMessageToSegment(e, error);
      },
    },
    {
      name: 'business_registered_pin',
      label: 'Pincode',
      size: 'small',
      maxLength: '6',
      validator: isPinValid,
      onBlur: function onBlur(e, error) {
        this.sendErrorMessageToSegment(e, error);
        this.sendInputToSegment({
          'Field Name': 'Pincode',
          'Field Type': 'Text',
          'Tab Title': 'Business Details',
          Mandatory: 'Yes',
        });
      },
    },
    {
      name: 'business_registered_city',
      label: 'City',
      _autoRenderImpure: true, // Re-evaluate errors if pincode is updated
      onBlur: function onBlur(e, error) {
        this.sendErrorMessageToSegment(e, error);
        this.sendInputToSegment({
          'Field Name': 'City',
          'Field Type': 'Text',
          'Tab Title': 'Business Details',
          Mandatory: 'Yes',
        });
      },
    },
    {
      name: 'business_registered_state',
      label: 'State',
      _cmp: Input.Select,
      options: stateOptions,
      _autoRenderImpure: true, // Re-evaluate errors if pincode is updated
      onBlur: function onBlur(e, error) {
        this.sendErrorMessageToSegment(e, error);
        this.sendInputToSegment({
          'Field Name': 'State',
          'Field Type': 'Text',
          'Tab Title': 'Business Details',
          Mandatory: 'Yes',
        });
      },
    },
  ],
  {
    _name: 'same_address',
    fieldLabel: 'Operational Address same as above',
    description: 'Physical Verification may take place at this address',
    _cmp: Input.Check,
    _when: excludeFor_Indiv,
    onBlur: function onBlur(e, error) {
      this.sendErrorMessageToSegment(e, error);
      this.sendCheckboxToSegment({
        'Checkbox Label': 'Operational Address same as above',
        'Option Selected': e.target.value,
        'Element Type': 'Form',
        Mandatory: 'No',
      });
    },
  },
  [
    {
      name: 'business_operation_address',
      placeholder: 'Enter Street Address',
      label: 'Operational Address',
      _cmp: Input.Textarea,
      _when: differentAddress,
      onBlur: function onBlur(e, error) {
        this.sendErrorMessageToSegment(e, error);
        this.sendInputToSegment({
          'Field Name': 'Enter Street Address',
          'Field Type': 'Text',
          'Tab Title': 'Business Details',
          Mandatory: 'Yes',
        });
      },
    },
    {
      name: 'business_operation_pin',
      label: 'Pincode',
      size: 'small',
      maxLength: '6',
      validator: isPinValid,
      _when: differentAddress,
      onBlur: function onBlur(e, error) {
        this.sendErrorMessageToSegment(e, error);
        this.sendInputToSegment({
          'Field Name': 'Pincode',
          'Field Type': 'Text',
          'Tab Title': 'Business Details',
          Mandatory: 'Yes',
        });
      },
    },
    {
      name: 'business_operation_city',
      label: 'City',
      _autoRenderImpure: true, // Re-evaluate errors if pincode is updated
      _when: differentAddress,
      onBlur: function onBlur(e, error) {
        this.sendErrorMessageToSegment(e, error);
        this.sendInputToSegment({
          'Field Name': 'City',
          'Field Type': 'Text',
          'Tab Title': 'Business Details',
          Mandatory: 'Yes',
        });
      },
    },
    {
      name: 'business_operation_state',
      label: 'State',
      _cmp: Input.Select,
      options: stateOptions,
      _autoRenderImpure: true, // Re-evaluate errors if pincode is updated
      _when: differentAddress,
      onBlur: function onBlur(e, error) {
        this.sendErrorMessageToSegment(e, error);
        this.sendInputToSegment({
          'Field Name': 'State',
          'Field Type': 'Text',
          'Tab Title': 'Business Details',
          Mandatory: 'Yes',
        });
      },
    },
  ],
];

/* Show fields if same_address is not ticked */
function differentAddress(activation) {
  return activation.state.same_address === '0';
}

function isPinValid(value) {
  const pin = Number(value);
  if (!pin || pin < 100000 || pin > 999999) {
    return 'Please enter 6 digit pincode';
  }
  return 0;
}
