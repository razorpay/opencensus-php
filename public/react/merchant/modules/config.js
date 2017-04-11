import ajax from 'merchant/utils/ajax'
import { set, merge } from 'rzp/utils/immutable'

const FETCH_CONFIG_AND_FEATURES = 'FETCH_CONFIG'
const REFRESH_CONFIG = 'REFRESH_CONFIG'
const UPDATE_FEATURES = 'UPDATE_FEATURES'

const getConfig = () => {
  var params = {
    route_name: 'merchant_fetch_config'
  };

  return ajax({
    url: '/user/generic',
    data: params,
    appendModeInQueryParam: true,
  })
};

const fetchFeatures = (userCurrent) => {
  var params = {
    route_name: 'merchant_get_features',
    url_params: {
      '{id}': userCurrent
    }
  };
  return ajax({
    url: '/user/generic',
    data: params,
    appendModeInQueryParam: true
  })
}

/*Fetch config*/
export const fetchConfig = () => {
  return (dispatch) => {
    return getConfig().then((response)=> {
      return response.data
    });
  }
};

/*Fetch config and features*/
export const fetchConfigsFeatures = (userCurrent)=> {
  return (dispatch) => {
    return dispatch({
      type: FETCH_CONFIG_AND_FEATURES,
      payload: Promise.all([
        getConfig(),
        fetchFeatures(userCurrent)
      ]).then((values) => {
        if ( !values[0].success || !values[1].success) {
          throw "Couldn't load balance data";
        }
        return values;
      })
    })
  }
}

/*update features*/
export const updateFeatures = (featureData, userCurrent) => {
  var params = {
    route_name: 'merchant_update_features',
    // mode: $scope.mode
  };
  params.url_params = {
    '{id}': userCurrent
  };
  params.body = featureData;

  return (dispatch) => {
    return ajax({
      url: '/user/generic',
      method: 'post',
      data: params,
      appendModeInQueryParam: true
    })
  }
}

/*update config*/
export const updateConfig = (data) => {
  var params = {
    route_name: 'merchant_edit_config',
    body: data
  };
  return (dispatch) => {
    return ajax({
      url: '/user/generic',
      method: 'put',
      data: params,
      appendModeInQueryParam: true
    })
  }
}

/*update logo*/
export const uploadLogo = (file, fieldname) => {
  var params = {
    route_name: 'merchant_edit_config_logo',
    file_name: fieldname,
    file: file,
  };

  var fd = new FormData();
  for (var field in params) {
    var value = params[field];
    fd.append(field, value);
  }

  return (dispatch) => {
    return ajax({
      url: '/user/generic',
      file: file,
      data: fd,
      method: 'post',
      processData: false,
      contentType: false,
      appendModeInQueryParam: true
    })
  }
}

let initialState = {
  config: {},
  showImageSelector: false,
  features: []
}


/* create config in proper format*/
const createConfig = (config) => {
  let brand_color;
  let transaction_report_email;
  let logo_url;

  brand_color = config.brand_color ? config.brand_color : null;
  transaction_report_email = config.transaction_report_email.join(',');

  /**
   * API is currently returning invalid logo urls
   * so we need to translate it into a valid URL
   */
  if ((config.logo_url !== null) && !/^http/.test(config.logo_url)) {
    logo_url = 'https://cdn.razorpay.com' + config.logo_url.replace(/\.([^\.]+$)/,'_medium.$1');
  }
  else {
    logo_url = config.logo_url;
  }
  return {
    brand_color,
      transaction_report_email,
      logo_url
  }
};

/*frontend only change in config*/
export const refreshConfig = (config)=> {
  return (dispatch) => {
    return dispatch({
      type: REFRESH_CONFIG,
      config
    })
  }
}


export default function (state = initialState, action) {
  switch(action.type) {
    case `${FETCH_CONFIG_AND_FEATURES}::PENDING`:
      return merge(state, {loading: true})

    case `${FETCH_CONFIG_AND_FEATURES}::SUCCESS`:
      return merge(state, {
        loading: false,
        config: createConfig(action.payload[0].data),
        showImageSelector: state.config.logo_url === null ? true : false,
        features: action.payload[1].data.features,
        error: null,
      })

    case `${FETCH_CONFIG_AND_FEATURES}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.payload.errors,
        ...initialState
      })
    case 'REFRESH_CONFIG':
          return merge(state, {
            config: action.config
          })

    default:
      return state
  }
}
