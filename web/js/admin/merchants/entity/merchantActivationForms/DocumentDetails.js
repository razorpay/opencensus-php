import React, { Component } from 'react';

import { notifyError } from 'common/modal';

import { adminFetch } from 'common/fetch';
import EntityRow from 'ui/EntityRow';

export default class DocumentDetails extends Component {
  state = { files: null };

  componentWillMount() {
    adminFetch({
      route_name: 'merchant_activation_files',
      url_params: {
        id: this.props.merchantId,
      },
      account_id: this.props.merchantId,
    })
      .then(data => {
        this.setState({ files: data.files });
      })
      .catch(err => {
        notifyError(err);
      });
  }

  render() {
    const { files } = this.state;

    return (
      <div class="container">
        <header>{this.props.title}</header>
        {!files ? (
          <div class="spinner center m-t" />
        ) : (
          <div>
            <banner class="info">
              The links to uploaded files are valid for one hour, refresh the
              page if they do not work.
            </banner>
            <div style={{ width: '70%', margin: 'auto' }}>
              <EntityRow
                label="Business Proof"
                className="m-t left-align"
                value={() => (
                  <div>
                    <span class="info-block">
                      Upload scan of following (not necessary for
                      proprietership):
                      <ul>
                        <li>
                          Partnership Agreement (Mandatory, if partnership firm)
                        </li>
                        <li>
                          Certificate of Incorporation (Mandatory if private
                          limited)
                        </li>
                        <li>Trust/Society/NGO etc. registration proof</li>
                      </ul>
                    </span>
                    <div class="m-t left-align">
                      {
                        do {
                          if (files.business_proof) {
                            <a href={files.business_proof} target="_blank">
                              {files.business_proof}
                            </a>;
                          } else {
                            <a>No file Uploaded</a>;
                          }
                        }
                      }
                    </div>
                  </div>
                )}
              />

              <EntityRow
                label="Business Operation Proof"
                className="separate m-t left-align"
                value={() => (
                  <div>
                    <span class="info-block">
                      Upload scan of following :
                      <ul>
                        <li>MOA and AOA (Mandatory if private limited)</li>
                        <li>
                          Sales tax/Service Tax or Shop Act Registration
                          (Mandatory for
                          Proprietership/Partnership/Trust/Society etc.){' '}
                        </li>
                      </ul>
                    </span>
                    <div class="m-t left-align">
                      {
                        do {
                          if (files.business_operation_proof) {
                            <a
                              href={files.business_operation_proof}
                              target="_blank"
                            >
                              {files.business_operation_proof}
                            </a>;
                          } else {
                            <a>No file Uploaded</a>;
                          }
                        }
                      }
                    </div>
                  </div>
                )}
              />

              <EntityRow
                label="Business PAN"
                className="separate m-t left-align"
                value={() => (
                  <div>
                    <span class="info-block">
                      Company Pan Card (Sole Proprietor can use personal PAN)
                    </span>
                    <div class="m-t left-align">
                      {
                        do {
                          if (files.business_pan_proof) {
                            <a href={files.business_pan_proof} target="_blank">
                              {files.business_pan_proof}
                            </a>;
                          } else {
                            <a>No file Uploaded</a>;
                          }
                        }
                      }
                    </div>
                  </div>
                )}
              />

              <EntityRow
                label="Address Proof of Company's Operational Addresss"
                className="separate m-t left-align"
                value={() => (
                  <div>
                    <span class="info-block">
                      Upload one of following:
                      <ul>
                        <li>Bank Account Statement (past three months)</li>
                      </ul>
                    </span>
                    <div class="m-t left-align">
                      {
                        do {
                          if (files.address_proof) {
                            <a href={files.address_proof} target="_blank">
                              {files.address_proof}
                            </a>;
                          } else {
                            <a>No file Uploaded</a>;
                          }
                        }
                      }
                    </div>
                  </div>
                )}
              />

              <EntityRow
                label="Authorised Signatory Proof"
                className="separate m-t left-align"
                value={() => (
                  <div>
                    <div class="m-t left-align">
                      {
                        do {
                          if (files.promoter_proof) {
                            <a href={files.promoter_proof} target="_blank">
                              {files.promoter_proof}
                            </a>;
                          } else {
                            <a>No file Uploaded</a>;
                          }
                        }
                      }
                    </div>
                  </div>
                )}
              />

              <EntityRow
                label="Address Proof of Company's Operational Addresss"
                className="separate m-t left-align"
                value={() => (
                  <div>
                    <span class="info-block">
                      Upload PAN card scan of at least one authorised signatory,
                      whose details have been filled earlier. In case of sole
                      proprietership, upload your personal PAN.
                    </span>
                    <div class="m-t left-align">
                      {
                        do {
                          if (files.promoter_pan_proof) {
                            <a href={files.promoter_pan_proof} target="_blank">
                              {files.promoter_pan_proof}
                            </a>;
                          } else {
                            <a>No file Uploaded</a>;
                          }
                        }
                      }
                    </div>
                  </div>
                )}
              />

              <EntityRow
                label="Authorised Signatory Address Proof"
                className="separate m-t left-align"
                value={() => (
                  <div>
                    <span class="info-block">
                      Upload address proof of at least one authorised signatory,
                      whose details have been filled earlier. In case of sole
                      proprietership, upload your personal PAN.
                    </span>
                    <div class="m-t left-align">
                      {
                        do {
                          if (files.promoter_address_proof) {
                            <a
                              href={files.promoter_address_proof}
                              target="_blank"
                            >
                              {files.promoter_address_proof}
                            </a>;
                          } else {
                            <a>No file Uploaded</a>;
                          }
                        }
                      }
                    </div>
                  </div>
                )}
              />
            </div>
          </div>
        )}
      </div>
    );
  }
}
