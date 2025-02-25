type ParsedABVariable = Record<string, unknown>;

type ExperimentType = {
  experimentId: string;
  variables: ParsedABVariable;
};

export const isExperimentEnabled = (experiment: ExperimentType): boolean => {
  return experiment?.variables?.["result"] === 'on';
};
