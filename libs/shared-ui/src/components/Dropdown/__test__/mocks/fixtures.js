function createOption(idx) {
  return {
    title: `Option ${idx}`,
    value: `option${idx}`,
  };
}

export const option1 = createOption(1);
export const option2 = createOption(2);
export const option3 = createOption(3);
export const option4 = createOption(4);

export const defaultOptions = [option1, option2];

export const options = [...defaultOptions, option3];

export const actionListWrapperOptions = [
  ...defaultOptions,
  { section: { name: 'Section 1', options: [option3] } },
];

export const utils = {
  getDropdownTarget: {
    defaultProps: {
      isLink: true,
      selectInputName: '',
      dropdownCommonProps: { isDisabled: false },
      dropdownTitle: 'Dropdown Title',
      defaultOptions: [],
    },
  },
  getDropdownContent: {
    isMultipleSelection: true,
    selectedOptions: [],
    tempSelectedOptions: [],
    onOptionClick: () => {},
    onClear: () => {},
    onApply: () => {},
    options: [],
    isWithBottomSheet: true,
    bottomSheetTitle: 'Bottom Sheet Title',
    isDropdownOpen: true,
  },
  getAllOptions: {
    options: [
      ...defaultOptions,
      {
        section: {
          label: 'Section 1',
          options: [option3, option4],
        },
      },
    ],
    expectedAllOptions: [...defaultOptions, option3, option4],
  },
};
