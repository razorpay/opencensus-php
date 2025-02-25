import abService from '@razorpay/universe-cli/ab';

import {
  evaluatedExperimentParser,
  evaluatedBulkExperimentsParser,
  getTargettedExperimentId,
} from 'common/splitz/utils';

import { ExperimentType, InitABServiceConfig, VariantAPICallArgs } from './types';

// for bulk calls, response modified as per our usecase
export const getVariant = async ({
  defaultVariant,
  experimentId,
  requestData,
}: VariantAPICallArgs): Promise<ExperimentType> => {
  return abService
    .getVariant(
      {
        experimentId: getTargettedExperimentId(experimentId),
        variables: requestData,
      },
      defaultVariant,
    )
    .then((value) => {
      return evaluatedExperimentParser({
        variables: value.variables,
        experiment_id: getTargettedExperimentId(experimentId),
      });
    });
};

// for bulk calls, response modified as per our usecase
export const getVariants = async (
  experiments: VariantAPICallArgs[],
): Promise<Record<string, ExperimentType>> => {
  const tempMap = {};

  const parsedExperiments = experiments.map(
    ({ defaultVariant: { name, variables }, experimentId, uniqueHashKey, requestData }) => {
      const refEnvExperimentId = getTargettedExperimentId(experimentId);
      tempMap[refEnvExperimentId] = uniqueHashKey;

      return {
        experiment: {
          experimentId: refEnvExperimentId as string,
          variables: requestData,
        },
        defaultVariant: {
          experiment_id: refEnvExperimentId as string,
          name,
          variables,
        },
      };
    },
  );

  return abService
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
