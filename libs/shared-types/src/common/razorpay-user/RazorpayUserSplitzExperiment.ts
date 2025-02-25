

/**
 * Represents splitz experiments.
 */
export type RazorpayUserSplitzExperiment = Record<
  string,
  | []
  | {
      id: string;
      name: string;
      variables: Record<string, string>;
      experiment_id: string;
      weight: number;
    }
>;
