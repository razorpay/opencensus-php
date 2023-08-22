import { SUFFIX } from './constants';
import { Config, Payload } from './types';

export const isSaveNotAllowed = (configs: Config[]): boolean => {
  return configs.some(({ label, masking_length, type }) => !label || !masking_length || !type);
};

export const createPayload = (
  initialConfigurations: Config[],
  configurations: Config[],
): Payload => {
  const callback = (config: Config, should_be_deleted: boolean) => {
    const { suffix, ...rest } = config;

    return {
      should_be_deleted,
      configuration: rest,
    };
  };

  return {
    configurations: [
      ...initialConfigurations.map((config) => callback(config, true)),
      ...configurations.map((config) => callback(config, false)),
    ],
  };
};

export const formattedSuffix = (num: number): string => {
  return `${'X'.repeat(num > SUFFIX.length ? SUFFIX.length : num)}${SUFFIX.slice(num)}`;
};
