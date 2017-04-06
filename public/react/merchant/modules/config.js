import ajax from 'merchant/utils/ajax'

export const fetchConfig = () => {
  return (dispatch) => {
    return ajax({
      url: '/user/generic',
      appendModeInQueryParam: true,
      data: {
        route_name: 'merchant_fetch_config'
      }
    })
  }
};

export const fetchFeatures = (userCurrent) => {
 const params = {
    route_name: 'merchant_get_features',
    url_params: {
      '{id}': userCurrent
    }
  };

  return (dispatch) => {
    return ajax({
      url: '/user/generic',
      data: params,
      appendModeInQueryParam: true
    })
  }
};

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

export const saveConfig = (data) => {
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
