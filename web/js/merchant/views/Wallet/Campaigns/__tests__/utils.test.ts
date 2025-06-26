import moment from 'moment';
import {
  normalizeEvents,
  combineEpochAndTime,
  generateRuleString,
  createUsageLimits,
  sanitizeRuleValues,
  getFilterRules,
} from '../utils';

describe('normalizeEvents', () => {
  it('should normalize attributes and flatten nested maps', () => {
    const input = [
      {
        id: 'event1',
        name: 'Event 1',
        payload_config: {
          order: {
            type: 'map',
            required: true,
            map: {
              amount: {
                type: 'number',
                required: true,
              },
              pins: {
                type: 'array',
                required: true,
                array: {
                  type: 'string',
                },
              },
              billing_address: {
                type: 'map',
                required: true,
                map: {
                  city: {
                    type: 'string',
                    required: true,
                  },
                  country: {
                    type: 'string',
                    required: true,
                  },
                  pins: {
                    type: 'array',
                    required: true,
                    array: {
                      type: 'string',
                    },
                  },
                },
              },
            },
          },
        },
      },
      {
        id: 'event2',
        name: 'Event 2',
        payload_config: {
          user: {
            type: 'map',
            required: false,
            map: {
              name: {
                type: 'string',
                required: true,
              },
              email: {
                type: 'string',
                required: true,
              },
            },
          },
        },
      },
    ];

    const expectedOutput = {
      event1: {
        name: 'Event 1',
        attributes: {
          'order.amount': {
            type: 'number',
            required: true,
          },
          'order.billing_address.city': {
            type: 'string',
            required: true,
          },
          'order.billing_address.country': {
            type: 'string',
            required: true,
          },
          'order.billing_address.pins[]': {
            elementType: 'string',
            required: true,
            type: 'array',
          },
          'order.pins[]': {
            elementType: 'string',
            required: true,
            type: 'array',
          },
        },
      },
      event2: {
        name: 'Event 2',
        attributes: {
          'user.name': {
            type: 'string',
            required: true,
          },
          'user.email': {
            type: 'string',
            required: true,
          },
        },
      },
    };

    const result = normalizeEvents(input);

    expect(result).toEqual(expectedOutput);
  });

  it('should handle array type attributes properly', () => {
    const input = [
      {
        id: 'event3',
        name: 'Event 3',
        payload_config: {
          order: {
            type: 'map',
            required: true,
            map: {
              line_items: {
                type: 'array',
                required: true,
                array: {
                  type: 'map',
                  map: {
                    sku: {
                      type: 'string',
                      required: true,
                    },
                    price: {
                      type: 'int',
                      required: true,
                    },
                  },
                },
              },
              order_tags: {
                type: 'array',
                required: true,
                array: {
                  type: 'string',
                },
              },
            },
          },
        },
      },
    ];

    const expectedOutput = {
      event3: {
        name: 'Event 3',
        attributes: {
          // Array of maps - only scalar fields inside, not the parent array
          'order.line_items[].sku': {
            type: 'string',
            required: true,
          },
          'order.line_items[].price': {
            type: 'int',
            required: true,
          },
          // Scalar array is included
          'order.order_tags[]': {
            type: 'array',
            required: true,
            elementType: 'string',
          },
        },
      },
    };

    const result = normalizeEvents(input);

    expect(result).toEqual(expectedOutput);
  });

  it('should flatten the config and ignore nested arrays in output', () => {
    const input = [
      {
        id: 'event4',
        name: 'Event 4',
        payload_config: {
          order: {
            type: 'map',
            required: true,
            map: {
              addresses: {
                type: 'array',
                required: true,
                array: {
                  type: 'map',
                  map: {
                    city: {
                      type: 'string',
                      required: true,
                    },
                    pins: {
                      type: 'array',
                      required: true,
                      array: {
                        type: 'int',
                      },
                    },
                    address_owners: {
                      type: 'array',
                      required: true,
                      array: {
                        type: 'map',
                        map: {
                          name: {
                            type: 'string',
                            required: true,
                          },
                        },
                      },
                    },
                  },
                },
              },
            },
          },
        },
      },
    ];

    const expectedOutput = {
      event4: {
        name: 'Event 4',
        attributes: {
          // No container array entry for order.addresses
          'order.addresses[].city': {
            type: 'string',
            required: true,
          },
        },
      },
    };

    const result = normalizeEvents(input);

    expect(result).toEqual(expectedOutput);
  });

  it('should return an empty object when no events are provided', () => {
    const input = [];

    const result = normalizeEvents(input);

    expect(result).toEqual({});
  });

  it('should handle an empty payload_config gracefully', () => {
    const input = [
      {
        id: 'event3',
        name: 'Event 3',
        payload_config: {},
      },
    ];

    const expectedOutput = {
      event3: {
        name: 'Event 3',
        attributes: {},
      },
    };

    const result = normalizeEvents(input);

    expect(result).toEqual(expectedOutput);
  });

  it('should handle scalar arrays and nested maps correctly', () => {
    const input = [
      {
        id: 'event5',
        name: 'Event 5',
        payload_config: {
          sample_order: {
            type: 'map',
            required: true,
            map: {
              id: {
                type: 'string',
                required: true,
              },
              amount: {
                type: 'int',
                required: true,
              },
              // Scalar array - should be included
              pins: {
                type: 'array',
                required: true,
                array: {
                  type: 'string',
                  required: true,
                },
              },
              // Nested map - should normalize all the way to scalar values
              address: {
                type: 'map',
                required: true,
                map: {
                  line1: {
                    type: 'map',
                    required: true,
                    map: {
                      city: {
                        type: 'string',
                        required: true,
                      },
                      state: {
                        type: 'string',
                        required: true,
                      },
                    },
                  },
                },
              },
            },
          },
        },
      },
    ];

    const expectedOutput = {
      event5: {
        name: 'Event 5',
        attributes: {
          'sample_order.id': {
            type: 'string',
            required: true,
          },
          'sample_order.amount': {
            type: 'int',
            required: true,
          },
          // Scalar array is normalized only up to the array itself
          'sample_order.pins[]': {
            type: 'array',
            required: true,
            elementType: 'string',
          },
          // Nested map is normalized all the way to scalar values
          'sample_order.address.line1.city': {
            type: 'string',
            required: true,
          },
          'sample_order.address.line1.state': {
            type: 'string',
            required: true,
          },
        },
      },
    };

    const result = normalizeEvents(input);

    expect(result).toEqual(expectedOutput);
  });

  it('should handle array of maps and nested arrays together correctly', () => {
    const input = [
      {
        id: 'event6',
        name: 'Event 6',
        payload_config: {
          sample_order: {
            type: 'map',
            required: true,
            map: {
              // Array of maps - normalize to scalar fields inside maps
              addresses: {
                type: 'array',
                required: true,
                array: {
                  type: 'map',
                  map: {
                    line_address: {
                      type: 'string',
                      required: true,
                    },
                    city: {
                      type: 'string',
                      required: true,
                    },
                    // Nested array inside array of maps - should be excluded
                    pins: {
                      type: 'array',
                      required: true,
                      array: {
                        type: 'int',
                      },
                    },
                  },
                },
              },
            },
          },
        },
      },
    ];

    const result = normalizeEvents(input) as any;

    // Check scalar values are included but nested arrays are not
    expect(result['event6'].name).toBe('Event 6');
    expect(result['event6'].attributes['sample_order.addresses[].line_address']).toBeDefined();
    expect(result['event6'].attributes['sample_order.addresses[].city']).toBeDefined();
    expect(result['event6'].attributes['sample_order.addresses[].pins']).toBeUndefined();
  });

  it('should follow array normalization rules correctly', () => {
    const input = [
      {
        id: 'event_arrays',
        name: 'Event Arrays',
        payload_config: {
          sample_order: {
            type: 'map',
            required: true,
            map: {
              // Simple scalar fields
              id: {
                type: 'string',
                required: true,
              },
              amount: {
                type: 'int',
                required: true,
              },
              // Scalar array - should be included in output
              tags: {
                type: 'array',
                required: true,
                array: {
                  type: 'string',
                },
              },
              // Array of maps - should not be included, only its contents
              line_items: {
                type: 'array',
                required: true,
                array: {
                  type: 'map',
                  map: {
                    sku: {
                      type: 'string',
                      required: true,
                    },
                    price: {
                      type: 'int',
                      required: true,
                    },
                  },
                },
              },
              // Deeply nested regular map - normalize all the way
              shipping: {
                type: 'map',
                required: false,
                map: {
                  address: {
                    type: 'map',
                    required: true,
                    map: {
                      city: {
                        type: 'string',
                        required: true,
                      },
                      state: {
                        type: 'string',
                        required: true,
                      },
                    },
                  },
                },
              },
            },
          },
        },
      },
    ];

    const expectedOutput = {
      event_arrays: {
        name: 'Event Arrays',
        attributes: {
          // Simple scalar fields
          'sample_order.id': {
            type: 'string',
            required: true,
          },
          'sample_order.amount': {
            type: 'int',
            required: true,
          },
          // Scalar array included
          'sample_order.tags[]': {
            type: 'array',
            required: true,
            elementType: 'string',
          },
          // Array of maps - only the content fields, not the array itself
          'sample_order.line_items[].sku': {
            type: 'string',
            required: true,
          },
          'sample_order.line_items[].price': {
            type: 'int',
            required: true,
          },
          // Deep nested map fields
          'sample_order.shipping.address.city': {
            type: 'string',
            required: true,
          },
          'sample_order.shipping.address.state': {
            type: 'string',
            required: true,
          },
        },
      },
    };

    const result = normalizeEvents(input);

    expect(result).toEqual(expectedOutput);
  });

  it('should handle elementType property correctly for arrays', () => {
    const input = [
      {
        id: 'event_element_type',
        name: 'Event Element Type',
        payload_config: {
          order: {
            type: 'map',
            required: true,
            map: {
              tags: {
                type: 'array',
                required: true,
                array: {
                  type: 'string',
                },
              },
              amounts: {
                type: 'array',
                required: true,
                array: {
                  type: 'int',
                },
              },
            },
          },
        },
      },
    ];

    const expectedOutput = {
      event_element_type: {
        name: 'Event Element Type',
        attributes: {
          'order.tags[]': {
            type: 'array',
            required: true,
            elementType: 'string',
          },
          'order.amounts[]': {
            type: 'array',
            required: true,
            elementType: 'int',
          },
        },
      },
    };

    const result = normalizeEvents(input);
    expect(result).toEqual(expectedOutput);
  });
});

describe('combineEpochAndTime', () => {
  const testCases = [
    { date: '2025-04-19', time: '02:00' },
    { date: '2025-04-20', time: '00:00' },
    { date: '2025-04-21', time: '23:59' },
    { date: '2025-04-22', time: '12:00' },
    { date: '2025-04-24', time: '5:30' },
    { date: '2025-04-25', time: '09:05' },
  ];

  testCases.forEach(({ date, time }) => {
    it(`should correctly combine ${date} and ${time}`, () => {
      const dateObj = new Date(`${date}T00:00:00`);
      const [hour, minute] = time.split(':').map(Number);

      const expected = moment(dateObj)
        .set({
          hour,
          minute,
          second: 0,
          millisecond: 0,
        })
        .utc()
        .unix();

      const actual = combineEpochAndTime(dateObj, time);

      expect(actual).toBe(expected);
    });
  });
});

describe('generateRuleString', () => {
  it('should generate correct rule string for equal_to operator with string values', () => {
    const rules = [
      {
        field: 'name',
        operator: 'equal_to',
        value: 'John Doe',
        type: 'string',
      },
    ];

    const expected = 'Event.name == "John Doe"';
    const result = generateRuleString(rules);

    expect(result).toBe(expected);
  });

  it('should generate correct rule string for equal_to operator with number values', () => {
    const rules = [
      {
        field: 'age',
        operator: 'equal_to',
        value: 30,
        type: 'int',
      },
    ];

    const expected = 'Event.age == 30';
    const result = generateRuleString(rules);

    expect(result).toBe(expected);
  });

  it('should generate correct rule string for contains operator with string values', () => {
    const rules = [
      {
        field: 'name',
        operator: 'contains',
        value: 'Doe',
        type: 'string',
      },
    ];

    const expected = 'Event.name in ["Doe"]';
    const result = generateRuleString(rules);

    expect(result).toBe(expected);
  });

  it('should generate correct rule string for greater_than operator with number values', () => {
    const rules = [
      {
        field: 'age',
        operator: 'greater_than',
        value: 18,
        type: 'int',
      },
    ];

    const expected = 'Event.age > 18';
    const result = generateRuleString(rules);

    expect(result).toBe(expected);
  });

  it('should generate correct rule string for is_between operator with number values', () => {
    const rules = [
      {
        field: 'age',
        operator: 'is_between',
        minValue: 18,
        maxValue: 30,
        type: 'int',
      },
    ];

    const expected = 'Event.age >= 18 && Event.age <= 30';
    const result = generateRuleString(rules);

    expect(result).toBe(expected);
  });

  it('should generate correct rule string for is operator with boolean values', () => {
    const rules = [
      {
        field: 'isActive',
        operator: 'is',
        value: 'yes',
        type: 'boolean',
      },
    ];

    const expected = 'Event.isActive == true';
    const result = generateRuleString(rules);

    expect(result).toBe(expected);
  });

  it('should return an empty string for invalid operators', () => {
    const rules = [
      {
        field: 'age',
        operator: 'invalid_operator',
        value: 30,
        type: 'int',
      },
    ];

    const result = generateRuleString(rules);

    expect(result).toBe('');
  });

  it('should handle empty rules gracefully', () => {
    const rules = [];

    const result = generateRuleString(rules);

    expect(result).toBe('');
  });

  it('should escape special characters in string values', () => {
    const rules = [
      {
        field: 'name',
        operator: 'equal_to',
        value: 'John "The Boss" Doe',
        type: 'string',
      },
    ];

    const expected = 'Event.name == "John \\"The Boss\\" Doe"';
    const result = generateRuleString(rules);

    expect(result).toBe(expected);
  });

  it('should handle multiple rules and join them with "&&"', () => {
    const rules = [
      {
        field: 'age',
        operator: 'greater_than',
        value: 18,
        type: 'int',
      },
      {
        field: 'isActive',
        operator: 'is',
        value: 'yes',
        type: 'boolean',
      },
      {
        field: 'name',
        operator: 'equal_to',
        value: 'John Doe',
        type: 'string',
      },
    ];

    const expected = 'Event.age > 18 && Event.isActive == true && Event.name == "John Doe"';
    const result = generateRuleString(rules);

    expect(result).toBe(expected);
  });

  it('shuld generate correct rule for amount like fields', () => {
    const rules = [
      {
        field: 'amount',
        operator: 'greater_than',
        value: 100,
        type: 'int',
      },
    ];

    const expected = 'Event.amount > 10000';
    const result = generateRuleString(rules);

    expect(result).toBe(expected);
  });

  it('should format string arrays correctly with escaped quotes around values', () => {
    const rules = [
      {
        field: 'tags',
        operator: 'contains_any',
        value: '1,2,3,4,5',
        type: 'array',
        elementType: 'string',
      },
    ];

    //prettier-ignore
    const expected = "Event.tags contains_any [\"1\",\"2\",\"3\",\"4\",\"5\"]";
    const result = generateRuleString(rules);

    expect(result).toBe(expected);
  });

  it('should format number arrays correctly without quotes around values', () => {
    const rules = [
      {
        field: 'amounts',
        operator: 'contains_any',
        value: '1,2,3,4,5',
        type: 'array',
        elementType: 'int',
      },
    ];

    const expected = 'Event.amounts contains_any [1,2,3,4,5]';
    const result = generateRuleString(rules);

    expect(result).toBe(expected);
  });

  it('should format contains_all operator correctly based on element type', () => {
    const stringRule = {
      field: 'tags',
      operator: 'contains_all',
      value: 'tag1,tag2',
      type: 'array',
      elementType: 'string',
    };

    const numberRule = {
      field: 'ids',
      operator: 'contains_all',
      value: '1,2,3',
      type: 'array',
      elementType: 'int',
    };

    const stringResult = generateRuleString([stringRule]);
    const numberResult = generateRuleString([numberRule]);

    //prettier-ignore
    const expectedString = "Event.tags contains_all [\"tag1\",\"tag2\"]";
    expect(stringResult).toBe(expectedString);
    expect(numberResult).toBe('Event.ids contains_all [1,2,3]');
  });

  it('should handle whitespace in array values correctly', () => {
    const rules = [
      {
        field: 'tags',
        operator: 'contains_any',
        value: ' tag1 , tag2 , tag3 ',
        type: 'array',
        elementType: 'string',
      },
    ];

    //prettier-ignore
    const expected = "Event.tags contains_any [\"tag1\",\"tag2\",\"tag3\"]";
    const result = generateRuleString(rules);

    expect(result).toBe(expected);
  });

  it('should format contains operator correctly', () => {
    const rules = [
      {
        field: 'tags',
        operator: 'contains',
        value: 'tag1,tag2,tag3',
        type: 'string',
      },
    ];

    const expected = 'Event.tags in ["tag1","tag2","tag3"]';
    const result = generateRuleString(rules);
    expect(result).toBe(expected);
  });

  it('should convert amount fields in array values to paise', () => {
    const rules = [
      {
        field: 'amount',
        operator: 'contains_any',
        value: '100,200,300',
        type: 'array',
        elementType: 'int',
      },
    ];

    const expected = 'Event.amount contains_any [10000,20000,30000]';
    const result = generateRuleString(rules);
    expect(result).toBe(expected);
  });

  it('should skip fields with array elements in the middle', () => {
    const rules = [
      {
        field: 'order.line_items[].price',
        operator: 'greater_than',
        value: 100,
        type: 'int',
      },
      {
        field: 'price',
        operator: 'greater_than',
        value: 100,
        type: 'int',
      },
    ];

    const expected = 'Event.price > 10000';
    const result = generateRuleString(rules);
    expect(result).toBe(expected);
  });
});

describe('createUsageLimits', () => {
  it('should return an empty object when all limits are disabled', () => {
    const data = {
      campaignLimitAmountEnabled: false,
      campaignLimitActionsEnabled: false,
      userLimitAmountEnabled: false,
      userLimitActionsEnabled: false,
    } as any;

    expect(createUsageLimits(data)).toEqual({});
  });

  it('should add SINGLE_CAMPAIGN when only campaign amount limit is enabled', () => {
    const data = {
      campaignLimitAmountEnabled: true,
      campaignLimitAmount: 50,
      campaignLimitAmountPeriod: 'DAILY',
      campaignLimitActionsEnabled: false,
      userLimitAmountEnabled: false,
      userLimitActionsEnabled: false,
    } as any;

    expect(createUsageLimits(data)).toEqual({
      usage_limits: [
        {
          type: 'SINGLE_CAMPAIGN',
          config: [
            {
              budget: 5000,
              frequency: 'DAILY',
            },
          ],
        },
      ],
    });
  });

  it('should add SINGLE_CAMPAIGN_USER when only user amount limit is enabled', () => {
    const data = {
      campaignLimitAmountEnabled: false,
      campaignLimitActionsEnabled: false,
      userLimitAmountEnabled: true,
      userLimitAmount: 30,
      userLimitAmountPeriod: 'WEEKLY',
      userLimitActionsEnabled: false,
    } as any;

    expect(createUsageLimits(data)).toEqual({
      usage_limits: [
        {
          type: 'SINGLE_CAMPAIGN_USER',
          config: [
            {
              budget: 3000,
              frequency: 'WEEKLY',
            },
          ],
        },
      ],
    });
  });

  it('should merge budget and count when frequency matches for SINGLE_CAMPAIGN', () => {
    const data = {
      campaignLimitAmountEnabled: true,
      campaignLimitAmount: 50,
      campaignLimitAmountPeriod: 'DAILY',
      campaignLimitActionsEnabled: true,
      campaignLimitActions: 10,
      campaignLimitActionsPeriod: 'DAILY',
      userLimitAmountEnabled: false,
      userLimitActionsEnabled: false,
    } as any;

    expect(createUsageLimits(data)).toEqual({
      usage_limits: [
        {
          type: 'SINGLE_CAMPAIGN',
          config: [
            {
              budget: 5000,
              frequency: 'DAILY',
              count: 10,
            },
          ],
        },
      ],
    });
  });

  it('should keep separate objects when frequency is different for SINGLE_CAMPAIGN', () => {
    const data = {
      campaignLimitAmountEnabled: true,
      campaignLimitAmount: 50,
      campaignLimitAmountPeriod: 'DAILY',
      campaignLimitActionsEnabled: true,
      campaignLimitActions: 10,
      campaignLimitActionsPeriod: 'WEEKLY',
      userLimitAmountEnabled: false,
      userLimitActionsEnabled: false,
    } as any;

    expect(createUsageLimits(data)).toEqual({
      usage_limits: [
        {
          type: 'SINGLE_CAMPAIGN',
          config: [
            {
              budget: 5000,
              frequency: 'DAILY',
            },
            {
              count: 10,
              frequency: 'WEEKLY',
            },
          ],
        },
      ],
    });
  });

  it('should merge budget and count when frequency matches for SINGLE_CAMPAIGN_USER', () => {
    const data = {
      campaignLimitAmountEnabled: false,
      campaignLimitActionsEnabled: false,
      userLimitAmountEnabled: true,
      userLimitAmount: 30,
      userLimitAmountPeriod: 'WEEKLY',
      userLimitActionsEnabled: true,
      userLimitActions: 5,
      userLimitActionsPeriod: 'WEEKLY',
    } as any;

    expect(createUsageLimits(data)).toEqual({
      usage_limits: [
        {
          type: 'SINGLE_CAMPAIGN_USER',
          config: [
            {
              budget: 3000,
              frequency: 'WEEKLY',
              count: 5,
            },
          ],
        },
      ],
    });
  });

  it('should handle both SINGLE_CAMPAIGN and SINGLE_CAMPAIGN_USER correctly', () => {
    const data = {
      campaignLimitAmountEnabled: true,
      campaignLimitAmount: 50,
      campaignLimitAmountPeriod: 'DAILY',
      campaignLimitActionsEnabled: true,
      campaignLimitActions: 10,
      campaignLimitActionsPeriod: 'WEEKLY',
      userLimitAmountEnabled: true,
      userLimitAmount: 30,
      userLimitAmountPeriod: 'WEEKLY',
      userLimitActionsEnabled: true,
      userLimitActions: 5,
      userLimitActionsPeriod: 'WEEKLY',
    };

    expect(createUsageLimits(data)).toEqual({
      usage_limits: [
        {
          type: 'SINGLE_CAMPAIGN',
          config: [
            {
              budget: 5000,
              frequency: 'DAILY',
            },
            {
              count: 10,
              frequency: 'WEEKLY',
            },
          ],
        },
        {
          type: 'SINGLE_CAMPAIGN_USER',
          config: [
            {
              budget: 3000,
              frequency: 'WEEKLY',
              count: 5,
            },
          ],
        },
      ],
    });
  });
});

describe('sanitizeRuleValues', () => {
  it('should return the value with "iprog_" prefix if not present', () => {
    const result = sanitizeRuleValues('program_id', '12345');
    expect(result).toBe('iprog_12345');
  });

  it('should return the value as is if it already has "iprog_" prefix', () => {
    const result = sanitizeRuleValues('program_id', 'iprog_12345');
    expect(result).toBe('iprog_12345');
  });

  it('should return the value unchanged for fields other than "program_id"', () => {
    const result = sanitizeRuleValues('other_field', 'value');
    expect(result).toBe('value');
  });
});

describe('getFilterRules', () => {
  it('should create filter rules grouped by array paths', () => {
    const rules = [
      // Regular non-array field - should be ignored
      {
        field: 'order.amount',
        operator: 'greater_than',
        value: 100,
        type: 'int',
      },
      // Array fields from order.line_items
      {
        field: 'order.line_items[].sku',
        operator: 'equal_to',
        value: 'SKU123',
        type: 'string',
      },
      {
        field: 'order.line_items[].price',
        operator: 'greater_than',
        value: 50,
        type: 'int',
      },
      // Array fields from order.addresses
      {
        field: 'order.addresses[].city',
        operator: 'equal_to',
        value: 'Mumbai',
        type: 'string',
      },
      {
        field: 'order.addresses[].line_address',
        operator: 'contains',
        value: 'Main Street',
        type: 'string',
      },
    ];

    const result = getFilterRules(rules);

    expect(result).toEqual({
      'order.line_items': 'Event.sku == "SKU123" && Event.price > 5000',
      'order.addresses': 'Event.city == "Mumbai" && Event.line_address in ["Main Street"]',
    });
  });

  it('should handle different operators correctly', () => {
    const rules = [
      // Equal to
      {
        field: 'order.line_items[].sku',
        operator: 'equal_to',
        value: 'SKU123',
        type: 'string',
      },
      // Greater than
      {
        field: 'order.line_items[].price',
        operator: 'greater_than',
        value: 50,
        type: 'int',
      },
      // Is between
      {
        field: 'order.line_items[].quantity',
        operator: 'is_between',
        minValue: 5,
        maxValue: 10,
        type: 'int',
      },
      // Is (boolean)
      {
        field: 'order.line_items[].in_stock',
        operator: 'is',
        value: 'yes',
        type: 'boolean',
      },
    ];

    const result = getFilterRules(rules);

    expect(result).toEqual({
      'order.line_items':
        'Event.sku == "SKU123" && Event.price > 5000 && Event.quantity >= 5 && Event.quantity <= 10 && Event.in_stock == true',
    });
  });

  it('should handle amount fields correctly', () => {
    const rules = [
      {
        field: 'order.line_items[].price',
        operator: 'greater_than',
        value: 100,
        type: 'int',
      },
    ];

    const result = getFilterRules(rules);

    // Should be multiplied by 100 for paise conversion
    expect(result).toEqual({
      'order.line_items': 'Event.price > 10000',
    });
  });

  it('should return null when no array fields are provided', () => {
    const rules = [
      {
        field: 'order.amount',
        operator: 'greater_than',
        value: 100,
        type: 'int',
      },
      {
        field: 'customer.email',
        operator: 'equal_to',
        value: 'test@example.com',
        type: 'string',
      },
    ];

    const result = getFilterRules(rules);

    expect(result).toBeNull();
  });

  it('should sanitize program_id values in array fields', () => {
    const rules = [
      {
        field: 'order.line_items[].program_id',
        operator: 'equal_to',
        value: '12345',
        type: 'string',
      },
      {
        field: 'order.line_items[].program_id',
        operator: 'equal_to',
        value: 'iprog_67890',
        type: 'string',
      },
    ];

    const result = getFilterRules(rules);

    expect(result).toEqual({
      'order.line_items': 'Event.program_id == "iprog_12345" && Event.program_id == "iprog_67890"',
    });
  });
});
