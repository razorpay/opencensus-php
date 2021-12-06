import * as yup from 'yup';

/*

Structure:
routeName: {
  environmentType: 'channenID'
}

*/
export const routeToChannelIDMap = {
  default: {
    dev: 'HTdu8cC7FJEIHC',
    beta: 'HTdu8cC7FJEIHC',
    stage: 'HTdu8cC7FJEIHC',
    production: 'HpP3cspZ3AcuV2',
  },
  home: {
    dev: 'HTdu8cC7FJEIHC',
    beta: 'HTdu8cC7FJEIHC',
    stage: 'HTdu8cC7FJEIHC',
    production: 'HpP3cspZ3AcuV2',
  },
};

export const bankingRouteToChannelIDMap = {
  default: {
    dev: 'ILpOiPdxsl62QN',
    beta: 'ILpOiPdxsl62QN',
    stage: 'ILpOiPdxsl62QN',
    axis: 'ILpOiPdxsl62QN',
    production: 'IMN6odavPeZlSu',
  },
  home: {
    dev: 'ILpOiPdxsl62QN',
    beta: 'ILpOiPdxsl62QN',
    stage: 'ILpOiPdxsl62QN',
    axis: 'ILpOiPdxsl62QN',
    production: 'IMN6odavPeZlSu',
  },
};

export const assetNames = {
  ANNOUNCEMENT: 'ANNOUNCEMENT',
};

const trackingDataSchema = yup
  .object()
  .optional()
  .default(undefined)
  .shape({
    campaign: yup.string().required().strict(true),
    campaign_description: yup.string().required().strict(true),
    sub_campaign: yup.string().optional().strict(true),
    sub_campaign_description: yup.string().optional().strict(true),
    campaign_id: yup.string().optional().strict(true),
    sub_campaign_id: yup.string().optional().strict(true),
    meta: yup
      .object()
      .optional()
      .shape({
        product_feature: yup.string().optional().strict(true),
      }),
  });

const urlTest = yup
  .mixed()
  .required()
  .test('checkString', 'error: error in checking string', (text) => typeof text === 'string');

export const announcementSchema = yup.object().shape({
  title: yup.string().required().strict(true),
  description: yup.string().required().strict(true),
  icon: yup.string().required().strict(true),
  id: yup.string().required().strict(true),
  start_ts: yup.number().strict(true).required(),
  end_ts: yup.number().strict(true).required(),
  buttons: yup
    .array()
    .required()
    .of(
      yup.object().shape({
        type: yup.string().required().strict(true),
        label: yup.string().required().strict(true),
        url: urlTest,
      }),
    ),
  l2_content: yup
    .object()
    .optional()
    .default(undefined)
    .shape({
      content: yup.string().required(),
      buttons: yup
        .array()
        .optional()
        .default(undefined)
        .of(
          yup.object().shape({
            type: yup.string().required().strict(true),
            label: yup.string().required().strict(true),
            url: urlTest,
          }),
        ),
    }),
  tracking_data: trackingDataSchema,
});
