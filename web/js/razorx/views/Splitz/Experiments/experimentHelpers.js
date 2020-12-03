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
        rule.value,
      ],
    })),
  });
