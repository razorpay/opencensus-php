import React from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'react-router-dom';
import { ModalContent } from 'common/new-ui/Modal';
import { closeModal, notifySuccess, notifyError } from 'razorx/components/Modal';
import Form from 'razorx/components/ui/Form';
import { TextAreaField } from 'razorx/components/ui/Field';
import { splitzFetch } from 'razorx/helpers/fetch';

@withRouter
export default class WhitelistExperiment extends React.Component {
  state = {
    isSaving: false,
    variants: this.props.variants,
  };

  onSubmit = () => {
    const payload = {
      id: this.props.data.id,
      whitelisting: this.state.variants
        .map((variant) => ({
          entity_id: variant.id,
          ids: variant.whitelistedIds,
        }))
        .filter((variant) => variant.ids.length),
    };

    const url = 'experiment.v1.ExperimentAPI/Action';
    const successMsg = 'Successfully updated whitelist';

    this.setState({ isSaving: true });

    splitzFetch({ url, data: payload })
      .then(() => {
        this.setState({ isSaving: false });
        notifySuccess(successMsg);
        closeModal();
        this.props.onEdit();
      })
      .catch((err) => {
        this.setState({ isSaving: false });
        notifyError(err);
      });
  };

  render() {
    const { isSaving, variants } = this.state;

    return (
      <ModalContent class="modal-features modal-json-edit" header="Whitelist Entity IDs">
        <Form
          onSubmit={this.onSubmit}
          class="full-span full-elements"
          style={{ opacity: isSaving ? 0.5 : 1 }}
        >
          {isSaving && <div className="spinner center" />}
          <div className="sub-description" style={{ color: 'orange' }}>
            <i
              className="fa fa-exclamation-circle"
              style={{ paddingRight: '5px', paddingTop: '3px' }}
            />
            Whitelisting is only for testing/debugging purpose and has limit of 50. Please use
            audience and segments for bucketing
          </div>
          <br />
          {variants.map((variant, index) => (
            <div
              className="segment pad-highlight"
              key={variant.id}
              style={{ padding: '20px 0px', borderBottom: '1px solid rgba(0,0,0,0.04)' }}
            >
              <span className="label" style={{ fontSize: '11px' }}>
                Variant #{index + 1}
              </span>
              <div>
                <span className="square-pills label-semi-muted" style={{ fontSize: '14px' }}>
                  {variant.name}
                </span>
              </div>
              <br />
              <TextAreaField
                label="Entity IDs: (max 50)                       (comma seperated)"
                placeholder="Add comma separated list of IDs"
                value={variant.whitelistedIds.join()}
                onChange={({ target: { value: ids } }) => {
                  this.setState({
                    variants: variants.map((v) => {
                      if (v.id === variant.id) {
                        return {
                          ...v,
                          whitelistedIds: ids.split(','),
                        };
                      }
                      return v;
                    }),
                  });
                }}
              />
            </div>
          ))}
          <div style={{ marginTop: 24 }} />
          <div className="footer">
            <button className="btn btn--primary">
              Save Whitelisted IDs
              <span className="spin-btn" />
            </button>
          </div>
        </Form>
      </ModalContent>
    );
  }
}

WhitelistExperiment.defaultProps = {
  data: {},
  onEdit: () => {},
};

WhitelistExperiment.propTypes = {
  data: PropTypes.object,
  variants: PropTypes.array,
  onEdit: PropTypes.func,
};
