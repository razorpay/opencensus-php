/*
*
* This is just the example file for UI schema for exhaustive set of supported fields
*
* */
const UISCHEMA = [
  {
    name: 'name',
    type: 'string',
    title: 'Customer Name',
    pattern: '^([a-zA-Z]+ ?)*$',
    minLength: '5',
    maxLength: '10',
    required: true,
    description: 'This is the help text of field, present under Input field',
    options: {
      // Optional keyword
      // cmp: 'Input' // Default field if cmp not present
      value: 'Initialy dummy name', // keyword dynamically inserted if we have seeding data. In case.
    },
  },
  {
    name: 'phone',
    type: 'number',
    title: 'Customer Contact',
    // pattern: '^([0-9]){8,}$', // Pattern restricts typing, so even if valid patter, it will not allow user to type anything
    minLength: 8,
    options: {
      // cmp: 'Input' // Default field for any component of type:string/number/integer is Input
      icon: {
        before: 'i-phone',
      },
    },
  },
  {
    name: 'amount',
    type: 'number',
    title: 'Amount',
    minimum: '1', // Can be anything (>0) technically
    maximum: '50000000', // Could be user defined max(technically)/ default for amount that we support
    pattern: '^[1-9]+(.([0-9]){1,2})?$',
    options: {
      // cmp: 'Input' // Default field for any component of type:string/number/integer is Input
      padded_text: {
        before: '₹',
      },
    },
  },
  {
    name: 'field_1',
    type: 'number',
    title: 'Some Counter Field',
    minimum: '2', // Optional
    maximum: '4', // Required keyword, Product wise defines Quantity
    options: {
      cmp: 'Counter', // type:number can be represented as Input.Counter component
    },
  },
  {
    name: 'field_1',
    type: 'number',
    title: 'Dropdown with value as labels',
    enum: [0, 1, 4], // Empty value shouldn't be allowed. First empty value is auto inserted from UI.
    options: {
      cmp: 'Select', // Default field for type:enum is Select
      value: 4,
    },
  },
  {
    name: 'field_3',
    type: 'string',
    title: 'Dropdown with custom Labels',
    enum: ['option_0', 'option_1', 'option_2'],
    options: {
      cmp: 'Select', // Default field for type:enum is Select
      enum_labels: ['Option Label 0', 'Option Label 1', 'Option Label 2'],
      // value: 'option_2',
    },
  },
];

export default UISCHEMA;
