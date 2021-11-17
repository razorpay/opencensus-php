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

export const announcementSchema = yup.object().shape({
  title: yup.string().required(),
  description: yup.string().required(),
  icon: yup.string().required(),
  id: yup.string().required(),
  start_ts: yup.number().strict(true).required(),
  end_ts: yup.number().strict(true).required(),
  buttons: yup
    .array()
    .required()
    .of(
      yup.object().shape({
        type: yup.string().required(),
        label: yup.string().required(),
        url: yup
          .mixed()
          .required()
          .test(
            'checkString',
            'error: error in checking string',
            (text) => typeof text === 'string',
          ),
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
            type: yup.string().required(),
            label: yup.string().required(),
            url: yup
              .mixed()
              .required()
              .test(
                'checkString',
                'error: error in checking string',
                (text) => typeof text === 'string',
              ),
          }),
        ),
    }),
});
