import React, { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import { fetchConfig, saveConfig, uploadLogo, fetchFeatures, updateFeatures } from 'merchant/modules/config'
import * as NotificationActions from 'merchant/modules/notifications'
import CustButton from 'rzp/ui/CustButton'
import FlashCheckout from 'merchant/components/Config/FlashCheckout'

@connect(
  (state) => {
    return {
      user: state.session.user
    }
  },
  { fetchConfig, saveConfig, uploadLogo, fetchFeatures, updateFeatures, ...NotificationActions }
)
export default class Congfiguration extends Component {
  constructor() {
    super(...arguments)

    this.state = {
      config: {},
      showImageSelector: false
    }
    this.onFileUpload = this.onFileUpload.bind(this);
    this.saveConfig = this.saveConfig.bind(this);
  }

  componentWillMount() {
    this.props.fetchConfig().then((res)=>{
      if(res.success) {

        this.setConfig(res.data);

        if (this.state.config.logo_url === null) {
          this.setState({showImageSelector: true});
        }
      }
    }).catch((err)=>{
      this.props.showNotification({
        type: 'danger',
        message: err.errors
      }, true)
    });
  }

  /* Set Config*/
  setConfig(config) {
    let brand_color;
    let transaction_report_email;
    let logo_url;

    brand_color = config.brand_color ? config.brand_color : null;

    // This always stays as a string, except when we send it back
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

    this.setState({
      config: {
        brand_color,
        transaction_report_email,
        logo_url
      }
    });
  };

  /* Save Config*/
  saveConfig () {
    const config = this.state.config;
    const data = {
      'brand_color': config.brand_color ? config.brand_color.substr(1).toUpperCase() : null,
      'transaction_report_email': config.transaction_report_email ? config.transaction_report_email.split(',') : null
    };

    this.props.saveConfig(data).then((res) => {
      if (res.success) {
        this.props.showNotification({
          type: 'success',
          message: 'Configuration Updated'
        }, true);
      }
    }).catch((err) => {
      this.props.showNotification({
        type: 'danger',
        message: err.errors
      }, true)
    });
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

    }).then(()=>{
      this.setState({locked: false});
    });
  }


  renderCheckoutTheme() {
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
                       value={this.state.config.brand_color ? this.state.config.brand_color : '#168AFA'}
                       onChange={(evnt)=> {
                         let {brand_color , ...rest} = this.state.config;
                         brand_color = evnt.target.value;
                         this.setState({
                           config: {brand_color, ...rest}
                         });
                         this.refs['brand_color_hex'].value = brand_color;
                       }} />
              </div>
              <div class="col-sm-3">
                {
                  true || this.state.config.brand_color ? (
                    <input class="form-control brand-color-input m-l-neg"
                           type="text"
                           placeholder="Example: #168AFA"
                           ref="brand_color_hex"
                           defaultValue={this.state.config.brand_color ? this.state.config.brand_color : '#168AFA'}
                           onChange={(evnt)=> {
                             let {brand_color , ...rest} = this.state.config;
                             brand_color = evnt.target.value;
                             if (brand_color.length ===7 && /^#[0-9a-f]{6}$/i.test(brand_color)) {
                               this.setState({
                                 config: {brand_color, ...rest}
                               });
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
                !this.state.showImageSelector ? (
                  <div class="pull-left m-r" >
                    <img src={this.state.config.logo_url} width="72" height="72" />
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
                        onClick={this.saveConfig}>
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

  renderEmailNotifications() {
    return (
      <div class="panel panel-default">
        <div class="panel-heading">
          Email Notifications
        </div>
        <div class="panel-body">

          <div class="form-group">
            <div class="">
              <div class="help-block m-b">
                Enter email addresses that will receive email notifications regarding payments, settlements, daily payment reports, webhooks, etc. (You can enter multiple email addresses separated by a comma.)
              </div>
            </div>
          </div>
          <div class="m-t m-b text-center prev-next">
            <div class="col-sm-10">
              <input type="text"
                     class="form-control m-l-neg"
                     maxLength="255"
                     value ={this.state.config.transaction_report_email ? this.state.config.transaction_report_email : ''}
                     onChange={(evnt)=>{
                       let {transaction_report_email , ...rest} = this.state.config;
                       transaction_report_email = evnt.target.value;
                       this.setState({
                         config: {transaction_report_email, ...rest}
                       });
                     }}/>
            </div>
            <button class="btn btn-save btn-default btn-rounded col-sm-2"
                    type="submit"
                    onClick={this.saveConfig}>
              Save Changes
            </button>
          </div>
        </div>
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

  render() {
    return (
      <div class='react-root'>

        <div class="bg-light lter b-b wrapper-md">
          <h1 class="m-n font-thin h3">
            Configuration
            <spinner class="inline"></spinner>
          </h1>
        </div>

        <div class="wrapper-md col-sm-8 col-sm-offset-2" >
          {this.renderCheckoutTheme()}
          <FlashCheckout user={this.props.user}
                         fetchFeatures={this.props.fetchFeatures}
                         updateFeatures={this.props.updateFeatures}
                         showNotification={this.props.showNotification}/>
          {this.renderEmailNotifications()}
        </div>
      </div>
    )
  }
}

