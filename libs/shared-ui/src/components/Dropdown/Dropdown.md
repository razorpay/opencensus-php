# Shared Dropdown Component

This shared dropdown component is a simplified wrapper around the Blade Dropdown Component. It takes care of both Desktop and Mobile view by using DropdownOverlay and BottomSheet respectively.

## Table of Contents

- [Usage](#usage)
- [Props](#props)
- [Examples](#examples)

## Usage

To use the Dropdown component in your React components, import it as follows:

```jsx
import Dropdown from 'common/components/Dropdown';

const MyComponent = () => {
  const options = [
    { value: 'all', title: 'All' },
    { value: 'option1', title: 'Option 1' },
    { value: 'option2', title: 'Option 2' },
    { value: 'option3', title: 'Option 3' },
  ];

  const handleChange = (selectedOptions) => {
    // Your logic here...
  };

  return (
    <div>
      <h1>My Component</h1>
      <Dropdown
        options={options}
        onChange={handleChange}
        // Additional props (optional)
      />
    </div>
  );
};

export default MyComponent;
```

## Props

The shared Dropdown component accepts the following props:

- `selectionType` (string, optional) - `single`(default) | `multiple`.
- `options` (array) - An array of objects representing the options for the dropdown. Each object should have `value` and `title` properties.
- `defaultOptions` (array, optional) - An array of objects representing the options for the dropdown. Each object should have `value` and `title` properties, default is `[]`.
- `onChange` (function, optional) - A callback function that will be called with the selected option(s) whenever the selection changes. `selectedOptions` is just an "array of a single option" in case of `single selectionType` whereas `selectedOptions` is an "array of options" in case of `multiple selectionType`.
- `prefixTitle` (string, optional) - A prefix text to be displayed with the the Dropdown title, default is `''`.
- `isLink` (boolean, optional) - If set to `true`, the dropdown target will be blade DropdownLink, default is `false`.
- `isSelectInput` (boolean, optional) - If set to `true`, the dropdown target will be blade SelectInput, default is `false`.
- `selectInputName` (string, optional) - The name of the select input field. Useful in form submissions, default is `''`.
- `isDisabled` (boolean, optional) - If set to `true`, the dropdown will be disabled and users won't be able to interact with it, default is `false`.
- `withBottomSheet` (boolean, optional) - If set to `true`, the dropdown content will render in BottomSheet otherwise it will render in DropdownOverlay, default is `true` only for mobile.
- `bottomSheetTitle` (string, optional) - Title for the BottomSheetHeader, default is `''`.

## Examples

Here are some examples of how to use the shared Dropdown component:

```jsx
// Example 1: Basic usage with options
const options = [
  { value: 'all', title: 'All' },
  { value: 'option1', title: 'Option 1' },
  { value: 'option2', title: 'Option 2' },
  { value: 'option3', title: 'Option 3' },
  section: {
    name: "Section Name",
    options: [
        { value: 'option4', title: 'Option 4' },
        { value: 'option5', title: 'Option 5' },
    ],
  }
];

<Dropdown options={options} />;

// Example 2: Setting defaultOption and handling onChange with single selectionType
const defaultOption = options[0];
const [selectedOption, setSelectedOption] = useState(defaultOption);

const handleOptionChange = ([option]) => {
  setSelectedOption(option);
};

<Dropdown options={options} defaultOptions={[defaultOption]} onChange={handleOptionChange} />;

// Example 3: Setting defaultOptions and handling onChange with multiple selectionType
const defaultOptions = [
    { value: 'option3', title: 'Option 3' },
    { value: 'option4', title: 'Option 4' },
];
const [selectedOptions, setSelectedOptions] = useState(defaultOptions);

const handleOptionChange = (options) => {
  setSelectedOptions(options);
};

<Dropdown
  selectionType="multiple"
  options={options}
  defaultOptions={defaultOptions}
  onChange={handleOptionChange}
/>;
```
