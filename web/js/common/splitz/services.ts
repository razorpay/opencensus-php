import abService from '@razorpay/universe-cli/ab';
import { evaluatedExperimentParser, evaluatedBulkExperimentsParser } from './utils';
import { ExperimentType, InitABServiceConfig, VariantConfigArgs } from './types';
import { APP_ENV } from './constants';

// for bulk calls, response modified as per our usecase
export const getVariant = async ({
  defaultVariant,
  experimentId,
}: VariantConfigArgs): Promise<ExperimentType> => {
  return await abService
    .getVariant(
      {
        experimentId: (experimentId[APP_ENV] as string) ?? experimentId.beta,
      },
      defaultVariant,
    )
    .then((value) => {
      return evaluatedExperimentParser({
        variables: value.variables,
        experiment_id: (experimentId[APP_ENV] as string) ?? experimentId.beta,
      });
    });
};

// for bulk calls, response modified as per our usecase
export const getVariants = async (
  experiments: VariantConfigArgs[],
): Promise<Record<string, ExperimentType>> => {
  const tempMap = {};

  const parsedExperiments = experiments.map(
    ({ defaultVariant: { name, variables }, experimentId, uniqueHashKey }) => {
      const refEnvExperimentId = (experimentId[APP_ENV] as string) ?? experimentId.beta;
      tempMap[refEnvExperimentId] = uniqueHashKey;

      return {
        experiment: {
          experimentId: refEnvExperimentId as string,
        },
        defaultVariant: {
          experiment_id: refEnvExperimentId as string,
          name,
          variables,
        },
      };
    },
  );

  return await abService
    .getVariants(
      parsedExperiments as unknown as {
        experiment: abService.ABBulkExperiment;
        defaultVariant: abService.ABVariant;
      }[],
    )
    .then((value) => {
      // Acc to ts suggestion, each experiment expects to have experimentId but instead,
      // from BE value it was experiment_id.
      return evaluatedBulkExperimentsParser(value, tempMap);
    });
};

// initializer
export const initABService = (config: Omit<InitABServiceConfig, 'experiments'>) => {
  abService.init({
    // api base url based on env like https://beta-api.stage.razorpay.in
    apiBaseUrl: config.apiBaseUrl,
    // unique id based on which user should be identified. will be merchant id in most cases
    id: config?.merchantId,
  });
};
