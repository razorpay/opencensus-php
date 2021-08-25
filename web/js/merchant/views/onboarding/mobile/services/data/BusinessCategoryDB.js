const initialData = [
  {
    group_name: 'ecommerce',
    matches: [
      {
        subcategory_value: 'computer_software_stores',
        subcategory_name: 'Computer Software Stores',
        tags: ['Computer'],
      },
      {
        subcategory_value: 'computers_peripheral_equipment_software',
        subcategory_name: 'Computers, Computer Peripheral Equipment, Software',
        tags: ['Computers', 'Computer'],
      },
    ],
  },
  {
    group_name: 'services',
    matches: [
      {
        subcategory_value: 'internet_service_providers',
        subcategory_name: 'Computer Network/Information Services',
        tags: ['Computer'],
      },
    ],
  },
  {
    group_name: 'computer_programming_data_processing',
    matches: [
      {
        subcategory_value: 'computer_programming_data_processing',
        subcategory_name: 'Computer Programming/Data Processing',
        tags: ['Computer'],
      },
    ],
  },
  {
    group_name: 'housing',
    matches: [
      {
        subcategory_value: 'facility_management',
        subcategory_name: 'Facility Management Company',
        tags: ['Company'],
      },
    ],
  },
  {
    group_name: 'it_and_software',
    matches: [
      {
        subcategory_value: 'technical_support',
        subcategory_name: 'Technical Support',
        tags: ['Computer'],
      },
    ],
  },
  {
    group_name: 'tours_and_travel',
    matches: [
      { subcategory_value: 'aviation', subcategory_name: 'Aviation', tags: ['Compania'] },
      {
        subcategory_value: 'accommodation',
        subcategory_name: 'Lodging and Accommodation',
        tags: ['Compri'],
      },
    ],
  },
];

let data = [...initialData];

function read() {
  return data;
}

function update(_data) {
  data = { ...data, ..._data };
  return data;
}

function reset() {
  data = { ...initialData };
}

export { read, update, reset };
