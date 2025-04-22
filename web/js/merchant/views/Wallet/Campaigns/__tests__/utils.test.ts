import moment from 'moment';
import {
  normalizeEvents,
  combineEpochAndTime,
  generateRuleString,
  createUsageLimits,
  sanitizeRuleValues,
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
