export const getDefaultSelectedStepId = (steps): string | null => {
  return steps.reduce((defaultStep, step) => {
    if (step?.data?.change_type === 'selected') {
      defaultStep = step.id;
    }

    return defaultStep;
  }, null);
};

export const getSuggestedProductForSelectedStep = (selectedStep): string => {
  return (
    selectedStep?.data?.cross_sell_widget_data?.cross_sell_widget_product_data?.product_name || ''
  );
};

export const getPitchingTypeForSelectedStep = (selectedStep): string => {
  return (
    selectedStep?.data?.cross_sell_widget_data?.cross_sell_widget_product_data
      ?.product_pitching_type || ''
  );
};
