import abService from '@razorpay/universe-cli/ab';
import {
  ABVariant,
  EvalExperimentResults,
  ExperimentType,
  InitABServiceConfig,
  VariantConfigArgs,
} from './types';
import { APP_ENV } from '@apps/shell/src/env';

const chunkArray = <T>(array: T[], chunkSize: number): T[][] => {
  const chunks: T[][] = [];
  for (let i = 0; i < array.length; i += chunkSize) {
    chunks.push(array.slice(i, i + chunkSize));
  }
  return chunks;
};

// initializer
export const initABService = (config: Omit<InitABServiceConfig, 'experiments'>) => {
  abService.init({
    // api base url based on env like https://beta-api.stage.razorpay.in
    apiBaseUrl: config.apiBaseUrl,
    // @ts-ignore
    isInternalEndpointCall: config?.isInternalEndpointCall,
    internalApiAuthToken: config?.internalApiAuthToken,
  });
};

export const evaluatedExperimentParser = (
  evaluatedExperiment: Pick<ABVariant, 'experiment_id' | 'variables'>,
): ExperimentType => ({
  experimentId: evaluatedExperiment.experiment_id,
  variables: evaluatedExperiment.variables.reduce(
    (prevVariable, currVariable) => ({
      ...prevVariable,
      [currVariable.key]: currVariable.value,
    }),
    {},
  ),
});

export const evaluatedBulkExperimentsParser = (
  evaluatedExperiments: unknown[],
  expeimentHashkeyEvalMap: Record<
    string,
    {
      uniqueHashKey: string;
      evaluater: VariantConfigArgs['evaluater'];
    }
  >,
): EvalExperimentResults =>
  (evaluatedExperiments as ABVariant[]).reduce((prevExperiment, currExperiment) => {
    const evalUniqueHashMap = expeimentHashkeyEvalMap[currExperiment.experiment_id];
    const info = evaluatedExperimentParser(currExperiment);
    return {
      ...prevExperiment,
      [evalUniqueHashMap.uniqueHashKey]: {
        enabled: evalUniqueHashMap?.evaluater?.(info.variables),
        info,
      },
    };
  }, {});

// for bulk calls, response modified as per our usecase

export const getVariants = async (
  experiments: VariantConfigArgs[],
  userId: string,
): Promise<EvalExperimentResults> => {
  const tempMap = {};

  // Step 1: Parse experiments and prepare the map
  const parsedExperiments = experiments.map(
    ({ defaultVariant: { name, variables }, experimentId, uniqueHashKey, evaluater }) => {
      const refEnvExperimentId = (experimentId[APP_ENV as string] as string) ?? experimentId.beta;
      tempMap[refEnvExperimentId] = {
        uniqueHashKey,
        evaluater,
      };

      return {
        experiment: {
          experimentId: refEnvExperimentId as string,
          id: userId,
        },
        defaultVariant: {
          experiment_id: refEnvExperimentId as string,
          name,
          variables,
        },
      };
    },
  );

  // Step 2: Break parsed experiments into batches of 50
  const batches = chunkArray(parsedExperiments, 50);

  // Step 3: Make API calls for each batch
  const apiResults = await Promise.all(
    batches.map((batch) =>
      abService.getVariants(
        batch as unknown as {
          experiment: abService.ABBulkExperiment;
          defaultVariant: abService.ABVariant;
        }[],
      ),
    ),
  );

  // Step 4: Merge all the results from the batches
  const mergedResults = apiResults.reduce((acc, currentBatchResult) => {
    const parsedBatchResult = evaluatedBulkExperimentsParser(currentBatchResult, tempMap);
    return {
      ...acc,
      ...parsedBatchResult,
    };
  }, {});

  return mergedResults;
};


export const parseExperimentsForServerSideUsage = (x: EvalExperimentResults) => {
  return Object.entries(x).reduce((prev, curr) => {
    const [uniqueName, value] = curr;
    return {
      ...prev,
      [uniqueName]: value.enabled,
    };
  }, {});
};
