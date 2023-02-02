const config = {};

export const init = (abConfig) => {
  Object.assign(config, abConfig);
};

export const getVariants = async (experiments) => {
  // keeping a map of default variants for each exp, search would be in constant time
  const defaultVariantsMap = {};
  if (!experiments) {
    throw new Error('[ab]: experiments list cannot be empty');
  }

  if (!Array.isArray(experiments)) {
    throw new Error('[ab]: experiments has to be an array');
  }

  experiments.forEach(({ experiment, defaultVariant }) => {
    // each experiment should have an experiment id
    if (!experiment.experimentId) {
      throw new Error(`[ab]: experiment does not have an experiment id`);
    }

    // each experiment should have a default variant
    if (!defaultVariant) {
      throw new Error(
        `[ab]: experiment ${experiment.experimentId} does not have a default variant`,
      );
    }
    defaultVariantsMap[experiment.experimentId] = defaultVariant;
  });

  const experimentsList = experiments.map(({ experiment }) => {
    return {
      id: experiment.id,
      experiment_id: experiment?.experimentId,
      track_impression: experiment?.trackImpression ?? true,
    };
  });
  const body = {
    bulk_evaluate: experimentsList,
  };
  try {
    const ABServiceResponse = await fetch(
      `https://${config.base_url}/v1/splitz/twirp/rzp.splitz.evaluate.v1.EvaluateAPI/EvaluateBulk`,
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(body),
      },
    )
      .then((response) => response.json())
      .then((response) => {
        // https://idocs.razorpay.com/openapi/splitz/master#post-/rzp.splitz.evaluate.v1.EvaluateAPI/Evaluate
        // mentions response will have bulk_evaluate_response key containing the variants
        if (response && response.bulk_evaluate_response) {
          return response.bulk_evaluate_response.map(({ experiment, variant }) => {
            if (variant) {
              return { [experiment?.id]: variant };
            } else {
              // for experiment ids not valid, we'll return respective default variants
              return {
                [experiment?.id]: defaultVariantsMap[experiment?.id],
              };
            }
          });
        } else {
          // return default variants for all experiments
          return experiments.map(({ experiment, defaultVariant }) => {
            return {
              [experiment?.experimentId]: {
                id: experiment?.id,
                experiment_id: experiment?.experimentId,
                ...defaultVariant,
              },
            };
          });
        }
      });
    return ABServiceResponse;
  } catch (err) {
    // return default variants
    return experiments.map(({ experiment, defaultVariant }) => {
      return {
        [experiment?.experimentId]: {
          id: experiment?.id,
          experiment_id: experiment?.experimentId,
          ...defaultVariant,
        },
      };
    });
  }
};
