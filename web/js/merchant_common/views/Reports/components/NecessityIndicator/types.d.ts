enum NecessityIndicator {
  required = 'required',
  none = 'none',
  optional = 'optional',
}

export type NecessityIndicatorType = keyof typeof NecessityIndicator;
