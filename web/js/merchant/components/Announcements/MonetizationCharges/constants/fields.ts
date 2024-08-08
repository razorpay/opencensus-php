export const enum FIELD_NAMES {
  name = 'name',
  phone = 'phone',
  email = 'email',
  company = 'company',
  website = 'website',
  revenue = 'revenue',
  employeeCount = 'employeeCount',
  productInterest = 'productInterest',
}

interface FormFieldElement {
  label: string;
  value: string;
}

interface FormField {
  isRequired: boolean;
  name: string;
  destinationKey: string;
  label: string;
  type: string;
  placeholder: string;
  isPIIData: boolean;
  helperText?: string;
  elements?: FormFieldElement[];
  transformValues?: (value: string) => number;
}

export const FORM_FIELDS: FormField[] = [
  {
    isRequired: true,
    name: 'name',
    destinationKey: 'Name',
    label: 'Your Name',
    type: 'text',
    placeholder: 'Start typing here',
    isPIIData: true,
  },
  {
    isRequired: true,
    name: 'phone',
    destinationKey: 'Phone',
    label: 'Mobile number',
    type: 'phone-IN',
    placeholder: '98765 43210',
    isPIIData: true,
  },
  {
    isRequired: true,
    name: 'email',
    destinationKey: 'Email',
    label: 'Work Email',
    type: 'email',
    placeholder: 'Enter email ID',
    isPIIData: true,
  },
  {
    isRequired: true,
    name: 'company',
    destinationKey: 'Company',
    label: 'Business Name',
    type: 'text',
    placeholder: 'Enter business name',
    isPIIData: true,
  },
  {
    isRequired: false,
    name: 'website',
    destinationKey: 'Website',
    label: 'Website',
    type: 'text',
    placeholder: 'Enter website URL',
    helperText: 'You can enter social media links, linkedIn, or your website URL',
    isPIIData: false,
  },
  {
    isRequired: true,
    name: 'revenue',
    destinationKey: 'Average_Monthly_Revenue__c',
    label: 'Average Monthly Revenue',
    type: 'select',
    placeholder: 'Select',
    elements: [
      { label: '0 - 1L', value: '0 - 1L' },
      { label: '1L - 5L', value: '1L - 5L' },
      { label: '5L - 25L', value: '5L - 25L' },
      { label: '25L - 1Cr', value: '25L - 1Cr' },
      { label: 'Above 1Cr', value: 'Above 1Cr' },
    ],
    isPIIData: false,
  },
  {
    isRequired: true,
    name: 'employeeCount',
    destinationKey: 'NumberOfEmployees',
    label: 'Number of Employees',
    type: 'select',
    placeholder: 'Select',
    elements: [
      { label: '1 - 19', value: '1 - 19' },
      { label: '20 - 99', value: '20 - 99' },
      { label: '100 - 499', value: '100 - 499' },
      { label: '500 - 1999', value: '500 - 1999' },
      { label: '2000 - 4999', value: '2000 - 4999' },
      { label: '5000+', value: '5000+' },
    ],
    transformValues: (value) => {
      const upperLimit = value.split('-').pop();
      if (upperLimit) {
        const upperLimitStr = upperLimit.replace('+', '').trim();
        return Number(upperLimitStr);
      }
      return 0;
    },
    isPIIData: false,
  },
];
