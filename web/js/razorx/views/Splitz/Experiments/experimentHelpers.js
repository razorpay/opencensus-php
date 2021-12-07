export const stringifyNull = (value) => (value === null ? 'null' : value);

export const getAudienceRules = (audience) => {
  audience = JSON.parse(audience);
  const ruleCondition = Object.keys(audience)[0];
  const rules = audience[ruleCondition];

  return {
    ruleCondition,
    rules: rules.map((rule) => {
      const operator = Object.keys(rule)[0];
      return {
        operator,
        key: rule[operator][0].var[0],
        value: rule[operator][1],
      };
    }),
  };
};

export const createAudienceRules = (audienceRules) =>
  JSON.stringify({
    [audienceRules.ruleCondition]: audienceRules.rules.map((rule) => ({
      [rule.operator]: [
        {
          var: [rule.key],
        },
        rule.value === 'null' ? null : rule.value,
      ],
    })),
  });

// return true if all rules are empty
export const isEmptyRules = (audienceRules) => {
  if (!audienceRules || audienceRules.length === 0) return true;

  return audienceRules.every((rule) => !rule.key && !rule.operator && !rule.value);
};

export const ruleOperatorMap = {
  '>': 'greater than',
  '<': 'less than',
  '===': 'equal to',
  belongsTo: 'belongs to',
  doesNotBelongTo: "doesn't belong to",
};
