import React, { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import { refreshConfig, uploadLogo } from 'merchant/modules/config'
import * as NotificationActions from 'merchant/modules/notifications'

import CustButton from 'rzp/ui/CustButton'

@connect(
  (state) => {
    return {
      user: state.session.user,
      config: state.config.config,
      showImageSelector: state.config.showImageSelector,
      error: state.config.error
    }
  },
  { refreshConfig, uploadLogo, ...NotificationActions }
)
export default class CheckoutTheme extends Component {
  constructor() {
    super(...arguments)
    this.onFileUpload = this.onFileUpload.bind(this);
  }


  /* Logo Upload */
  onFileUpload(evnt, fieldname) {
    let fileName = evnt.target.value.split('\\');
    fileName = fileName[fileName.length - 1];
    // this.setState({isUploading: true});

    const file = evnt.target.files[0];
    // var file = $files[0];
    var allowed_types = [
      'image/jpeg',
      'image/png',
      'image/jpg'
    ];
    if (allowed_types.indexOf(file.type) <= -1) {
      this.props.showNotification({
        type: 'danger',
        message: 'Invalid filetype. Only jpg/jpeg and png files are allowed.'
      });
      return;
    }
    if (file.size > 1048576) {
      this.props.showNotification({
        type: 'danger',
        message: 'Max file size allowed is 1 MB.'
      });

      return;
    }
    this.props.showNotification({
      type: 'info',
      message: 'Uploading...'
    });

    var params = {
      route_name: 'merchant_edit_config_logo',
      // mode: $scope.mode,
      file_name: fieldname
    };

    this.props.uploadLogo(file, fieldname).then((res)=>{
      if (res.success) {
        this.props.showNotification({
          type: 'success',
          message: 'File Uploaded Successfully'
        }, true);
      }
    }).catch((err)=>{
      this.props.showNotification({
        type: 'danger',
        message: err.errors
      }, true);

    })
  }

  render() {
    return (
      <div class="panel panel-default">
        <div class="panel-heading"
             style={{backgroundColor: '#eaeff0'}}>
          Checkout Theme
        </div>
        <form class="form-horizontal ">
          <div class="panel-body border-bottom">

            <label class="control-label"><strong>Theme Color</strong></label>
            <div class="m-t">
              <div class="col-sm-2">
                <input class="form-control brand-color-picker m-l-neg"
                       type="color"
                       value={this.props.config.brand_color ? this.props.config.brand_color : '#168AFA'}
                       onChange={(evnt)=> {
                         let {brand_color , ...rest} = this.props.config;
                         brand_color = evnt.target.value;
                         let newConfig = {brand_color, ...rest}
                         this.props.refreshConfig(newConfig);

                         this.refs['brand_color_hex'].value = brand_color;
                       }} />
              </div>
              <div class="col-sm-3">
                {
                  this.props.config.brand_color ? (
                    <input class="form-control brand-color-input m-l-neg"
                           type="text"
                           placeholder="Example: #168AFA"
                           ref="brand_color_hex"
                           defaultValue={this.props.config.brand_color ? this.props.config.brand_color : '#168AFA'}
                           onChange={(evnt)=> {
                             let {brand_color , ...rest} = this.props.config;
                             brand_color = evnt.target.value;
                             if (brand_color.length ===7 && /^#[0-9a-f]{6}$/i.test(brand_color)) {
                               let newConfig = {brand_color, ...rest}
                               this.props.refreshConfig(newConfig);
                             }
                           }}/>
                  ) : null
                }
              </div>
              <div class="clearfix"></div>
              <span class="help-block m-t m-b-none">Choose a theme color to customize the checkout form. The default theme color will be used if none is specified. <strong>Use the color picker or enter the hexadecimal color code.</strong></span>
            </div>
          </div>

          <div class="panel-body">
            <label class="control-label"><strong>Your Logo</strong></label>
            <div class="m-t m-b">
              {
                !this.props.showImageSelector ? (
                  <div class="pull-left m-r" >
                    <img src={this.props.config.logo_url} width="72" height="72" />
                  </div>
                ) : null
              }
              <div class="pull-left col-sm-4 file-upload-container m-l-neg">
                <CustButton handleClick={(evnt)=> this.onFileUpload(evnt, 'logo')}/>
                <i class="m-t">Max file size: 1MB</i>
              </div>
              <div class="clearfix"></div>
            </div>
            <span class="help-block m-b-none ">
                  Upload your logo that will appear on the checkout form. Choose a square image of minimum dimensions 256x256 px.
                </span>
            <div class="clearfix"></div>
            <div class="m-t">

              <div class=" text-center prev-next">
                <button type="submit"
                        class="btn btn-save btn-default btn-rounded pull-right"
                        onClick={this.props.saveConfig}>
                  Save Changes
                </button>
              </div>
            </div>

          </div>
        </form>
        <footer class="panel-footer">
          {/*
           <div class="text-center m-t m-b">
           <alert ng-repeat="alert in alerts.getAlerts()" type="{{alert.type}}" close="alerts.closeAlert($index)">{{alert.msg}}</alert>
           </div>
           */}
        </footer>
      </div>
    );
  }
}

